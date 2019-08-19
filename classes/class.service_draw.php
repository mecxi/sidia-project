<?php
/**
 * User: Mecxi
 * Date: 9/2/2017
 * Time: 8:33 PM
 */

class service_draw
{
    /* obj fields */
    public $id;
    public $name;
    public $desc;
    public $notify;
    public $start_date;
    public $end_date;
    public $draw_type_id;
    public $draw_type_name;
    public $draw_win_no;
    public $draw_engine_type;
    public $active;
    public $services_draw_linked;
    public $date_created;
    public $draw_win_rollout;

    public function __construct($id)
    {
        $result = db::sql("SELECT `name`, `desc`, notify, start_date, end_date, draw_type_id, draw_win_no, draw_engine_type, active, date_created, draw_win_rollout FROM `tl_services_draw_engine` WHERE id = '$id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($name, $desc, $notify, $start_date, $end_date, $draw_type_id, $draw_win_no, $draw_engine_type, $active, $date_created, $draw_win_rollout) = mysqli_fetch_array($result)){
                $this->id = $id;
                $this->name = $name;
                $this->desc = $desc;
                $this->notify = $notify;
                $this->start_date = $start_date;
                $this->end_date = $end_date;
                $this->draw_type_id = (int) $draw_type_id;
                $this->draw_win_no = (int) $draw_win_no;
                $this->draw_engine_type = (int) $draw_engine_type;
                $this->active = (int) $active;
                $this->date_created = $date_created;
                $this->draw_win_rollout = (int) $draw_win_rollout;
            }
        }

