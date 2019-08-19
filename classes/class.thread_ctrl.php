<?php
/**
 * @author: Mecxi Musa
 * Control threading for memory management and system resources
 */

class thread_ctrl
{
    /* start thread for current user */
    public static function run($script_name=null, $params=null, $shell_control=null)
    {
        $output = null;
        if (is_null($shell_control)){
            exec("php ".$script_name.".php $params", $output);
        } else {
            exec($shell_control, $output);
        }
        return $output;
    }

    /* log current thread */
    public static function log($user_id, $operation_state, $service_local_id)
    {
        /* operation_stat = 0 : thread has started  | 1 done */
        if ($operation_state){
            db::sql("UPDATE `tl_threading_services` SET `end_time` = CURRENT_TIMESTAMP, `completed` = '$operation_state' WHERE `user_id` = '$user_id' AND service_id = '$service_local_id'", "tl_gateway");
        } else {
            db::sql("INSERT INTO `tl_threading_services` (`service_id`, `user_id`, `start_time`, `completed`) VALUES('$service_local_id', '$user_id', CURRENT_TIMESTAMP, '$operation_state')", "tl_gateway");
        }
    }

    /* check if current user has already being queued for today service */
    public static function has_queued($user_id, $service_local_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_threading_services` WHERE `user_id` = '$user_id' AND service_id = '$service_local_id'", "tl_gateway"));
    }

    /* check if the user is first_content_broadcast_queue */
    public static function has_first_content_queued($user_id, $service_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_delay_content_delivery` WHERE user_id = '$user_id' AND service_id = '$service_id';", "tl_gateway"));
    }

    public static function has_queued_started($user_id=null, $service_local_id)
    {
        if (is_null($user_id)){
            return mysqli_num_rows(db::sql("SELECT * FROM `tl_threading_services` WHERE service_id = '$service_local_id' LIMIT 1", "tl_gateway"));
        } else {
            return mysqli_num_rows(db::sql("SELECT * FROM `tl_threading_services` WHERE `user_id` = '$user_id' AND service_id = '$service_local_id'  LIMIT 1", "tl_gateway"));
        }
    }


    /* return uncompleted thread in order to allocated more threads */
    public static function uncompleted_thread($service_local_id)
    {
        return mysqli_num_rows(db::sql("SELECT `completed` FROM `tl_threading_services` WHERE `completed` = 0 AND service_id = '$service_local_id';", "tl_gateway"));
    }
    /* return completed thread in order to determine phase operation in service preparation */
    public static function completed_thread($service_local_id)
    {
        return mysqli_num_rows(db::sql("SELECT `completed` FROM `tl_threading_services` WHERE `completed` = 1 AND service_id = '$service_local_id';", "tl_gateway"));
    }

    /* return running system threads */
    public static function running_threads($thread_name, $display=null)
    {
        return (is_null($display)) ?
            count(self::run(null, null, 'ps -eo pcpu,pid,user,args | sort -k 1 -r | grep "'.$thread_name.'"')) - 2:
            self::run(null, null, 'ps -eo pcpu,pid,user,args | sort -k 1 -r | grep "'.$thread_name.'"');
    }

    /* check all services have been completed to unset */
    public static function completed_thread_all_services()
    {
        $thread_results = 0;
        $service_list = self::all_service_status();
        foreach($service_list as $service) {
            $service_local_id = $service[0];
            $start_date = $service[1];
            $broadcast = $service[2];
            $end_date = $service[4];
            /* check if the service has launched and broadcast is enabled */
            if (self::service_has_started($start_date, $end_date) && $broadcast != 0){
                $completed_threads = self::completed_thread($service_local_id);
                $user_base = user::active_user_base($service_local_id);
                if ( $completed_threads == $user_base || $completed_threads > $user_base ){
                    /* log current service completed */
                    services::log_service_process($service_local_id, 2, 'completed broadcast operation', 1);
                    ++$thread_results;
                }
            }
        }
        return $thread_results;
    }


    /* generate a random unique trace_id */
    public static function get_unique_trace_id()
    {
        return rand(0, 2147483647);
    }

