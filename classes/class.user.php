<?php
/**
 * @author: Mecxi Musa
 * object for managing users:
 * users details
 * keeps of users subscription and un-subscription, re-susbcription activity
 * keeps track users score and level activity per service
 */

class user
{
    /* initialise object fields */
    public $id;
    public $msisdn;
    public $services_stats;
    public $activity_stats;


    public function __construct($id)
    {
        $result = db::sql("SELECT msisdn, service_id, `status` FROM `tl_users`
                  INNER JOIN tl_users_added_services ON tl_users_added_services.user_id = tl_users.id WHERE tl_users.id = '$id'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($msisdn, $service_id, $status) = mysqli_fetch_array($result)){
                /* get current service start and endate */
                $this->id = $id;
                $this->msisdn =  $msisdn;
                $this->services_stats[] = array(
                    'name'=>(new services($service_id))->name, 'service_id'=>(int) $service_id, 'status'=>(int) $status,
                    'history'=>self::get_current_service_history($id, $service_id, true), 'product_id'=>self::product_service_selected($id, $service_id)
                );
            }
        }

        /* get user activity stats */
        $result = db::sql("SELECT `level`, score, last_brcast_id, service_id FROM `tl_users_stats` WHERE user_id = '$id'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($level, $score, $last_brcast_id, $service_id) = mysqli_fetch_array($result)){
                $this->activity_stats[] = array('name'=>(new services($service_id))->name, 'service_id'=>(int) $service_id, 'level'=>$level, 'score'=>$score, 'last_brcast_id'=>$last_brcast_id);
            }
        }
    }

    /* find related product_service for the current user */
    private static function product_service_selected($user_id, $service_local_id)
    {
        $result = db::sql("SELECT product_id FROM `tl_users_added_products` WHERE service_id = '$service_local_id' AND user_id = '$user_id';", DB_NAME);
        if(mysqli_num_rows($result)){
            while(list($product_id) = mysqli_fetch_array($result)){
                return $product_id;
            }
        } return null;
    }

    /* get current service history */
    public static function get_current_service_history($user_id, $service_id, $all=null)
    {
        $result = (is_null($all)) ?
            db::sql("SELECT start_date, end_date FROM `tl_users_sub_history` WHERE service_id = '$service_id' AND user_id = '$user_id' ORDER BY start_date DESC LIMIT 1;", DB_NAME) :
            db::sql("SELECT start_date, end_date FROM `tl_users_sub_history` WHERE service_id = '$service_id' AND user_id = '$user_id' ORDER BY start_date DESC;", DB_NAME);
        $services_history = null;
        if (mysqli_num_rows($result)){
            while(list($start_date, $end_date) = mysqli_fetch_array($result)){
                $services_history[] = array('start_date'=>$start_date, 'end_date'=>$end_date);
            }
        }
        return $services_history;
    }

    /* fetch active subscribers */
    public static function fetch_active_subs($service_local_id, $last_ID=null, $allocated_res=null, $today=null)
    {
        /* to temporary give everyone a chance to win, as the current system is enable to broadcast to all base
        we are allocating services based on daily odd number starting from the top or the bottom */
        $current_day = (int) date('d');
        if (($current_day % 2 == 0) && $last_ID === 0){
            $last_ID = self::get_bottom_user_id();
        }

        $users_list = null;
        $result = $today ?
            db::sql("SELECT tl_users.id, tl_users.msisdn FROM `tl_users`
            INNER JOIN tl_users_sub_history ON tl_users_sub_history.user_id = tl_users.id WHERE tl_users_sub_history.service_id = '$service_local_id' AND
            tl_users_sub_history.start_date LIKE '$today%' AND tl_users_sub_history.end_date IS NULL;", DB_NAME)
            : (
            is_null($last_ID) ?
                db::sql("SELECT id, msisdn FROM `tl_users` INNER JOIN tl_users_added_services ON tl_users_added_services.user_id = tl_users.id
            WHERE service_id = '$service_local_id' AND `status` = 1 ORDER BY tl_users.id ASC;", DB_NAME)
                :(
                    $current_day % 2 == 0 ?
                        db::sql("SELECT id, msisdn FROM `tl_users` INNER JOIN tl_users_added_services ON tl_users_added_services.user_id = tl_users.id
                    WHERE service_id = '$service_local_id' AND tl_users.id < '$last_ID' AND `status` = 1  ORDER BY tl_users.id DESC LIMIT $allocated_res;", DB_NAME) :
                            db::sql("SELECT id, msisdn FROM `tl_users` INNER JOIN tl_users_added_services ON tl_users_added_services.user_id = tl_users.id
                    WHERE service_id = '$service_local_id' AND tl_users.id > '$last_ID' AND `status` = 1  ORDER BY tl_users.id ASC LIMIT $allocated_res;", DB_NAME)
                )
            );

        if (mysqli_num_rows($result)){
            while(list($id, $msisdn) = mysqli_fetch_array($result)){
                $users_list[] = array('id'=>$id, 'msisdn'=>$msisdn);
            }
        }
        return $users_list;
    }

    /* get the bottom subscriber */
    public static function get_bottom_user_id()
    {
        $result = db::sql("SELECT id FROM `tl_users` ORDER BY id DESC LIMIT 1;", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
                return $id;
            }
        } return null;
    }


    /*  fetch skipped users during broadcast */
    public static function fetch_active_skipped_subs($service_local_id, $allocated_res)
    {
        $result = db::sql("SELECT user_id FROM `tl_servicing_skipped` WHERE service_id = '$service_local_id' LIMIT $allocated_res;", "tl_datasync");
        $users_list = null;
        if (mysqli_num_rows($result)){
            while(list($user_id)=mysqli_fetch_array($result)){
                $users_list[] = $user_id;
            }
        }
        return $users_list;
    }

    /* check if user has a current subscription for a given service */
    public static function has_current_subscription($current_user_service_stats, $service_local_id)
    {
        if (is_array($current_user_service_stats)){
            foreach($current_user_service_stats as $service_stat) {
                if ($service_stat['service_id'] == $service_local_id) {
                    return $service_stat['status'];
                }
            } return false;
        } else {
            return null;
        }
    }

    /* return service history for the given service */
    public static function service_history_related($current_user_service_stats, $service_local_id)
    {
        if (is_array($current_user_service_stats)){
            foreach($current_user_service_stats as $service_stat) {
                if ($service_stat['service_id'] == $service_local_id) {
                    return $service_stat['history'];
                }
            } return false;
        } else {
            return null;
        }
    }

    /* return the product selected for the given service */
    public static function service_product_selected($current_user_service_stats, $service_local_id)
    {
        if (is_array($current_user_service_stats)) {
            foreach ($current_user_service_stats as $service_stat) {
                if ($service_stat['service_id'] == $service_local_id) {
                    return $service_stat['product_id'];
                }
            } return false;
        } else {
            return null;
        }
    }

    /* return the service points related */
    public static function service_points_related($current_user_activity_stats, $service_local_id)
    {
        if (is_array($current_user_activity_stats)){
            foreach($current_user_activity_stats as $activity_stat) {
                if ($activity_stat['service_id'] == $service_local_id) {
                    return (int) $activity_stat['score'];
                }
            } return false;
        } else {
            return null;
        }
    }

    public static function get_user_id($msisdn)
    {
       $result = db::sql("SELECT id FROM `tl_users` WHERE msisdn = '$msisdn';", DB_NAME);
        if (mysqli_num_rows($result)) {
            while(list($id) = mysqli_fetch_array($result)) {
                return $id;
            }
        } return null;
    }

    public static function get_user_msisdn($user_id)
    {
        $result = db::sql("SELECT msisdn FROM `tl_users` WHERE id = '$user_id';", DB_NAME);
        if (mysqli_num_rows($result)) {
            while(list($msisdn) = mysqli_fetch_array($result)) {
                return $msisdn;
            }
        } return null;
    }

    /* add first-time user */
    private static function add_first_user($msisdn)
    {
        return db::sql("INSERT INTO `tl_users` (msisdn) VALUES('$msisdn');", DB_NAME);
    }


    /* subscribe user */
    private function subscribe_service($user_id, $service_local_id, $product_id)
    {
        if (is_int(db::sql("INSERT INTO `tl_users_added_services` (user_id, service_id, `status`) VALUES('$user_id', '$service_local_id', 1);", DB_NAME))){
            /* add a subscription history */
            db::sql("INSERT INTO `tl_users_sub_history` (user_id, service_id, start_date) VALUES('$user_id', '$service_local_id', CURRENT_TIMESTAMP);", DB_NAME);
            /* add a subscription stats */
            db::sql("INSERT INTO `tl_users_stats` (user_id, `level`, score, last_brcast_id, service_id) VALUES('$user_id', 1, 0, 0, '$service_local_id');", DB_NAME);
            /* insert or update current product id */
            if (db::sql("INSERT INTO `tl_users_added_products` (user_id, product_id, service_id) VALUES('$user_id', '$product_id', '$service_local_id');", DB_NAME) === false){
                db::sql("UPDATE `tl_users_added_products` SET product_id = '$product_id' WHERE user_id = '$user_id' AND service_id = '$service_local_id';", DB_NAME);
            }
            return true;
        } return null;
    }

    /* re-subscribe user */
    private function resubscribe_service($user_id, $service_local_id, $product_id)
    {
        if (db::sql("UPDATE `tl_users_added_services` SET `status` = 1 WHERE user_id = '$user_id' AND service_id = '$service_local_id';", DB_NAME)){
            /* insert or update current product id */
            if (db::sql("INSERT INTO `tl_users_added_products` (user_id, product_id, service_id) VALUES('$user_id', '$product_id', '$service_local_id');", DB_NAME) === false){
                db::sql("UPDATE `tl_users_added_products` SET product_id = '$product_id' WHERE user_id = '$user_id' AND service_id = '$service_local_id';", DB_NAME);
            }
            /* add a subscription history */
            db::sql("INSERT INTO `tl_users_sub_history` (user_id, service_id, start_date) VALUES('$user_id', '$service_local_id', CURRENT_TIMESTAMP);", DB_NAME);
            return true;
        } return null;
    }

    /* un-subscribe user */
    private function unsubscribe_service($user_id, $service_local_id)
    {
        $result = db::sql("UPDATE `tl_users_added_services` SET `status` = 0 WHERE user_id = '$user_id' AND service_id = '$service_local_id';", DB_NAME);
        if ($result > 0){
            /* add a subscription history */
            db::sql("UPDATE `tl_users_sub_history` SET end_date = CURRENT_TIMESTAMP WHERE `user_id` = '$user_id' AND `service_id` = '$service_local_id' AND `end_date` IS NULL;", DB_NAME);
            return true;
        } else if ($result == 0) {
            return false;
        } else {
            return null;
        }
    }

    /*
     * Subcription - unsubscribe / resubscribe request state
    [Code no.]
    --> 1. New user added successfully
    --> 2. User has been added without stats | stats=current level, score, service_id Please check the table 'users_stats' exists | very unlikely to happen
    --> 3. Failed creating user into the database. Please review database log './log/%today%.log' file for any related errors
    --> 4. Current user has an existing subscription
    --> 5. Warning ! User doesn't exist in the system!
    --> 6. User has been successfully un-subscribed
    --> 7. The system fails to unsubscribe the user | database system is down
    --> 8. User has been re-subscribed successfully!
    --> 9. An error occurred re-subscribing current user
    --> 10. User has no running subscription | on un-subscribe request
    --> 11. SDP fails to process user request
     */

    /* process | rolling new subscription or re-subscribe request : user, stat, history */
    public static function start_unroll_process($msisdn, $service_local_id, $product_id)
    {
        /* check existing record */
        $user_id = user::get_user_id($msisdn);
        if ($user_id){
            $current_user = new user($user_id);
            $current_service = new services($service_local_id);
            /* re-subscribe request */
            $requested_service_status = self::has_current_subscription($current_user->services_stats, $service_local_id);
            if ($requested_service_status){
                return array('result'=>false, 'message'=>"Re-subscribe request | Current $msisdn has an existing subscription to ". $current_service->name.' - code 4', 4);
            } else {

                if ($requested_service_status === 0){
                    /* In a successful re-subscription request, to avoid cheating, we check if the user has already received
                    today related contents service in automated queueing broadcast
                   if yes, result=>false (no broadcast after a re-subscription request will be initiated in the ./gateway),
                   if no, result=>true ( a queueing broadcast for today trivia will be initiated after this request) */
                    $start_thread = (thread_ctrl::has_queued($current_user->id, $service_local_id)) ? false : true;

                    /* perform a re-subscription */
                    if ($current_user->resubscribe_service($current_user->id, $service_local_id, $product_id)){
                        return array('result'=>$start_thread, 'message'=>"Re-subscribe request | $msisdn has been re-subscribed successfully to ". $current_service->name ." - code 8", 8);
                    } else {
                        return array('result'=>$start_thread, 'message'=>"Re-susbcribe request | Error re-subscribing current $msisdn ". $current_service->name ." - code 9", 9);
                    }
                } else {
                    /* subscribe user to a new service */
                    if ($current_user->subscribe_service($user_id, $service_local_id, $product_id)){
                        return array('result'=>true, 'message'=>"Subscription request | $msisdn has been subscribed successfully to ". $current_service->name . ' - code 1', 1);
                    } else {
                        return array('result'=>false, 'message'=>"Subscription request | Request failed for $msisdn, service name: $current_service->name. A system error has occurred. Please check database log - code 3", 3);
                    }
                }
            }
        } else {
            /* first-time request */
            $user_id = self::add_first_user($msisdn);
            if ($user_id){
                return self::start_unroll_process($msisdn, $service_local_id, $product_id);
            } else {
                return array('result'=>false, 'message'=>"Subscription request | Request failed for $msisdn, service name: ".(new services($service_local_id))->name." A system error has occurred. Please check database log - code 3", 3);
            }
        }
    }

    /* process | derolling / un-subscribed current user */
    public static function start_derolling_process($msisdn, $service_local_id, $product_id)
    {
        $user_id = self::get_user_id($msisdn);
        if ($user_id){
            $current_user = new user($user_id);
            $current_service = new services($service_local_id);
            /* un-subscribe user */
            $service_request = $current_user->unsubscribe_service($current_user->id, $service_local_id);
            if ($service_request === true){
                /* reset the current user service entry allocated */
                services::reset_allocated_entries($user_id, $service_local_id);
                return  array('result'=>6, 'message'=>"Unsubscribed request | $msisdn has been un-subscribed successfully to ". $current_service->name .". - code 6");
            } else if ($service_request === false) {
               return array('result'=>10, 'message'=>"Unsubscribed request | $msisdn has no running subscription for ". $current_service->name ." - code 10");
            } else {
                return array('result'=>7, 'message'=>"Unsubscribed request | Failed to un-subscribe  $msisdn | service : ". $current_service->name .". Services are currently down. Please try again later - code 7");
            }
        } else {
            /* user not found in the system
            to avoid a torrent of sdp requests, subscribe the user and un-subscribe him
            */
            self::start_unroll_process($msisdn, $service_local_id, $product_id);
            return self::start_derolling_process($msisdn, $service_local_id, $product_id);
        }
    }

    /* check if user is whitelisted */
    public static function is_whitelisted($user_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `whitelist` WHERE `user_id` = '$user_id'", DB_NAME));
    }

    /****************** Web API interface ********************/

    /* fetch totals or the sum subscribers, unsubscribed, membership */
    public static function fetch_sum_subscribers($type)
    {
        $process_return = array('error'=>'Wrong type. Please specific a param type: sum | new | unsub | member ');
        switch($type){
            case 'sum':
                $process_return = array('result'=>self::sum_totals_subs());
                break;
            case 'new':
                $process_return = array('result'=> self::sum_new_today_subs());
                break;
            case 'unsub':
                $process_return = array('result'=> self::sum_total_unsubs());
                break;
            case 'member':
                $process_return = array('result'=> self::sum_totals_membership());
                break;
        }

        return $process_return;
    }


    /* fetch last subscribers - unsubscribed - members on request */
    public static function fetch_last_subscribers($type)
    {
        $process_return = array('error'=>'Wrong type. Please specific a param type: active | inactive | new | member ');
        switch($type){
            case 'new':
                $process_return = array('data'=>self::last_new_today_subs());
                break;
            case 'inactive':
                $process_return = array('data'=>self::last_total_unsubs_all());
                break;
            case 'inactive_all':
                $process_return = array('data'=>self::last_total_unsubs_all_no_services());
                break;
            case 'active':
                $process_return = array('data'=>self::last_total_subs_all());
                break;
            case 'active_all':
                $process_return = array('data'=>self::last_total_subs_all_with_services());
                break;
            case 'members':
                $process_return = array('data'=>self::last_registered_members());
                break;
        }
        return $process_return;
    }

    /* @return sum totals not including whitelist */
    public static function sum_totals_subs($service_id=null, $target_day=null, $start_date=null)
    {
        /* get the start date */
        $service_list = thread_ctrl::all_service_status($service_id);
        $date = date_format(date_create($service_list[0][1]), 'Y-m-d');

        if (is_null($target_day)){
            return is_null($service_id) ?
                mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE end_date IS NULL", DB_NAME)):
                (   is_null($start_date) ?
                    mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE end_date IS NULL AND service_id = '$service_id'", DB_NAME)):
                    mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE end_date IS NULL AND service_id = '$service_id' AND start_date > '$date%'", DB_NAME))
                );
        } else {
            $target_day = date('Y-m-d', strtotime("$target_day +1 days"));
            return is_null($service_id) ?
                mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE end_date IS NULL AND start_date < '$target_day%'", DB_NAME)):
                mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE service_id = '$service_id' AND end_date IS NULL AND start_date < '$target_day%'", DB_NAME));

        }
    }

    /* return the number of active users */
    public static function active_user_base($service_id)
    {
        return mysqli_num_rows(db::sql("SELECT user_id FROM `tl_users_added_services` WHERE service_id = '$service_id' AND `status` = 1;", DB_NAME)) ;
    }

    /* only new subscribers today
    * @service_id: current target service
     * @$today: subscribers entered today
     * @has_free_period: return only those with free period
    */
    public static function sum_new_today_subs($service_id=null, $target_day=null, $has_free_period=null, $has_report=null)
    {
        $date = date('Y-m-d');
        if (is_null($target_day)){
            return is_null($service_id) ?
                mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$date%' AND end_date IS NULL", DB_NAME)):
                mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$date%' AND end_date IS NULL AND service_id = '$service_id'", DB_NAME));
        } else {
            /* check has free period is requested */
            if ($has_free_period){
                $result = is_null($service_id) ?
                    db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$target_day%' AND end_date IS NULL", DB_NAME):
                    db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$target_day%' AND end_date IS NULL AND service_id = '$service_id'", DB_NAME);
                $new_subs_free = 0;
                if (mysqli_num_rows($result)){
                    while(list($user_id) = mysqli_fetch_array($result)){
                        $new_subs_free += (self::has_free_period($user_id, $service_id, $target_day) === true) ? 1 : 0;
                    }
                }
                return $new_subs_free;
            } else {
                if ($has_report){
                    return is_null($service_id) ?
                        mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$target_day%'", DB_NAME)):
                        mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$target_day%' AND service_id = '$service_id'", DB_NAME));
                } else {
                    return is_null($service_id) ?
                        mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$target_day%' AND end_date IS NULL", DB_NAME)):
                        mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM `tl_users_sub_history` WHERE start_date LIKE '$target_day%' AND end_date IS NULL AND service_id = '$service_id'", DB_NAME));
                }
            }
        }

    }

    /* check new user has free period */
    private static function has_free_period($userid, $service_id, $today)
    {
        return (mysqli_num_rows(db::sql("SELECT * FROM `tl_sync_services_requests` WHERE msisdn = '".user::get_user_msisdn($userid) ."' AND req_service = '$service_id' AND gracePeriod = 1 AND date_created = '$today' ;", "tl_gateway")) > 0) ? true : false;
    }

    /* check if the user has been billed before */
    private static function has_been_billed($userid, $service_id)
    {
        return (mysqli_num_rows(db::sql("SELECT * FROM `tl_sync_services_requests` WHERE msisdn = '".user::get_user_msisdn($userid) ."' AND req_service = '$service_id' AND resp_type = 3", "tl_gateway")) > 0 ) ? true : false;
    }

    /* totals of unsubscribed for all services or a particular service
     * @today : true to return only today unsubs numbers for a particular service
     * @has_free_period : check current unsubscriber has still have free period or being billed to remove him as part of the daily target
    */
    public static function sum_total_unsubs($service_id=null, $target_day=null, $has_free_period=null)
    {
        /* get the start date */
        $service_list = thread_ctrl::all_service_status($service_id);
        $start_date = date_format(date_create($service_list[0][1]), 'Y-m-d');

        $result = null;
        if (is_null($target_day)){
            $result = is_null($service_id) ?
                db::sql("SELECT DISTINCT tl_users.msisdn FROM `tl_users_sub_history` INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                                WHERE tl_users_sub_history.end_date IS NOT NULL AND tl_users_sub_history.start_date > '$start_date%'", DB_NAME):
                db::sql("SELECT DISTINCT tl_users.msisdn FROM `tl_users_sub_history` INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                                WHERE tl_users_sub_history.end_date IS NOT NULL AND tl_users_sub_history.service_id = '$service_id' AND tl_users_sub_history.start_date > '$start_date%'", DB_NAME);
        } else {
            $result = is_null($service_id) ?
                db::sql("SELECT DISTINCT tl_users.msisdn FROM `tl_users_sub_history` INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                                WHERE tl_users_sub_history.end_date LIKE '$target_day%'", DB_NAME):
                db::sql("SELECT DISTINCT tl_users.msisdn FROM `tl_users_sub_history` INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                                WHERE tl_users_sub_history.service_id = '$service_id' AND tl_users_sub_history.end_date LIKE '$target_day%'", DB_NAME);
        }

        $return_result = 0;
        if (mysqli_num_rows($result)){
            if (!is_null($service_id)){
                if (is_null($has_free_period) || (new services($service_id))->free_period == 0){
                    $return_result = mysqli_num_rows($result);
                } else {
                    while(list($msisdn) = mysqli_fetch_array($result)){
                        /* check if current msisdn still has a free period */
                        if (self::has_free_period(user::get_user_id($msisdn), $service_id, $target_day) > 0){
                            ++$return_result;
                        } else {
                            /* check if the user has been billed before */
                            $return_result += (self::has_been_billed(user::get_user_id($msisdn), $service_id) == false) ? 1 : 0;
                        }
                    }
                }
            } else {
                $return_result = mysqli_num_rows($result);
            }
        }
        return $return_result;
    }

    /* check totals membership */
    private static function sum_totals_membership()
    {
        $services = services::services_list();
        $result = db::sql("SELECT `phone_no` FROM `tl_sys_login`", DB_NAME);
        $return_result = 0;
        if (mysqli_num_rows($result)){
            while(list($msisdn)=mysqli_fetch_array($result)){
                $watch = 0;
                foreach($services as $service){
                    if (self::has_current_subscription((new user(user::get_user_id($msisdn)))->services_stats, $service['id'])){
                        ++$watch;
                    }
                }
                if ($watch > 0){
                    ++$return_result;
                }
            }
        }
        return $return_result;
    }

    /* @return last 100 new subs for all services */
    public static function last_new_today_subs()
    {
        $date = date('Y-m-d');
        $return_data = null;
        $result = db::sql("SELECT tl_users.msisdn, tl_users_sub_history.start_date, tl_services.`name` FROM `tl_users_sub_history`
                  INNER JOIN tl_services ON tl_services.service_local_id = tl_users_sub_history.service_id INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                  WHERE tl_users_sub_history.start_date LIKE '$date%' AND tl_users_sub_history.end_date IS NULL ORDER BY tl_users_sub_history.start_date DESC LIMIT 100", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($msisdn, $start_date, $name)=mysqli_fetch_array($result)){
                $return_data[] = array('msisdn'=>$msisdn, 'service_name'=>$name, 'date'=>date_format(date_create($start_date), 'Y-m-d H:i'));
            }
        }
        return $return_data;
    }

    /* @return last 100 subscribers across all services */
    public static function last_total_subs_all()
    {
        $return_data = null;
        $result = db::sql("SELECT tl_users.msisdn, tl_users_sub_history.start_date, tl_services.`name` FROM `tl_users_sub_history`
                  INNER JOIN tl_services ON tl_services.service_local_id = tl_users_sub_history.service_id INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                  WHERE tl_users_sub_history.end_date IS NULL ORDER BY tl_users_sub_history.start_date DESC LIMIT 100", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($msisdn, $start_date, $name)=mysqli_fetch_array($result)){
                $return_data[] = array('msisdn'=>$msisdn, 'service_name'=>$name, 'date'=>date_format(date_create($start_date), 'Y-m-d H:i'));
            }
        }

        return $return_data;
    }

    /* @return last 100 subscribers across all services */
    public static function last_total_subs_all_with_services()
    {
        $return_data = null;
        $services = services::services_list();
        $result = db::sql("SELECT tl_users.msisdn, tl_users_sub_history.start_date, tl_services.`name` FROM `tl_users_sub_history`
                  INNER JOIN tl_services ON tl_services.service_local_id = tl_users_sub_history.service_id INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                  WHERE tl_users_sub_history.end_date IS NULL ORDER BY tl_users_sub_history.start_date DESC", DB_NAME);
        $limit = 0;
        if (mysqli_num_rows($result)){
            while(list($msisdn, $start_date, $name)=mysqli_fetch_array($result)){
                /* check if current user has related services */
                $has_services = false;
                foreach($services as $service){
                    if (self::has_current_subscription((new user(user::get_user_id($msisdn)))->services_stats, $service['id'])){
                        $has_services = true;
                        break;
                    }
                }
                if ($has_services){
                    /* don't add duplicate msisdn */
                    $added = false;
                    if (is_array($return_data)){
                        foreach($return_data as $users){
                            if (in_array($msisdn, $users)){
                                $added = true;
                            }
                        }
                    }
                    if (!$added){
                        if ($limit <= 100){
                            $return_data[] = array('msisdn'=>$msisdn, 'service_name'=>$name, 'date'=>date_format(date_create($start_date), 'Y-m-d H:i'));
                        } else {
                            break;
                        }
                        ++$limit;
                    }
                }
            }
        }

        return $return_data;
    }

    /* @return last 100 un-subscribers across all services */
    public static function last_total_unsubs_all()
    {
        $return_data = null;
        $result = db::sql("SELECT tl_users.msisdn, tl_users_sub_history.start_date, tl_services.`name`, tl_users_sub_history.end_date, tl_users_sub_history.service_id FROM `tl_users_sub_history`
                  INNER JOIN tl_services ON tl_services.service_local_id = tl_users_sub_history.service_id INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                  WHERE tl_users_sub_history.end_date IS NOT NULL ORDER BY tl_users_sub_history.start_date DESC LIMIT 100", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($msisdn, $start_date, $name, $end_date, $service_id)=mysqli_fetch_array($result)){
                /* check if current user has not resubscribed */
                if (!self::has_current_subscription($msisdn, $service_id)){
                    $return_data[] = array('msisdn'=>$msisdn, 'service_name'=>$name, 'date'=>date_format(date_create($start_date), 'Y-m-d H:i'), 'end_date'=>date_format(date_create($end_date), 'Y-m-d H:i') );
                }
            }
        }

        return $return_data;
    }

    /* @return last 100 un-subscribers across all services */
    public static function last_total_unsubs_all_no_services()
    {
        $return_data = null;
        $services = services::services_list();
        $result = db::sql("SELECT tl_users.msisdn, tl_users_sub_history.start_date, tl_services.`name`, tl_users_sub_history.end_date FROM `tl_users_sub_history`
                  INNER JOIN tl_services ON tl_services.service_local_id = tl_users_sub_history.service_id INNER JOIN tl_users ON tl_users.id = tl_users_sub_history.user_id
                  WHERE tl_users_sub_history.end_date IS NOT NULL ORDER BY tl_users_sub_history.start_date DESC", DB_NAME);
        $limit = 0;
        if (mysqli_num_rows($result)){
            while(list($msisdn, $start_date, $name, $end_date)=mysqli_fetch_array($result)){
                /* check if current user has not related services */
                $no_services = true;
                foreach($services as $service){
                    if (self::has_current_subscription((new user(user::get_user_id($msisdn)))->services_stats, $service['id'])){
                        $no_services = false;
                    }
                }
                if ($no_services){
                    /* don't add duplicate msisdn */
                    $added = false;
                    if (is_array($return_data)){
                        foreach($return_data as $users){
                            if (in_array($msisdn, $users)){
                                $added = true;
                            }
                        }
                    }
                    if (!$added){
                        if ($limit <= 100){
                            $return_data[] = array('msisdn'=>$msisdn, 'service_name'=>$name, 'date'=>date_format(date_create($start_date), 'Y-m-d H:i'), 'end_date'=>date_format(date_create($end_date), 'Y-m-d H:i') );
                        } else {
                            break;
                        }
                        ++$limit;
                    }
                }
            }
        }

        return $return_data;
    }

    /* @return last 200 registered members */
    public static function last_registered_members()
    {
        $services = services::services_list();
        $result = db::sql("SELECT `id`, CONCAT(`name`,' ',`surname`) as fullname, email, phone_no, date_created FROM `tl_sys_login`", DB_NAME);
        $return_data = null;
        $limit = 0;
        if (mysqli_num_rows($result)){
            while(list($login_id, $fullname, $email, $msisdn, $date_created)=mysqli_fetch_array($result)){

                $watch = 0;
                foreach($services as $service){
                    if (self::has_current_subscription((new user(user::get_user_id($msisdn)))->services_stats, $service['id'])){
                        ++$watch;
                    }
                }
                if ($watch > 0){
                    $return_data[] = array('fullname'=>$fullname, 'email'=>$email, 'phone'=>'0'. substr($msisdn, 2),
                        'registered'=>$date_created, 'last_login'=>login::last_session_logged($login_id));
                }

                if ($limit > 200){
                    break;
                } else {
                    ++$limit;
                }
            }
        }
        return $return_data;
    }


    /****** Web API - profile ********/

    /* return current user profile services or stats */
    public static function user_profile_stats($msisdn, $services_only=null)
    {
        $msisdn = DIAL_CODE. substr($msisdn, 1);
        /* check all services stats */
        $result = array('services'=>null, 'last_login'=>null);
        $services = services::services_list();
        $current_user = new user(user::get_user_id($msisdn));
        if ($services){
            foreach($services as $service){
                $current_service = null;
                if (user::has_current_subscription($current_user->services_stats, $service['id']) === 1){
                    $current_service = array(
                        'id'=>$service['id'], 'name'=>$service['name'], 'status'=>'Subscribed',
                        'points'=> (int) user::totals_points($service['id'], $current_user->id),
                        'today_points'=> (int) user::today_points($service['id'], $current_user->id),
                        'last_played' => user::get_last_play_date($service['id'], $current_user->id)
                    );
                } else if (user::has_current_subscription((new user(user::get_user_id($msisdn)))->services_stats, $service['id']) === 0){
                    $current_service = array(
                        'id'=>$service['id'], 'name'=>$service['name'], 'status'=>'Deactivated',
                        'points'=> (int) user::totals_points($service['id'], $current_user->id),
                        'today_points'=> (int) user::today_points($service['id'], $current_user->id),
                        'last_played' => user::get_last_play_date($service['id'], $current_user->id)
                    );
                }

                if ($current_service){
                    $result['services'][] = $current_service;
                }
            }
        }
        /* profile activity stats */
        if (is_null($services_only)){
            /* get last logged */
            $last_login = thread_ctrl::get_time_ago(strtotime(login::last_session_logged(login::get_login_id($msisdn))));
            $result['last_login'] = ($last_login == '47 years ago') ? 'none': $last_login;
        }

        return array('result'=>$result);
    }

    /* query current user services subscription historic */
    public static function user_services_subscription_historic($msisdn)
    {
        $msisdn = DIAL_CODE. substr($msisdn, 1);
        $result = array();
        /* check if user exist in the system */
        if (user::get_user_id($msisdn)){
            $result['data'] = array();
            $current_user = new user(user::get_user_id($msisdn));
            /* check all services status */
            $services = services::services_list();
            foreach($services as $service){
                /* check current user service status */
                $current_service = null;
                if (user::has_current_subscription($current_user->services_stats, $service['id']) === 1){

                    $service_histories_list = user::service_history_related($current_user->services_stats, $service['id']);
                    $current_history = (is_array($service_histories_list)) ? $service_histories_list[0] : null;
                    $current_service = array(
                        'id'=>$service['id'], 'name'=>$service['name'], 'status'=>'Subscribed',
                        'points'=> (int) user::totals_points($service['id'], $current_user->id),
                        'today_points'=> (int) user::today_points($service['id'], $current_user->id),
                        'last_played' => user::get_last_play_date($service['id'], $current_user->id),
                        'date'=>is_null($current_history) ? null : $current_history['start_date']
                    );

                } else if (user::has_current_subscription((new user(user::get_user_id($msisdn)))->services_stats, $service['id']) === 0){

                    $service_histories_list = user::service_history_related($current_user->services_stats, $service['id']);
                    $current_history = (is_array($service_histories_list)) ? $service_histories_list[0] : null;
                    $current_service = array(
                        'id'=>$service['id'], 'name'=>$service['name'], 'status'=>'Deactivated',
                        'points'=> (int) user::totals_points($service['id'], $current_user->id),
                        'today_points'=> (int) user::today_points($service['id'], $current_user->id),
                        'last_played' => user::get_last_play_date($service['id'], $current_user->id),
                        'date'=>is_null($current_history) ? null : $current_history['end_date']
                    );
                }
                if ($current_service){
                    $result['data'][] = $current_service;
                }
            }
        } else {
            /* user not found on the system */
            $result['error'] = $msisdn. ' could not be found on the system. Please review the query';
        }
        return $result;
    }

    /* perform a service request related on user behalf to SDP */
    public static function request_service_related($msisdn, $request, $service_id)
    {
        /* initialise variables */
        $result = array();
        $msisdn = DIAL_CODE. substr($msisdn, 1);
        $collerator_id = thread_ctrl::get_unique_trace_id();
        $request_type = ($request == 'activation') ? 'subscribe':'unsubscribe';
        $current_service = new services($service_id);
        $serviceID = $current_service->service_sdp_id;
        $accesscode = $current_service->accesscode;
        $spID = $current_service->sp_id;

        /* check if it's a correct request word */
        if (in_array($request, array('activation', 'cancellation'))){
            $notifySmsReceptionRequest = '<?xml version="1.0" encoding="utf-8" ?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <soapenv:Header>
                        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
                            <ns1:spId>'.$spID.'</ns1:spId>
                            <ns1:serviceId>'.$serviceID.'</ns1:serviceId>
                            <ns1:timeStamp>20161110123710</ns1:timeStamp>
                            <ns1:linkid>10133710077204189825</ns1:linkid>
                            <ns1:traceUniqueID>404090105241611101237094336003</ns1:traceUniqueID>
                            <ns1:OperatorID>2701</ns1:OperatorID>
                        </ns1:NotifySOAPHeader>
                    </soapenv:Header>
                    <soapenv:Body>
                        <ns2:notifySmsReception xmlns:ns2="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
                            <ns2:correlator>'.$collerator_id.'</ns2:correlator>
                            <ns2:message><message>'.$request_type.'</message>
                                <senderAddress>tel:'.$msisdn.'</senderAddress>
                                <smsServiceActivationNumber>tel:'.$accesscode.'</smsServiceActivationNumber>
                                <dateTime>2016-11-10T12:37:10Z</dateTime>
                            </ns2:message>
                            <ns2:interface>portal</ns2:interface>
                        </ns2:notifySmsReception>
                    </soapenv:Body></soapenv:Envelope>';
            /* Send request internally */
            $result['result'] = gateway::curlHTTPRequestXML('http://localhost'.DEFAULT_PORT.'/gateway/sms/', $notifySmsReceptionRequest);

        } else {
            $result['error'] = 'Incorrect request word. Please specify your request parameter either activation or cancellation';
        }

        return $result;
    }


    /* get users total points */
    public static function totals_points($service_local_id, $user_id)
    {
        $result = db::sql("SELECT score FROM `tl_users_stats` WHERE user_id = '$user_id' AND service_id = '$service_local_id'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($total_score)=mysqli_fetch_array($result)){
                return $total_score;
            }
        }
        return 0;
    }

    /* get user today current points */
    public static function today_points($service_local_id, $user_id)
    {
        $result =  db::sql("SELECT score, trivia_id FROM `tl_servicing_trivia` WHERE user_id = '$user_id' AND service_id = '$service_local_id' AND user_answer IS NOT NULL;", "tl_datasync");
        $points = 0;
        if (mysqli_num_rows($result)){
            while(list($score, $trivia_id) = mysqli_fetch_array($result)){
                /* get score for the given trivia_id*/
                /* get score for the given trivia_id*/
                $q = services::fetch_content($trivia_id, new services($service_local_id));
                $points += ($score == 'correct') ? $q[1] : 0;
            }
        }
        return $points;
    }

    /* get last played log */
    private static function get_last_play_date($service_local_id, $user_id)
    {
        $result = ((new services($service_local_id))->service_type == 1) ?
            db::sql("SELECT process_end FROM `tl_servicing_contents` WHERE user_id = '$user_id' AND service_id = '$service_local_id' ORDER BY process_end DESC LIMIT 1;", "tl_datasync"):
            db::sql("SELECT process_end FROM `tl_datasync_trivia` WHERE user_id = '$user_id' AND service_id = '$service_local_id' ORDER BY process_end DESC LIMIT 1;", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($last_play_date) = mysqli_fetch_array($result)){
                $last_play = thread_ctrl::get_time_ago(strtotime($last_play_date));
                return ($last_play == '47 years ago') ? 'none' : $last_play;
            }
        }
        return 'none';
    }

    /* post profile last activities */
    public static function post_profile_activities($msisdn)
    {
        /* check users enable services */
        $services = self::user_profile_stats($msisdn, true);
        $msisdn = DIAL_CODE. substr($msisdn, 1);
        $process_result = array();
        foreach($services['result'] as $key => $value){
            switch($key){
                case 'service_1':
                    if ($value == 'Subscribed'){
                        /* get user activity */
                        $process_result['service_1'] = service_level_1::user_current_activity(user::get_user_id($msisdn));
                    }
                    break;
                case 'service_2':
                    if ($value == 'Subscribed'){
                        /* get user activity */
                        $process_result['service_2'] = service_level_2::user_current_activity(user::get_user_id($msisdn));
                    }
                    break;
                case 'service_3':
                    /* get user activity */
                    if ($value == 'Subscribed'){
                        $process_result['service_3'] = service_level_3::user_current_activity(user::get_user_id($msisdn));
                    }
                    break;
            }
        }
         return $process_result;
    }

    /* return current profile 10 recent activities */
    public static function profile_activities($msisdn)
    {
        $msisdn = DIAL_CODE. substr($msisdn, 1);;
        /* check services activities */
        $result = null;
        $services = services::services_list();
        if ($services){
            foreach($services as $service){
                $result[] = array(
                    'id'=> $service['id'],
                    'name'=> $service['name'],
                    'type'=> (new services($service['id']))->service_type,
                    'posts'=> services::services_activities($service['id'], user::get_user_id($msisdn))
                );
            }
        }
        return (is_null($result)) ? array('error'=>'No users services activity found') : array('data'=>$result);
    }


    /* reset users_stats draw entries */
    public static function reset_draw_stats_entries()
    {
        db::sql("DELETE FROM `tl_users_stats_draw`;", DB_NAME);
    }

}