        /* initialise services associated */
        $result = db::sql("SELECT service_local_id FROM `tl_services_draw_references` WHERE service_draw_id = '$this->id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($service_local_id) = mysqli_fetch_array($result)){
                $this->services_draw_linked[] = $service_local_id;
            }
        }

        /* initialise current draw type_name */
        $draw_type_ids = services::draw_type_list();
        foreach($draw_type_ids as $draw){
            if ($draw['id'] == $this->draw_type_id){
                $this->draw_type_name = $draw['type'];
            }
        }

    }

    /* update date range for the target service */
    public function update_service_promo_date($start_date, $end_date)
    {
        return db::sql("UPDATE `tl_services_draw_engine` SET start_date = '$start_date', end_date = '$end_date' WHERE id = '$this->id';", DB_NAME);
    }

    /* update service draw type */
    public function update_service_draw_type($draw_type)
    {
        return db::sql("UPDATE `tl_services_draw_engine` SET draw_type_id = '$draw_type' WHERE id = '$this->id';", DB_NAME);
    }

    /* update service draw winner number */
    public function update_service_draw_winner_num($draw_winner_num)
    {
        return db::sql("UPDATE `tl_services_draw_engine` SET draw_win_no = '$draw_winner_num' WHERE id = '$this->id';", DB_NAME);
    }
    /* update service draw notify message */
    public function update_service_draw_notify_msg($draw_notify_msg)
    {
        return db::sql("UPDATE `tl_services_draw_engine` SET notify = '$draw_notify_msg' WHERE id = '$this->id';", DB_NAME);
    }

    /* update service draw winner number */
    public function update_service_draw_select($draw_select)
    {
        return db::sql("UPDATE `tl_services_draw_engine` SET draw_engine_type = '$draw_select' WHERE id = '$this->id';", DB_NAME);
    }

    /* update service draw winner number */
    public function update_service_draw_winner_rollout($draw_win_rollout)
    {
        return db::sql("UPDATE `tl_services_draw_engine` SET draw_win_rollout = '$draw_win_rollout' WHERE id = '$this->id';", DB_NAME);
    }

    /* update service draw linked */
    public function update_service_draw_linked($data)
    {
        /* check if current data needs deletion */
        foreach($this->services_draw_linked as $service_id){
            if (!in_array($service_id, $data)){
                db::sql("DELETE FROM `tl_services_draw_references` WHERE service_local_id = '$service_id' AND service_draw_id = '$this->id';", DB_NAME);
            }
        }

        /* check if current data needs addition */
        $current_service_draw = new service_draw($this->id);
        foreach($data as $service_id){
            if (!in_array($service_id, $current_service_draw->services_draw_linked)){
                db::sql("INSERT INTO `tl_services_draw_references` (service_draw_id, service_local_id) VALUES('$current_service_draw->id', '$service_id');", DB_NAME);
            }
        }
    }

    /* check if the current local_service_id has a service draw */
    public static function has_service_local_id($service_local_id)
    {
        $result = db::sql("SELECT service_draw_id FROM `tl_services_draw_references` WHERE service_local_id = '$service_local_id';", DB_NAME);
        $service_draw_ids = null;
        if (mysqli_num_rows($result)){
            while(list($service_draw_id) = mysqli_fetch_array($result)){
                $service_draw_ids[] = $service_draw_id;
            }
        } return $service_draw_ids;
    }

    /* return service draw type_id for the target service_local_id */
    public static function get_draw_type_ids($service_local_id)
    {
        /* get service_draw_ids associated to the target service_id */
        $service_draw_ids = self::has_service_local_id($service_local_id);
        $draw_type_references = null;
        if ($service_draw_ids){
            foreach($service_draw_ids as $service_draw_id){
                $current_service_draw = new service_draw($service_draw_id);
                $current_date = date('Y-m-d');
                /* check if the current draw service has started */
                if (($current_service_draw->start_date == $current_date || $current_date > $current_service_draw->start_date)
                    && ($current_date < $current_service_draw->end_date) || $current_date == $current_service_draw->end_date){

                    switch($current_service_draw->draw_type_id){
                        case 1:
                            $draw_type_references[] = array('name'=>'D', 'id'=>$current_service_draw->draw_type_id);
                            break;
                        case 2:
                            $draw_type_references[] = array('name'=>'W', 'id'=>$current_service_draw->draw_type_id);
                            break;
                        case 3:
                            $draw_type_references[] = array('name'=>'M', 'id'=>$current_service_draw->draw_type_id);
                            break;
                    }
                }

            }
        }
        return $draw_type_references;
    }

    /* return the service_draw_id as given type_id and target local_service_id */
    public static function target_local_service_draw_id($service_local_id, $draw_type_id)
    {
        /* get service_draw_ids associated to the target service_id */
        $service_draw_ids = self::has_service_local_id($service_local_id);
        if ($service_draw_ids) {
            foreach ($service_draw_ids as $service_draw_id) {
                $current_service_draw = new service_draw($service_draw_id);
                if ($current_service_draw->draw_type_id == $draw_type_id){
                    return $current_service_draw->id;
                }
            }
        } return null;
    }

    /* process current service draw allocation */
    public static function process_draw_allocation($service_local_id, $user_id=null, $message=null, $process_type=null)
    {
        $service_draw_type_ids = service_draw::get_draw_type_ids($service_local_id);
        if ($service_draw_type_ids){
            foreach($service_draw_type_ids as $draw_type){
                switch($draw_type['name']){
                    case 'D':
                        if ($process_type){
                            self::prepare_draw_entries_pool($draw_type['name'], $service_local_id);
                            /* archive current players draw pool */
                            self::archive_players_draw_stats($service_local_id, $draw_type);
                        } else {
                            /* daily entry allocation */
                            services::entry_allocation($user_id, $service_local_id, $draw_type['name']);
                        }
                        break;
                    case 'W':
                        if ($process_type){
                            if (date('D') == 'Sun'){
                                self::prepare_draw_entries_pool($draw_type['name'], $service_local_id);
                                /* archive current players draw pool */
                                self::archive_players_draw_stats($service_local_id, $draw_type);
                            }
                        } else {
                            /* weekly allocation every monday */
                            if (date('D') == 'Mon' || $message == 'subscribe'){
                                services::entry_allocation($user_id, $service_local_id, $draw_type['name']);
                            }
                        }
                        break;
                    case 'M':
                        if ($process_type){
                            $current_month = thread_ctrl::get_start_end_date_month();
                            $end_date = $current_month['end_date'];
                            if (date('d') == date('d', strtotime("$end_date"))){
                                self::prepare_draw_entries_pool($draw_type['name'], $service_local_id);
                                /* archive current players draw pool */
                                self::archive_players_draw_stats($service_local_id, $draw_type);
                            }
                        } else {
                            /* monthly allocation every 1 day of the month */
                            if (date('d') == '01' || $message == 'subscribe'){
                                services::entry_allocation($user_id, $service_local_id, $draw_type['name']);
                            }
                        }
                        break;
                }
            }

        }
    }


    /* prepare users for the entries pool */
    public static function prepare_draw_entries_pool($type, $service_local_id)
    {
        $result = $type == 'D' ? db::sql("SELECT id, user_id, service_id, day_entries FROM `tl_users_stats` WHERE service_id = '$service_local_id';", DB_NAME) :
            ($type == 'W' ? db::sql("SELECT id, user_id, service_id, week_entries FROM `tl_users_stats` WHERE service_id = '$service_local_id';", DB_NAME) :
                db::sql("SELECT id, user_id, service_id, month_entries FROM `tl_users_stats` WHERE service_id = '$service_local_id';", DB_NAME));

        if (mysqli_num_rows($result)){
            while(list($id, $user_id, $service_id, $entries) = mysqli_fetch_array($result)){
                if ($type == 'D'){
                    if (db::sql("INSERT INTO tl_users_stats_draw (user_id, service_id, day_entries) VALUES('$user_id', '$service_id', '$entries')", DB_NAME) === false){
                        /* record exist, do an update */
                        db::sql("UPDATE tl_users_stats_draw SET day_entries = '$entries' WHERE user_id = '$user_id' AND service_id = '$service_id'", DB_NAME);
                    }
                    /* reset current users daily entries */
                    db::sql("UPDATE tl_users_stats SET day_entries = 0 WHERE id = '$id'", DB_NAME);

                } else if ($type == 'W'){
                    if (db::sql("INSERT INTO tl_users_stats_draw (user_id, service_id, week_entries) VALUES('$user_id', '$service_id', '$entries')", DB_NAME) === false){
                        /* record exist, do an update */
                        db::sql("UPDATE tl_users_stats_draw SET week_entries = '$entries' WHERE user_id = '$user_id' AND service_id = '$service_id'", DB_NAME);
                    }
                    /* reset current users weekly entries */
                    db::sql("UPDATE tl_users_stats SET week_entries = 0 WHERE id = '$id'", DB_NAME);
                } else {
                    if (db::sql("INSERT INTO tl_users_stats_draw (user_id, service_id, month_entries) VALUES('$user_id', '$service_id', '$entries')", DB_NAME) === false){
                        /* record exist, do an update */
                        db::sql("UPDATE tl_users_stats_draw SET month_entries = '$entries' WHERE user_id = '$user_id' AND service_id = '$service_id'", DB_NAME);
                    }
                    /* reset current users monthly entries */
                    db::sql("UPDATE tl_users_stats SET month_entries = 0 WHERE id = '$id'", DB_NAME);
                }
            }
        }
    }

    /* archive users stat draw */
    public static function archive_users_stat_draw($service_local_id)
    {
        $result = db::sql("SELECT user_id, day_entries, week_entries, month_entries FROM `tl_users_stats_draw` WHERE service_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($user_id, $day_entries, $week_entries, $month_entries) = mysqli_fetch_array($result)){
                if (db::sql("INSERT INTO `tl_users_stats_draw` (user_id, service_id, day_entries, week_entries, month_entries, date_created)
                VALUES('$user_id', '$service_local_id', '$day_entries', '$week_entries', '$month_entries', CURRENT_TIMESTAMP);", "tl_datasync_archives") === false){
                    $current_date = date('Y-m-d');
                    /* update the archives */
                    db::sql("UPDATE `tl_users_stats_draw` SET day_entries = '$day_entries', week_entries = '$week_entries', month_entries = '$month_entries'
                    WHERE service_id = '$service_local_id' AND  user_id = '$user_id' AND date_created = '$current_date'", "tl_datasync_archives");
                }
            }
        }
    }

    /* archive current players draw pool */
    public static function archive_players_draw_stats($service_local_id, $draw_type)
    {
        /* the service_draw_type will determine whether the target service required to archive daily, weekly, monthly date range */
        $start_date = '';
        $end_date = '';
        /* determine the date range */
        switch($draw_type['name']){
            case 'D':
                $start_date = date('Y-m-d'); $end_date = date('Y-m-d');
                break;
            case 'W':
                $current_week = thread_ctrl::get_start_end_date(date('W'), date('Y'));
                $start_date = $current_week['week_start']; $end_date = $current_week['week_end'];
                break;
            case 'M':
                $current_month = thread_ctrl::get_start_end_date_month();
                $start_date = $current_month['start_date']; $end_date = $current_month['end_date'];
                break;
        }
        $service_draw_id = self::target_local_service_draw_id($service_local_id, $draw_type['id']);
        self::write_players_draw_pool($start_date, $end_date, $service_local_id, $service_draw_id);
    }



    /**** DRAW ENGINE *****/
    /* write current players pool */
    public static function write_players_draw_pool($start_date, $end_date, $service_local_id, $service_draw_id)
    {
        /* get current players for the given date range */
        $users = null;
        self::fetch_players($start_date, $end_date, $users, $service_local_id);

        $process_result = null;
        if (is_array($users)){
            foreach($users as $user_id){
                /* get current user entries */
                $entries = self::get_service_entries($user_id, $service_local_id, (new service_draw($service_draw_id))->draw_type_id);
                $score = self::get_service_points($user_id, $service_local_id);
                $msisdn = (new user($user_id))->msisdn;
                if (db::sql("INSERT INTO `archive_players_draw_stats` (user_id, msisdn, service_id, service_draw_id, score, entries, start_date, end_date)
                VALUES('$user_id', '$msisdn', '$service_local_id', '$service_draw_id', '$score', '$entries', '$start_date', '$end_date');", "tl_datasync_archives") === false){
                    /* update score and points */
                    db::sql("UPDATE `archive_players_draw_stats` SET score = '$score', entries = '$entries' WHERE user_id = '$user_id'
                    AND service_id = '$service_local_id' AND service_draw_id = '$service_draw_id' AND start_date = '$start_date' AND end_date = '$end_date'", "tl_datasync_archives");
                }
            }
        }
    }

    /* get given date range players */
    public static function fetch_players($start_date, $end_date, &$users, $service_local_id)
    {
        $end_date = date('Y-m-d', strtotime("$end_date +1 days"));
        $current_service = new services($service_local_id);
        $result = $current_service->service_type == 1 ?
            db::sql("SELECT DISTINCT user_id FROM `tl_servicing_contents` WHERE service_id = '$service_local_id' AND suspended <> 1 AND process_start > '$start_date%' AND process_start < '$end_date%';", "tl_datasync") :
            db::sql("SELECT DISTINCT user_id FROM `tl_servicing_trivia` WHERE service_id = '$service_local_id' AND process_start > '$start_date%' AND process_start < '$end_date%';", "tl_datasync");

        if (mysqli_num_rows($result)){
            while(list($user_id) = mysqli_fetch_array($result)){
                $users[] = $user_id;
            }
        }
        /* check users are not currently suspended */
        if ($users){
            foreach($users as $key=>$user_id){
                /* check if current user still a subscriber */
                if (!user::has_current_subscription((new user($user_id))->services_stats, $service_local_id)){
                    unset($users[$key]);
                } else {
                    /* for trivia type service, check current notify state */
                    if ($current_service->service_type == 2){
                        if (self::is_player_suspended($user_id, $current_service)){
                            unset($users[$key]);
                        }
                    }
                }
            }
        }
    }

    /* check suspended players */
    private static function is_player_suspended($user_id, $current_service)
    {
        return $current_service->service_type == 1 ?
            mysqli_num_rows(db::sql("SELECT * FROM `tl_servicing_contents` WHERE user_id = '$user_id' AND service_id = '$current_service->service_local_id' AND suspended = 1", "tl_datasync")):
            mysqli_num_rows(db::sql("SELECT * FROM `tl_datasync_notify` WHERE user_id = '$user_id' AND service_id = '$current_service->service_local_id' AND suspended = 1;", "tl_datasync"));
    }

    /* get service entries count */
    public static function get_service_entries($user_id, $service_local_id, $draw_type_id, $notify=null)
    {
        /* determine target table */
        $target_table = is_null($notify) ? 'tl_users_stats_draw' : 'tl_users_stats';

        $result =
            $draw_type_id == 1 ?
                db::sql("SELECT day_entries FROM `". $target_table."` WHERE service_id = '$service_local_id' AND user_id = '$user_id';", DB_NAME):
                ($draw_type_id == 2 ? db::sql("SELECT week_entries FROM `". $target_table."` WHERE service_id = '$service_local_id' AND user_id = '$user_id';", DB_NAME) :
                    db::sql("SELECT month_entries FROM `". $target_table."` WHERE service_id = '$service_local_id' AND user_id = '$user_id';", DB_NAME));

        if (mysqli_num_rows($result)){
            while(list($entries) = mysqli_fetch_array($result)){
                return (int) $entries;
            }
        }
        return 0;
    }
    /* get users points accumulated for the target service */
    public static function get_service_points($user_id, $service_local_id)
    {
        $result = db::sql("SELECT score FROM `tl_users_stats` WHERE user_id = '$user_id' AND service_id = '$service_local_id'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($score) = mysqli_fetch_array($result)){
                return (int) $score;
            }
        } return 0;
    }

    /* return current service draw preview */
    public static function get_players_preview($service_draw_id, $date_range, $print_job=null, $limit=null)
    {
        /* get the target date range */
        $date_range = self::get_selected_date_range($date_range, (new service_draw($service_draw_id))->draw_type_id);
        $res_limit = $limit ? 'LIMIT 5000' : '';

        $result = db::sql("SELECT msisdn, service_id, score, entries FROM `archive_players_draw_stats`
                  WHERE service_draw_id = '$service_draw_id' AND start_date = '". $date_range['start_date']."' AND end_date = '". $date_range['end_date']."' ". $res_limit, "tl_datasync_archives");
        $players_preview = null;
        if (mysqli_num_rows($result)){
            while(list($msisdn, $service_id, $score, $entries) = mysqli_fetch_array($result)){
                $players_preview[] = array('msisdn'=>$msisdn, 'service'=>(new services($service_id))->name, 'score'=>$score, 'entries'=>$entries);
            }
        } else {
            $players_preview = array('error'=>'no data available');
        }
        return isset($players_preview['error']) ? $players_preview : (is_null($print_job) ? array('data'=>$players_preview) : $players_preview);
    }

    /* return current date range selected for the given service draw type */
    public static function get_selected_date_range($current_range, $draw_type_id, $format_requested=null)
    {
        $start_date = '';
        $end_date = '';
        /* determine the date range */
        if ($current_range == 'current'){
            switch($draw_type_id){
                case 1:
                    $start_date = date('Y-m-d'); $end_date = date('Y-m-d');
                    break;
                case 2:
                    $current_week = thread_ctrl::get_start_end_date(date('W'), date('Y'));
                    $start_date = $current_week['week_start']; $end_date = $current_week['week_end'];
                    break;
                case 3:
                    $current_month = thread_ctrl::get_start_end_date_month();
                    $start_date = $current_month['start_date']; $end_date = $current_month['end_date'];
                    break;
            }
        } else {
            /* reformat date range */
            $date_range = explode('|', $current_range);
            switch($draw_type_id){
                case 1:
                    $start_date = date('Y-m-d', strtotime("$date_range[0]")); $end_date = date('Y-m-d', strtotime("$date_range[0]"));
                    break;
                default:
                    $start_date = date('Y-m-d', strtotime("$date_range[0]")); $end_date = date('Y-m-d', strtotime("$date_range[1]"));
                    break;
            }

        }

        return is_null($format_requested) ? array('start_date'=>$start_date, 'end_date'=>$end_date) :
            array( 'format1'=>array('start_date'=>$start_date, 'end_date'=>$end_date),
                'format2'=>array('start_date'=>date('m/d/Y', strtotime("$start_date")), 'end_date'=>date('m/d/Y', strtotime("$end_date"))));
    }

    /* fetch winners list for the given type */
    public static function fetch_draw_winners($service_draw_id, $date_range, $print=null)
    {
        /* get the target date range */
        $date_range = self::get_selected_date_range($date_range, (new service_draw($service_draw_id))->draw_type_id);

        $result = db::sql("SELECT user_id, score, entries, select_type, created_by, date_created, notify FROM `tl_services_draw_winners`
                  WHERE service_draw_id = '$service_draw_id' AND start_date = '". $date_range['start_date']."' AND end_date = '". $date_range['end_date']."';", DB_NAME);
        $draw_winners = null;
        if (mysqli_num_rows($result)){
            while(list($user_id, $score, $entries, $type, $created_by, $date_created, $notify) = mysqli_fetch_array($result)){
                $draw_winners[] = array(
                    'msisdn'=>user::get_user_msisdn($user_id), 'score'=>$score, 'entry'=>$entries,
                    'selected'=> $type == '1' ? 'TOP': 'RANDOM', 'by'=>(new login($created_by))->fullname , 'date'=>$date_created, 'notify'=>$notify
                );
            }
        } else {
            $draw_winners = array('error'=>'Enable to fetch data - no data available');
        }

        return isset($draw_winners['error']) ? $draw_winners : (is_null($print) ? array('data'=>$draw_winners) : $draw_winners);
    }

    /* reset current winners table */
    public static function reset_draw_winners($service_draw_id, $date_range, $loginID, $remote_address)
    {
        if (in_array((new login($loginID))->role, array('1', '2'))){
            /* check remote_address for security */
            $current_session = login::has_session_started($loginID, $remote_address);
            if (is_null($current_session) || $current_session === false){
                return array('error'=>'Enable to process your request. You are not authorised to perform current task');
            } else {
                /* get the target date range */
                $date_range = self::get_selected_date_range($date_range, (new service_draw($service_draw_id))->draw_type_id);
                /* check if current winners have already been notified */
                if (self::draw_winners_notified($service_draw_id, $date_range['start_date'], $date_range['end_date'])){
                    return array('error'=>'Enable to reset current winners. Current winners have already been notified.');
                }

                if (db::sql("DELETE FROM `tl_services_draw_winners` WHERE service_draw_id = '$service_draw_id'
                    AND start_date = '". $date_range['start_date']. "' AND end_date = '". $date_range['end_date']."';", DB_NAME) > 0){
                    return array('result'=>'Current winners table has been successfully reset');
                } else {
                    return array('error'=>'Enable to reset current winners. The table has no winners drawn');
                }
            }
        } else {
            return array('error'=>'Enable to process your request. You are not authorised to perform current task');
        }

    }

    private static function draw_winners_notified($service_draw_id, $start_date, $end_date)
    {
        $result = db::sql("SELECT DISTINCT notify FROM `tl_services_draw_winners` WHERE service_draw_id = '$service_draw_id'
                    AND start_date = '$start_date' AND end_date = '$end_date';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($notify) = mysqli_fetch_array($result)){
                if ($notify == '1'){
                    return true;
                }
            }
        } return false;
    }

    /* notify current winners table */
    public static function notify_draw_winners($service_draw_id, $date_range)
    {
        /* get the target date range */
        $date_range = self::get_selected_date_range($date_range, (new service_draw($service_draw_id))->draw_type_id);
        $service_draw_linked = (new service_draw($service_draw_id))->services_draw_linked;

        $result = db::sql("SELECT user_id FROM `tl_services_draw_winners` WHERE service_draw_id = '$service_draw_id'
                    AND start_date = '". $date_range['start_date']. "' AND end_date = '". $date_range['end_date']."' AND notify <> 1;", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($user_id) = mysqli_fetch_array($result)){
                /* check subscription status for current user */
                $service_local_id = 0;
                foreach($service_draw_linked as $service_id){
                    if (user::has_current_subscription((new user($user_id))->services_stats, $service_id)){
                        $service_local_id = $service_id; break;
                    }
                }

                if (is_numeric(services::prepare_datasync_notify(array(user::get_user_msisdn($user_id), thread_ctrl::get_unique_trace_id(), $service_draw_id),
                    'winners', null, null, rand(0, 1), $service_local_id))){
                    /* update current notify status */
                    db::sql("UPDATE `tl_services_draw_winners` SET notify = 1 WHERE service_draw_id = '$service_draw_id'
                    AND start_date = '". $date_range['start_date']. "' AND end_date = '". $date_range['end_date']."' AND user_id = '$user_id';", DB_NAME);
                }
            }

            return array('result'=>'Current winners table has been successfully notified. Please note that some users might have cancelled their subscription. Retry later.');
        } else {
            return array('error'=>'Enable to process your request. No winners found to notify. If users exists in current date range. Run the draw first');
        }
    }

    /* process a winner on selected date
@type: top or random selection
@category: weekly or monthly
@loginID: current loginId making the request for verification
@remote_address: the client IP making the request
@update:
    - 2017-04-17 : disable select as top winner from FrontEnd task
*/
    public static function process_draw_winner($service_draw_id, $date_range, $type, $loginID, $remote_address)
    {
        /* check if the loginID performing the request has the administrative right
        and currently login from the same remote address */
        //TODO: Change winning operation right to Administration only
        if (in_array((new login($loginID))->role, array('1', '2', '6'))){
            /* check remote_address for security */
            $current_session = login::has_session_started($loginID, $remote_address);
            if (is_null($current_session) || $current_session === false){
                return array('error'=>'Enable to process your request. You are not authorised to perform winning task');
            } else {
                return self::log_process_request($service_draw_id, $date_range, $type, $loginID);
            }
        } else {
            return array('error'=>'Enable to process your request. You are not authorised to perform winning task');
        }

    }

    /* log a service draw process request */
    private static function log_process_request($service_draw_id, $date_range, $type, $loginID)
    {
        $result = db::sql("INSERT INTO `tl_services_draw_requests` (service_draw_id, date_range, `type`, login_id)
        VALUES('$service_draw_id', '$date_range', '$type', '$loginID');", DB_NAME);
        return is_int($result) ?
            array('result'=>'Your request has been recorded successfully! Please check within 5min or more for the outcome result') :
            self::has_process_request($service_draw_id, $date_range);
    }


    public static function log_process_routine($service_draw_id, $date_range, $routine=null)
    {
        return $routine ?
            db::sql(" UPDATE `tl_services_draw_requests` SET process_start = CURRENT_TIMESTAMP WHERE service_draw_id = '$service_draw_id' AND date_range = '$date_range'", DB_NAME):
            db::sql(" UPDATE `tl_services_draw_requests` SET process_end = CURRENT_TIMESTAMP WHERE service_draw_id = '$service_draw_id' AND date_range = '$date_range'", DB_NAME);
    }
    /* update a service draw process request */
    public static function  update_process_request($service_draw_id, $date_range, $code, $detail)
    {
        db::sql(" UPDATE `tl_services_draw_requests` SET `code` = '$code', detail = '$detail' WHERE service_draw_id = '$service_draw_id' AND date_range = '$date_range'", DB_NAME);
    }

    public static function delete_process_request($service_draw_id, $date_range)
    {
        db::sql(" DELETE FROM `tl_services_draw_requests` WHERE service_draw_id = '$service_draw_id' AND date_range = '$date_range'", DB_NAME);
    }

    /* check if process request result completed and display outcome */
    private static function has_process_request($service_draw_id, $date_range)
    {
        $result = db::sql("SELECT `code`, detail FROM `tl_services_draw_requests` WHERE service_draw_id = '$service_draw_id' AND date_range = '$date_range' AND process_end IS NOT NULL;", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($code, $detail) = mysqli_fetch_array($result)){
                if ((int) $code == 0){
                    return array('error'=>$detail);
                } else {
                    return array('result'=>$detail);
                }
            }
        } return array('error'=>'The current request already exist and currently being processed! Please check later or choose a different daterange.');
    }

    /* start service draw process */
    public static function fetch_process_requests()
    {
        $result = db::sql("SELECT service_draw_id, date_range, type, login_id FROM `tl_services_draw_requests` WHERE process_start IS NULL;", DB_NAME);
        $process_requests = null;
        if (mysqli_num_rows($result)){
            while(list($service_draw_id, $date_range, $type, $login_id) = mysqli_fetch_array($result)){
                $process_requests[] = array('service_draw_id'=>$service_draw_id, 'date_range'=>$date_range, 'type'=>$type, 'login_id'=>$login_id);
            }
        } return $process_requests;
    }


    /* perform winners selection */
    public static function process_raffle_selection($service_draw_id, $date_range, $type, $loginID)
    {
        /* prepare users */
        $players = self::get_players_preview($service_draw_id, $date_range, true);

        if (isset($players['error']))
            return array('error'=>'Enable to process your request. No players found for the given date range');

        /* get the target date range */
        $date_range = self::get_selected_date_range($date_range, (new service_draw($service_draw_id))->draw_type_id);

        /* check if winners already been selected for the given date range */
        if (self::has_winners_drawn($service_draw_id, $date_range))
            return array('error'=>'Enable to process your request. Winners already been selected for the given date range. Please refresh winners table');

        $r = (new service_draw($service_draw_id))->draw_win_no;
        $winners = null;
        /* top selection operation based on the highest score */
        if ($type == 1){
            /* Sort by score using anonymous function */
            usort($players, function($x, $y){
                return ($x['score'] < $y['score']);
            });
            $winners = $players;
        } else {
            /* Sort randomly as per user entries */
            $winners = self::process_pool_entries($players, $r);
        }

        /* create winners pool as specified by the current service.
        In a current random pool, only unique user will be selected */

        for($i=0; $i < count($winners); ++$i){
            db::sql("INSERT INTO `tl_services_draw_winners` (service_draw_id, user_id, score, entries, select_type, start_date, end_date, date_created, created_by)
            VALUES('$service_draw_id', '". user::get_user_id($winners[$i]['msisdn'])."', '".$winners[$i]['score'] ."', '".$winners[$i]['entries'] ."', '$type',
            '". $date_range['start_date']."', '". $date_range['end_date']."', CURRENT_TIMESTAMP, '$loginID');", DB_NAME);
        }

        return array('result'=> count($winners). ' winner(s) been successfully selected and recorded. Review Winners table');
    }

    /* create a pool as per user number of entries */
    private static function process_pool_entries($data, $service_draw_limit)
    {
        $users_pool = array();
        for($i=0; $i < count($data); ++$i){
            /* check current user entries */
            if ($data[$i]['entries'] > 1) {
                $total_entries = $data[$i]['entries'];
                for($j=0; $j < $total_entries; ++$j){
                    $users_pool[] = $data[$i];
                }
            } else {
                $users_pool[] = $data[$i];
            }
        } shuffle($users_pool);

        /* create a unique users pool as given service draw limit */
        $unique_msisdn_set = array();
        $service_draw_lucky_pool = array();
        foreach($users_pool as $user){
            if (count($service_draw_lucky_pool) > ($service_draw_limit - 1)) break;
            if (!in_array($user['msisdn'], $unique_msisdn_set)) {
                $unique_msisdn_set[] = $user['msisdn'];
                $service_draw_lucky_pool[] = $user;
            }
        }

        return $service_draw_lucky_pool;
    }

    /* check if winners already been selected for the given date range */
    public static function has_winners_drawn($service_draw_id, $date_range)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_services_draw_winners` WHERE service_draw_id = '$service_draw_id'
               AND start_date = '". $date_range['start_date']."' AND end_date = '". $date_range['end_date']."';", DB_NAME));
    }

}