    /* generate a random code */
    /* generate codeID */
    public static function generate_random_code($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /* Schedule retry for failed operation */
    public static function set_retry_operation($set_allow, $service_id)
    {
        /* 1: enable retry for today | 2: disable retry for today */
        $date = date('Y-m-d');
        $current_count = thread_ctrl::has_service_retries($service_id);
        if ($set_allow == 1){
            if (is_int($current_count)){
                db::sql("UPDATE `tl_services_retries` SET `count` = ". self::retry_count($service_id).", `completed` = 0 WHERE date_created LIKE '$date%' AND `service_id` = '$service_id'", "tl_datasync");
            } else {
                db::sql("INSERT INTO `tl_services_retries` (`date_created`, `time`, `count`, `completed`, `service_id`) VALUES (CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1, 0, '$service_id')", "tl_datasync");
            }
        } else if ($set_allow == 2) {
            db::sql("UPDATE `tl_services_retries` SET `completed` = 1, `date_completed` = CURRENT_TIMESTAMP
            WHERE `date_created` = '$date' AND `service_id` = '$service_id'", "tl_datasync");
        } else {
            /* update retries count on success retry schedule operation */
            if ($current_count > 0 ){
                db::sql("UPDATE `tl_services_retries` SET `count` = ". self::retry_decount($service_id).", `completed` = 0 WHERE date_created LIKE '$date%' AND `service_id` = '$service_id'", "tl_datasync");
            }
        }
    }

    private static function retry_count($service_id)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `count` FROM `tl_services_retries` WHERE date_created LIKE '$date%' AND `service_id` = '$service_id'", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($c)=mysqli_fetch_array($result)){
                return (int)$c + 1;
            }
        }
        return 0;
    }

    private static function retry_decount($service_id)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `count` FROM `tl_services_retries` WHERE date_created LIKE '$date%' AND `service_id` = '$service_id'", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($c)=mysqli_fetch_array($result)){
                return  (int)$c - 1;
            }
        }
        return 0;
    }

    /* check if a retry operation has been set */
    public static function has_service_retries($service_id)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `count` FROM `tl_services_retries` WHERE date_created LIKE '$date%' AND `completed` = 0 AND `service_id` = '$service_id'", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($c)=mysqli_fetch_array($result)){
                return (int)$c;
            }
        }
        return null;
    }

    /* get all services status */
    public static function all_service_status($service_id=null)
    {
        $result = (is_null($service_id)) ?
            db::sql("SELECT service_local_id, start_date, broadcast, set_thread, end_date, `name` FROM `tl_services`", DB_NAME):
            db::sql("SELECT service_local_id, start_date, broadcast, set_thread, end_date, `name` FROM `tl_services` WHERE `service_local_id` = '$service_id'", DB_NAME);
        $service_list = null;
        if (mysqli_num_rows($result)){
            while(list($service_local_id, $start_date, $broadcast, $set_thread, $end_date, $name)= mysqli_fetch_array($result)){
                $service_list[] = array((int)$service_local_id, $start_date, (int)$broadcast, (int)$set_thread, $end_date, $name);
            }
        }
        return $service_list;
    }

    /* check if the service has started */
    public static function service_has_started($start_date, $end_date)
    {
        /* set current date */
        $current_date = date('Y-m-d H:i:s');
        $launch_date = date('Y-m-d H:i:s', strtotime($start_date));
        $close_date = date('Y-m-d H:i:s', strtotime($end_date));
        /* check service state */
        if ($current_date > $launch_date && $current_date < $close_date){
            /* service has started */
            return true;
        } else if ($current_date > $close_date){
            /* service has closed */
            return false;
        }
        /* the service hasn't started */
        return null;
    }


    /* return thread stats for the given service id or retries stats */
    public static function return_thread_stats($service_local_id, $retries=null)
    {
        return is_null($retries) ?
            mysqli_num_rows(db::sql("SELECT * FROM `tl_threading_services` WHERE completed = 1 AND service_id = '$service_local_id'", "tl_gateway")):
            (
            in_array((new services($service_local_id))->service_type, array(1, 3)) ?
                mysqli_num_rows(db::sql("SELECT * FROM `tl_servicing_contents` WHERE service_id = '$service_local_id' AND process_state = 3;", "tl_datasync")):
                mysqli_num_rows(db::sql("SELECT * FROM `tl_datasync_notify` WHERE score = 'opening' AND sent = 0 AND process_end IS NOT NULL;;", "tl_datasync"))
            );
    }

    /* return the time since last given time */
    public static function get_time_ago($time_stamp)
    {
        $time_difference = strtotime('now') - $time_stamp;

        if ($time_difference >= 60 * 60 * 24 * 365.242199) {
            /*
             * 60 seconds/minute * 60 minutes/hour * 24 hours/day * 365.242199 days/year
             * This means that the time difference is 1 year or more
             */
            return self::get_time_ago_string($time_stamp, 60 * 60 * 24 * 365.242199, 'year');
        } elseif ($time_difference >= 60 * 60 * 24 * 30.4368499) {
            /*
             * 60 seconds/minute * 60 minutes/hour * 24 hours/day * 30.4368499 days/month
             * This means that the time difference is 1 month or more
             */
            return self::get_time_ago_string($time_stamp, 60 * 60 * 24 * 30.4368499, 'month');
        } elseif ($time_difference >= 60 * 60 * 24 * 7) {
            /*
             * 60 seconds/minute * 60 minutes/hour * 24 hours/day * 7 days/week
             * This means that the time difference is 1 week or more
             */
            return self::get_time_ago_string($time_stamp, 60 * 60 * 24 * 7, 'week');
        } elseif ($time_difference >= 60 * 60 * 24) {
            /*
             * 60 seconds/minute * 60 minutes/hour * 24 hours/day
             * This means that the time difference is 1 day or more
             */
            return self::get_time_ago_string($time_stamp, 60 * 60 * 24, 'day');
        } elseif ($time_difference >= 60 * 60) {
            /*
             * 60 seconds/minute * 60 minutes/hour
             * This means that the time difference is 1 hour or more
             */
            return self::get_time_ago_string($time_stamp, 60 * 60, 'hour');
        } else {
            /*
             * 60 seconds/minute
             * This means that the time difference is a matter of minutes
             */
            return self::get_time_ago_string($time_stamp, 60, 'minute');
        }
    }

    private static function get_time_ago_string($time_stamp, $divisor, $time_unit)
    {
        $time_difference = strtotime("now") - $time_stamp;
        $time_units      = floor($time_difference / $divisor);

        settype($time_units, 'string');

        if ($time_units === '0') {
            return 'less than 1 ' . $time_unit . ' ago';
        } elseif ($time_units === '1') {
            return '1 ' . $time_unit . ' ago';
        } else {
            /*
             * More than "1" $time_unit. This is the "plural" message.
             */
            // TODO: This pluralizes the time unit, which is done by adding "s" at the end; this will not work for i18n!
            return $time_units . ' ' . $time_unit . 's ago';
        }
    }

    /* get start and end date of the given range ver 1.0 */
    public static function get_start_end_date_ver1($week, $year)
    {
        $time = strtotime("1 January $year", time());
        $day = date('w', $time);
        $time += ((7*$week)+1-$day)*24*3600;
        $return[0] = date('Y-n-j', $time);
        $time += 6*24*3600;
        $return[1] = date('Y-n-j', $time);
        return $return;
    }

    public static function get_start_end_date($week, $year)
    {
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $ret['week_start'] = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $ret['week_end'] = $dto->format('Y-m-d');
        return $ret;
    }

    public static function get_start_end_date_month($target_month=null)
    {
       return is_null($target_month) ?
           array('start_date'=>date('Y-m-01'), 'end_date'=>date("Y-m-t", strtotime(date('Y-m-d')))) :
           array('start_date'=>date('Y-m-01', strtotime("-$target_month months")), 'end_date'=>date("Y-m-t", strtotime("-$target_month months")));
    }

    /***** live MO/MT traffic api report *****/
    public static function mo_mt_live_traffic()
    {
        /* initialise and get past_time_holder */
        $time_holders = self::get_past_time_holder();
        /* get traffic record for the last 60s */
        $datasync = self::get_mo_mt_last_data('datasync');
        $notify = self::get_mo_mt_last_data('notify');
        /* process data mo to its corresponding time holder */
        for ($i = 0; $i < count($time_holders); ++$i) {
            $d_mo = self::get_mo_data_given_time($datasync, $time_holders[$i]['time']);
            $n_mo = self::get_mo_data_given_time($notify, $time_holders[$i]['time']);
            /* merge mo */
            $time_holders[$i]['data'] = $d_mo + $n_mo;
        }
        return $time_holders;
    }

    /* initialise current timestamp and its data holder */
    private static function get_past_time_holder()
    {
        $current_timestamp = date('Y-m-d H:i:s');
        /* initialise an array for the past 60s */
        $past_timestamp = array();
        for($i = 60; $i > -1; --$i){
            $past_timestamp[] = array('time'=> date('Y-m-d H:i:s', strtotime("$current_timestamp -$i seconds")), 'data'=> 0);
        }

        return $past_timestamp;
    }

    /* get last mo_mt_data : traffic on data */
    private static function get_mo_mt_last_data($mo_type)
    {
        /* get last notify traffic */
        $result = ($mo_type == 'notify') ?
            db::sql("SELECT DISTINCT process_end AS time_sent, SUM(sent) AS mo_sent FROM `queued_notify`
                  GROUP BY process_end ORDER BY process_end DESC LIMIT 60;", "tl_gateway")
            : db::sql("SELECT DISTINCT process_end AS time_sent, SUM(sent) AS mo_sent FROM `queued_datasync`
                  GROUP BY process_end ORDER BY process_end DESC LIMIT 60;", "tl_gateway");

        $return_result = null;
        if (mysqli_num_rows($result)){
            while(list($time_sent, $mo_sent) = mysqli_fetch_array($result)){
                $return_result[] = array($time_sent, $mo_sent);
            }
        }
        return $return_result;
    }

    /* fetch mo_data per the given time */
    public static function get_mo_data_given_time($mo_data, $ref_time)
    {
        for($i=0; $i < count($mo_data); ++$i){
            if ($mo_data[$i][0] == $ref_time){
                return (int)$mo_data[$i][1];
                break;
            }
        }

        return 0;
    }

    /* push associative array */
    public static function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        return array_reverse($arr, true);
    }

    /* check if users been notifies */
    public  static function has_notify_sent($user_id, $trivia_id, &$notified_users){
        if ($notified_users){
            foreach($notified_users as $user){
                if ($user_id == $user['user_id'] && $trivia_id == $user['trivia']) return true;
            }
        } return false;
    }

    /* check suspended notification */
    public static function has_notify_suspended($user_id, &$suspended_users)
    {
        if ($suspended_users){
            if (in_array($user_id, $suspended_users)) return true;
        } return false;
    }



}