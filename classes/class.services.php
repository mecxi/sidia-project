<?php
/**
 * @author: Mecxi Musa
 * generals services queries and operations
 */

class services
{
    /* initialise service object properties */
    public $service_local_id;
    public $name;
    public $description;
    public $service_type;
    public $broadcast;
    public $broadcast_type;
    public $broadcast_length;
    public $cross_sell_services;
    public $cross_sell_on_sub;
    public $cross_sell_on_first;
    public $opening_message;
    public $set_thread;
    public $free_period;
    public $service_sdp_id;
    public $accesscode;
    public $sp_id;
    public $sp_password;
    public $last_process_id;
    public $total_threads_allowed;
    public $keywords;
    public $products;
    public $date_created;
    public $keywords_length;
    public $start_date;
    public $end_date;
    public $service_type_name;
    public $active;
    public $play_rate;


    private static $re_try = 0;
    private static $sync_result = null;

    public function __construct($service_local_id)
    {
        $result = db::sql("SELECT `name`, description, service_type, broadcast, set_thread, free_period, service_sdp_id, accesscode, sp_id, last_process_id, sp_password,
                  broadcast_type, total_threads, broadcast_length, cross_sell_services, cross_sell_on_sub, opening_message, date_created, keywords_length, start_date, end_date, `type`, active, cross_sell_on_first, play_rate FROM `tl_services`
                  INNER JOIN tl_services_type ON tl_services_type.id = tl_services.service_type WHERE service_local_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($name, $description, $service_type, $broadcast, $set_thread, $free_period, $service_sdp_id, $accesscode, $sp_id, $last_process_id, $sp_password,
                $broadcast_type, $total_threads, $broadcast_length, $cross_sell_services, $cross_sell_on_sub, $opening_message, $date_created,
                $keywords_length, $start_date, $end_date, $service_type_name, $active, $cross_sell_on_first, $play_rate) = mysqli_fetch_array($result)){
                $this->service_local_id = $service_local_id;
                $this->name = $name;
                $this->description = $description;
                $this->service_type = (int) $service_type;
                $this->broadcast = (int) $broadcast;
                $this->set_thread = (int) $set_thread;
                $this->free_period = (int) $free_period;
                $this->service_sdp_id = $service_sdp_id;
                $this->accesscode = $accesscode;
                $this->sp_id = $sp_id;
                $this->last_process_id = (int) $last_process_id;
                $this->sp_password = $sp_password;
                $this->broadcast_type = (int) $broadcast_type;
                $this->total_threads_allowed = (int) $total_threads;
                $this->broadcast_length = (int) $broadcast_length;
                $this->cross_sell_services = (int) $cross_sell_services;
                $this->cross_sell_on_sub = (int) $cross_sell_on_sub;
                $this->cross_sell_on_first = (int) $cross_sell_on_first;
                $this->opening_message = (int) $opening_message;
                $this->date_created = $date_created;
                $this->keywords_length = (int) $keywords_length;
                $this->start_date = date_format(date_create($start_date), 'Y-m-d');
                $this->end_date = date_format(date_create($end_date), 'Y-m-d');
                $this->service_type_name = $service_type_name;
                $this->active = $active;
                $this->play_rate = $play_rate;
            }
        }

        /* add current service keywords related */
        $result = db::sql("SELECT keyword FROM `tl_services_keywords` INNER JOIN tl_keyword ON tl_services_keywords.keyword_id = tl_keyword.id WHERE service_local_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($keywords) = mysqli_fetch_array($result)){
                $this->keywords[] = $keywords;
            }
        }
        /* add related product_ids associated */
        $result = db::sql("SELECT tl_products.id, product_sdp_id, billing_rate, billing_cycle_id FROM `tl_products`
                  INNER JOIN tl_services_products ON tl_services_products.product_id = tl_products.id WHERE service_local_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($product_id, $product_sdp_id, $billing_rate, $billing_cycle_id) = mysqli_fetch_array($result)){
                $this->products[] = array('product_id'=>$product_id, 'product_sdp_id'=>$product_sdp_id, 'rate'=>$billing_rate, 'cycle'=>$billing_cycle_id);
            }
        }
    }

    /* return services for the target type */
    public static function services_list($service_type=null)
    {
        $result = (is_null($service_type) || $service_type == 'all') ?
            db::sql("SELECT service_local_id, `name`, service_type, broadcast_length FROM `tl_services`", DB_NAME):
            db::sql("SELECT service_local_id, `name`, service_type, broadcast_length FROM `tl_services` WHERE service_type = '$service_type'", DB_NAME);
        $service_list = null;
        if (mysqli_num_rows($result)){
            while(list($service_id, $name, $service_type, $broadcast_length)=mysqli_fetch_array($result)){
                $service_list[] = array('id'=>$service_id, 'name'=>$name, 'type'=>$service_type, 'length'=>$broadcast_length);
            }
        } return $service_list;
    }

    /* return draw engines service list */
    public static function services_draw_engine_list()
    {
        $result = db::sql("SELECT id, `name`, draw_type_id FROM `tl_services_draw_engine`;", DB_NAME);
        $service_draw_list = null;
        if (mysqli_num_rows($result)){
            while(list($id, $name, $draw_type_id) = mysqli_fetch_array($result)){
                $service_draw_list[] = array('id'=>$id, 'name'=>$name, 'draw_type_id'=> $draw_type_id);
            }
        } return $service_draw_list;
    }

    /* return service type list */
    public static function service_type_list()
    {
        $result = db::sql("SELECT id, type FROM `tl_services_type`;", DB_NAME);
        $service_type_list = null;
        if (mysqli_num_rows($result)){
            while(list($id, $type)=mysqli_fetch_array($result)){
                $service_type_list[] = array('id'=>$id, 'type'=>$type);
            }
        } return $service_type_list;
    }

    /* return draw type list */
    public static function draw_type_list()
    {
        $result = db::sql("SELECT id, type FROM `tl_draw_type`;", DB_NAME);
        $service_draw_list = null;
        if (mysqli_num_rows($result)){
            while(list($id, $type)=mysqli_fetch_array($result)){
                $service_draw_list[] = array('id'=>$id, 'type'=>$type);
            }
        } return $service_draw_list;
    }
    /* return service type list */
    public static function service_cross_list()
    {
        $result = db::sql("SELECT service_local_id, `name` FROM `tl_services` WHERE service_type = 1;", DB_NAME);
        $service_cross_list = null;
        if (mysqli_num_rows($result)){
            while(list($id, $name)=mysqli_fetch_array($result)){
                $service_cross_list[] = array('id'=>$id, 'name'=>$name);
            }
        } return $service_cross_list;
    }

    /* return service broadcast type list */
    public static function service_broadcast_type_list()
    {
        $result = db::sql("SELECT id, type FROM `tl_broadcast_type`;", DB_NAME);
        $service_br_type_list = null;
        if (mysqli_num_rows($result)){
            while(list($id, $type)=mysqli_fetch_array($result)){
                $service_br_type_list[] = array('id'=>$id, 'type'=>$type);
            }
        } return $service_br_type_list;
    }

    /* return the billing_type for the given service_id and sdp_product_id */
    public static function service_billing_cycle_sdp_product_related($service_local_id, $sdp_product_id=null, $product_id=null)
    {
        $result = is_null($product_id) ? db::sql("SELECT billing_rate, type FROM `tl_services_products`
                  INNER JOIN tl_products ON tl_products.id = tl_services_products.product_id
                  INNER JOIN tl_billing_type ON tl_billing_type.id = tl_products.billing_cycle_id
                  WHERE tl_services_products.service_local_id = '$service_local_id' AND tl_products.product_sdp_id = '$sdp_product_id';", DB_NAME):
            db::sql("SELECT billing_rate, type FROM `tl_services_products`
                  INNER JOIN tl_products ON tl_products.id = tl_services_products.product_id
                  INNER JOIN tl_billing_type ON tl_billing_type.id = tl_products.billing_cycle_id
                  WHERE tl_services_products.service_local_id = '$service_local_id' AND tl_products.id = '$product_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($rate, $type) = mysqli_fetch_array($result)){
                return array('rate'=>(double) $rate, 'type'=>$type);
            }
        } return 0;
    }


    /* return service data list */
    public static function service_data_basic($service_local_id)
    {
        /* instantiate current service */
        $current_service = new services($service_local_id);
        return array(
            'name'=>$current_service->name,
            'desc'=>$current_service->description,
            'keywords'=>$current_service->keywords,
            'type_id'=>$current_service->service_type,
            'type_name'=>$current_service->service_type_name,
            'cross_sell_list'=> ($current_service->service_type == 1) ? null : service_messages::get_cross_sell_services($service_local_id, true),
            'promo_date'=> date('m/d/Y', strtotime("$current_service->start_date")) . ' - '. date('m/d/Y', strtotime("$current_service->end_date")),
            'service_messages'=> ($current_service->service_type == 1) ? service_messages::fetch_related_messages($service_local_id, 7) :
                array(
                    'Welcome'=> service_messages::fetch_related_messages($service_local_id, 1),
                    'Good Score'=> service_messages::fetch_related_messages($service_local_id, 2),
                    'Poor Score'=> service_messages::fetch_related_messages($service_local_id, 3),
                    'Last Play'=> service_messages::fetch_related_messages($service_local_id, 4),
                    'Never Play'=> service_messages::fetch_related_messages($service_local_id, 5),
                    'Excellent Score'=> service_messages::fetch_related_messages($service_local_id, 6),
                    'Closing'=> service_messages::fetch_related_messages($service_local_id, 8)
                ),
            'br_allow'=>$current_service->broadcast,
            'br_type'=> $current_service->broadcast_type,
            'br_length'=> $current_service->broadcast_length,
            'br_cross_sell_set'=> $current_service->cross_sell_services,
            'br_cross_sell_sub_set'=> $current_service->cross_sell_on_sub,
            'br_cross_sell_first_set'=>$current_service->cross_sell_on_first,
            'br_opening'=> $current_service->opening_message,
            'sp_id'=> $current_service->sp_id,
            'sp_password'=> $current_service->sp_password,
            'sp_service_id'=> $current_service->service_sdp_id,
            //'sp_product_id'=> $current_service->product_sdp_id,
            'sp_shortcode'=> $current_service->accesscode,
            //'sp_bill_rate'=> number_format($current_service->billing_rate, 2),
            'sp_free_period'=> $current_service->free_period,
            'date_created'=> date('M d Y', strtotime("$current_service->date_created")),
            'total_threads'=>$current_service->set_thread,
            'authcode'=> $current_service->service_type == 4 ? app::fetch_service_authcode($service_local_id) : null
        );
    }

    /* return campaign draw data list */
    public static function service_draw_data_basic($service_draw_id)
    {
        /* instantiate current draw */
        $current_service_draw = new service_draw($service_draw_id);
        return array(
            'name'=>$current_service_draw->name,
            'desc'=>$current_service_draw->desc,
            'notify'=>$current_service_draw->notify,
            'draw_type_id'=>$current_service_draw->draw_type_id,
            'draw_num'=>$current_service_draw->draw_win_no,
            'draw_engine_type'=>$current_service_draw->draw_engine_type,
            'draw_date_range'=>date('m/d/Y', strtotime("$current_service_draw->start_date")) . ' - '. date('m/d/Y', strtotime("$current_service_draw->end_date")),
            'services_draw_linked'=>$current_service_draw->services_draw_linked,
            'active'=>$current_service_draw->active,
            'date_created'=>$current_service_draw->date_created,
            'draw_win_rollout'=>$current_service_draw->draw_win_rollout
        );

    }

    /* return service data content list */
    public static function service_data_content($service_local_id)
    {
        /* instantiate current service */
        $current_service = new services($service_local_id);
        return array(
            'service_contents'=> self::service_data($current_service)
        );

    }

    public static function service_id($service_name)
    {
        $result = db::sql("SELECT service_local_id FROM `tl_services` WHERE `name` = '$service_name'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($service_id) = mysqli_fetch_array($result)){
                return (int) $service_id;
            }
        } return null;
    }

    /* return service data */
    private static function service_data($current_service)
    {
        $result = ($current_service->service_type == 1) ?
            db::sql("SELECT id, message, date_created FROM `tl_services_contents` WHERE service_id = '$current_service->service_local_id' ORDER BY date_created DESC;", DB_NAME):
            db::sql("SELECT id, question, answer, correct_notify, incorrect_notify, score, date_created FROM `tl_service_trivia` WHERE service_id = '$current_service->service_local_id' ORDER BY date_created DESC;", DB_NAME);
        $service_data = null;
        if (mysqli_num_rows($result)){
            if ($current_service->service_type == 1){
                while(list($content_id, $message, $date_created)=mysqli_fetch_array($result)){
                    $service_data[] = array('id'=>$content_id, 'message'=>$message, 'date_created'=>date('Y-m-d', strtotime("$date_created")));
                }
            } else {
                while(list($content_id, $question, $answer, $correct, $incorrect, $score, $date_created)=mysqli_fetch_array($result)){
                    $service_data[] = array('id'=>$content_id, 'question'=>$question, 'answer'=>$answer, 'correct'=>$correct, 'incorrect'=>$incorrect, 'score'=>$score, 'date_created'=>date('Y-m-d', strtotime("$date_created")));
                }
            }
        }
        return $service_data;
    }

    /*
     * . msisdn : user_no
    . keyword: keyword used
    . req_type: subscribe/unsubscribe
    . req_service: 1/2/3
    . req_state: 0=fails 1=success
    . by_date: time request sent
    . req_sent: 1=sent 0=not sent
    . processing: 1=in progress / 0=pending approval / 2=processed / 3=sdp has processed and sent in a request relation
    . resp_time: time sdp sent resp
    . resp_type: 1=add 2=delete 3=update
    . resp_desc: addition/deletion/update
    . resp_service_id: the serviceID that SDP processes the request
    . transID: sdp transactionID
    . gracePeriod: 1: free period | 0: service is being charged
    . date_created: track and maintain unique request per day per user
    . daily_count: count on daily basis how many times same request has been performed
    . code: local subscription code - please see below:
        [Code no.]
            --> 0. In progress
            --> 1. new user added successfully
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
            --> 12. Service has been closed
            --> 13. Broadcast has been disabled
            --> 14. Invalid SDP format or Service Request
            --> 15. SDP - 500 Internal Server Error
            --> 16. Service hasn't opened yet
            --> 17. Invalid SDP format or Product Request
         */

    /* log current service request */
    public static function request_logged($service_local_id, $msisdn, $keyword, $req_type, $req_state, $req_sent)
    {
        /* update daily count on every request */
        $result = db::sql("INSERT INTO `tl_sync_services_requests` (msisdn, keyword, req_type, req_service, req_state, by_date, req_sent, date_created, daily_count)
                VALUES ('$msisdn', '$keyword', '$req_type', '$service_local_id', '$req_state', CURRENT_TIMESTAMP, '$req_sent', CURRENT_TIMESTAMP, 1)", "tl_gateway");
        /* this record already been inserted for current user then do an update*/
        if ($result === false){
            self::update_request_counter($msisdn, $keyword, $req_type);
        }
        return $result;
    }

    /* direct incoming subscription relation request | SDP is managing subscriptions over USSD Menu
    */
    public static function direct_request_logged($msisdn, $keyword, $req_type, $req_serviceID, $processing, $resp_type, $resp_desc, $transID, $gracePeriod, $service_sp_ID, $product_sp_ID)
    {
        /* update daily count on every request */
        $result = db::sql("INSERT INTO `tl_sync_services_requests` (msisdn, `keyword`, `req_type`, `req_service`, `by_date`, `processing`, resp_time, resp_type, resp_desc, transID, gracePeriod, resp_service_id, resp_product_id, daily_count, date_created)
            VALUES('$msisdn', '$keyword', '$req_type', '$req_serviceID', CURRENT_TIMESTAMP, '$processing', CURRENT_TIMESTAMP, '$resp_type', '$resp_desc', '$transID', '$gracePeriod', '$service_sp_ID', '$product_sp_ID', 1, CURRENT_TIMESTAMP)", "tl_gateway");
        /* this record already been inserted for current user then do an update*/
        if ($result === false){
            self::update_request_counter($msisdn, $keyword, $req_type);
        }
        return $result;
    }

    /* update current service request */
    public static function update_logged($msisdn, $processing, $req_sent=null, $resp_type=null, $resp_desc=null, $transID=null, $gracePeriod=null, $service_sp_ID=null, $keyword=null, $req_type=null, $req_state=null, $process_code=0)
    {
        $date = date('Y-m-d');
        /* pending approval | in progress */
        if ($processing == 0 || $processing == 1) {
            return db::sql("UPDATE `tl_sync_services_requests` SET `processing` = '$processing', `req_sent` = '$req_sent', `req_state` = 1 WHERE `msisdn` = '$msisdn' AND `keyword` = '$keyword' AND `by_date` LIKE '$date%' AND `req_type` = '$req_type' ORDER BY `by_date` DESC LIMIT 1", "tl_gateway");
        } else if ($processing == 2) {
            /* completed */
            return db::sql("UPDATE `tl_sync_services_requests` SET `processing` = '$processing', resp_time = CURRENT_TIMESTAMP, resp_type = '$resp_type', resp_desc = '$resp_desc',
            transID = '$transID', gracePeriod = '$gracePeriod', resp_service_id = '$service_sp_ID' WHERE `msisdn` = '$msisdn' AND `keyword` = '$keyword' AND `by_date` LIKE '$date%' AND `req_type` = '$req_type' ORDER BY `by_date` DESC LIMIT 1", "tl_gateway");
        } else {
            /* update state from a direct request */
            return db::sql("UPDATE `tl_sync_services_requests` SET  `req_sent` = '$req_sent',  `processing` = '$processing',
            `req_state` = '$req_state', `code` = '$process_code', `by_date` = CURRENT_TIMESTAMP WHERE `msisdn` = '$msisdn' AND `keyword` = '$keyword' AND `by_date` LIKE '$date%' AND `req_type` = '$req_type' ORDER BY `by_date` DESC LIMIT 1", "tl_gateway");
        }
    }

    /* update daily count per user for tracking request */
    private static function update_request_counter($msisdn, $keyword, $req_type)
    {
        $date = date('Y-m-d');
        /* get current count */
        $current_count = self::get_current_count($msisdn, $keyword, $req_type);
        $count = (!is_null($current_count)) ? (int) $current_count + 1  : 1;

        db::sql("UPDATE `tl_sync_services_requests` SET `daily_count` = '$count' WHERE `msisdn` = '$msisdn' AND `keyword` = '$keyword' AND `by_date` LIKE '$date%' AND `req_type` = '$req_type' ORDER BY `by_date` DESC LIMIT 1", "tl_gateway");
    }

    private static function get_current_count($msisdn, $keyword, $req_type)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `daily_count` FROM `tl_sync_services_requests` WHERE `msisdn` = '$msisdn' AND `keyword` = '$keyword' AND `by_date` LIKE '$date%' AND `req_type` = '$req_type' ORDER BY `by_date` DESC LIMIT 1", "tl_gateway");
        if (mysqli_num_rows($result)){
            while(list($c) = mysqli_fetch_array($result)){
                return $c;
            }
        }
        return null;
    }

    /* log and open user extra menu request */
    public static function start_ussd_extra_menu($msisdn)
    {
        if (mysqli_num_rows(db::sql("SELECT * FROM `sys_ussd_menu` WHERE `msisdn` = '$msisdn'", DB_NAME))){
            return db::sql("UPDATE `sys_ussd_menu` SET `extra_menu` = 1 WHERE `msisdn` = '$msisdn'", DB_NAME);
        }

        return db::sql("INSERT INTO `sys_ussd_menu` (`msisdn`, `extra_menu`) VALUES('$msisdn', 1)", DB_NAME);
    }

    /* close user extra menu */
    public static function close_ussd_extra_menu($msisdn)
    {
        return db::sql("UPDATE `sys_ussd_menu` SET `extra_menu` = 0 WHERE `msisdn` = '$msisdn'", DB_NAME);
    }

    /* check ussd extra menu for current user */
    public static function has_extra_menu($msisdn)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `sys_ussd_menu` WHERE `msisdn` = '$msisdn' AND `extra_menu` = 1", DB_NAME));
    }

    /* find trace_id */
    public static function find_trace_service_id($trace_id)
    {
        $target_services = array('tl_servicing_contents', 'tl_servicing_trivia');
        $service_local_id = null;
        foreach($target_services as $service_local){
            $result = db::sql("SELECT service_id FROM `".$service_local."` WHERE tracer_id = '$trace_id';", "tl_datasync");
            if (mysqli_num_rows($result)){
                while(list($service_id) = mysqli_fetch_array($result)){
                    $service_local_id = $service_id;
                }
                break;
            }
        } return $service_local_id;
    }

    /* check current trace_id status */
    public static function current_broadcast_delivered_state($trace_id, $service_id)
    {
       return (in_array((new services($service_id))->service_type, array(1, 3))) ?
           mysqli_num_rows(db::sql("SELECT process_state FROM `tl_servicing_contents` WHERE `tracer_id`='$trace_id' AND process_state = 1", "tl_datasync")) :
           mysqli_num_rows(db::sql("SELECT process_state FROM `tl_servicing_trivia` WHERE `tracer_id`='$trace_id' AND process_state = 2", "tl_datasync"));
    }

    /* update current trace_id state */
    public static function update_broadcast_delivered_state($trace_id, $service_id, $state)
    {
        $current_service = new services($service_id);
        return ($state > 0) ? (
                (in_array($current_service->service_type, array(1, 3))) ?
                    db::sql("UPDATE `tl_servicing_contents` SET process_state = 1 WHERE `tracer_id` = '$trace_id'", "tl_datasync"):
                    db::sql("UPDATE `tl_servicing_trivia` SET process_state = 2 WHERE `tracer_id` = '$trace_id'", "tl_datasync")
        ) : (
                (in_array($current_service->service_type, array(1, 3))) ?
                    db::sql("UPDATE `tl_servicing_contents` SET process_state = 3 WHERE `tracer_id` = '$trace_id'", "tl_datasync"):
                    db::sql("UPDATE `tl_servicing_trivia` SET process_state = 3 WHERE `tracer_id` = '$trace_id'", "tl_datasync")
        );
    }



    /****************** Web API interface ********************/

    /* report services requests activities */
    public static function services_requests_log()
    {
        $result = db::sql("SELECT msisdn, req_type as request, req_service as service_id, gracePeriod as free_trial, req_state as state, daily_count, by_date, `code`, resp_product_id FROM `tl_sync_services_requests`
                            ORDER BY by_date DESC LIMIT 100", "tl_gateway");
        $requests_log = array();
        if (mysqli_num_rows($result)){
            while(list($msisdn, $request, $service_id, $free_trial, $state, $count, $date, $code, $sdp_product_id) = mysqli_fetch_array($result)){
                $billing_type = self::service_billing_cycle_sdp_product_related($service_id, $sdp_product_id);
                $requests_log[] = array('msisdn'=>$msisdn, 'request'=>$request, 'service'=> (new services($service_id))->name, 'trial'=>($free_trial == '0') ? '0': (new services($service_id))->free_period,
                    'state'=> ($state) ? (($code == '0') ? 'In progress':'Approved'): 'Declined', 'reason'=> self::request_code_details($code),
                    'count'=>$count, 'date'=> date_format(date_create($date), 'Y-m-d H:i'), 'bill_type'=> is_array($billing_type) ? $billing_type['type'] : $billing_type);
            }
        }
        return $requests_log;
    }

    /* report payment reqeuest activities */
    public static function payment_requests_log()
    {
        $result = db::sql("SELECT msisdn, req_type as request, req_service as service_id, req_state as state, appname, resp_desc as details, by_date, transID, req_amount, resp_code
                  FROM `tl_sync_payment_requests` ORDER BY by_date DESC LIMIT 500;", "tl_gateway");
        $request_log = array();
        if (mysqli_num_rows($result)){
            while(list($msisdn, $request, $service_id, $state, $author, $details, $date_created, $transID, $amount, $code) = mysqli_fetch_array($result)){
                $request_log[] = array('msisdn'=>$msisdn, 'request'=>$request, 'service'=>(new services($service_id))->name, 'amount'=>$amount,
                    'state'=> $state ? ($code == '01' ? 'Approved': ( $code == '1000' ? 'In Progress': ($code == '' ? 'Processing' : 'Failed'))) : 'Denied', 'details'=> $state ? $details : ($details == '' ? 'Internal Server Error' : $details),
                    'transactionid'=> $transID, 'author'=> is_numeric($author) ? (new login($author))->fullname : $author, 'date'=>$date_created);
            }
        } return $request_log;
    }

    /* return state details error_code */
    private static function request_code_details($code)
    {
        $return_details = '';
        switch($code){
            case '0':
                $return_details = 'Unconfirmed';
                break;
            case '1':
                $return_details = 'Success';
                break;
            case '2':
                $return_details = 'Success';
            break;
            case '3':
                $return_details = 'Internal error';
                break;
            case '4':
                $return_details = 'Subscription exits';
                break;
            case '5':
                $return_details = 'User not found';
                break;
            case '6':
                $return_details = 'Success';
                break;
            case '7':
                $return_details = 'Internal error';
                break;
            case '8':
                $return_details = 'Success';
                break;
            case '9':
                $return_details = 'Internal error';
                break;
            case '10':
                $return_details = 'No subscription exists';
                break;
            case '11':
                $return_details = 'Unknown error';
                break;
            case '12':
                $return_details = 'Service already closed';
                break;
            case '13':
                $return_details = 'Broadcast has been disabled';
                break;
            case '14':
                $return_details = 'Invalid SDP format';
                break;
            case '15':
                $return_details = 'SDP Internal Server Error';
                break;
            case '16':
                $return_details = "Service not opened yet";
                break;
            case '17':
                $return_details = 'Invalid SDP format or Product Request';
                break;
        }
        return $return_details;
    }


    /* report services daily push activities */
    public static function broadcast_stats($service_local_id)
    {
       return  array(
           'data'=> array(
            'push'=> thread_ctrl::return_thread_stats($service_local_id),
            'error'=>thread_ctrl::return_thread_stats($service_local_id, true),
            'total'=>user::active_user_base($service_local_id)
            )
       );
    }

    /*********** DRAW ENGINE ***********



    /* check if player been a winner  */
    private static function is_winner($user_id)
    {
        return mysqli_num_rows(db::sql("SELECT user_id FROM `winners` WHERE user_id = '$user_id';", "mtn_promo"));
    }

    /* fetch winners | @type: weekly or monthly winners */
    public static function fetch_winners($type, $print=null)
    {
        $result = null;
        $process_result = null;

        $result = ($type == 'weekly') ? db::sql("SELECT user_id, score, entries, top_selected, date_created, created_by FROM `winners` WHERE week_selected = 1 ORDER BY date_created DESC LIMIT 100", DB_NAME):
            db::sql("SELECT user_id, score, entries, top_selected, date_created, created_by FROM `winners` WHERE month_selected = 1 ORDER BY date_created DESC LIMIT 100", DB_NAME);

        if (mysqli_num_rows($result)){
            while(list($user_id, $score, $entries, $top, $date, $loginID)=mysqli_fetch_array($result)){
                /* get user details */
                $msisdn = (new user($user_id))->msisdn;
                $fullname = (new login(login::get_login_id($msisdn)))->fullname;
                $selected = ($top == 1) ? 'Top Scorer' : 'Randomly';
                $by = (new login($loginID))->fullname;
                $process_result[] = array('name'=>$fullname, 'msisdn'=>$msisdn, 'score'=>$score, 'entries'=>$entries, 'selected'=>$selected, 'date'=>$date, 'by'=>$by);
            }
        } else {
            $process_result = array('error'=>'Enable to fetch data - no data available');
        }

        return  (isset($process_result['error'])) ? $process_result : ( (is_null($print)) ? array('data'=>$process_result) : $process_result);
    }

    /* process raffle selection
    @type: top or random selection
    @category: weekly or monthly
    @loginID: current loginId making the request for verification
    @remote_address: the client IP making the request
    @update:
        - 2017-04-17 : disable select as top winner from FrontEnd task
    */
    public static function raffle_selection($start_date, $end_date, $type, $category, $loginID, $remote_address)
    {
        if (($type == 'top' || $type == 'random') && ($category == 'weekly' || $category == 'monthly')){
            /* check if the loginID performing the request has the administrative right
        and currently login from the same remote address */
            //TODO: Change winning operation right to Administration only
            if ((new login($loginID))->role == '2'){
                /* check remote_address for security */
                $current_session = login::has_session_started($loginID, $remote_address);
                if (is_null($current_session) || $current_session === false){
                    return array('error'=>'Enable to process your request. You are not authorised to perform winning task');
                } else {
                    return self::process_raffle_selection($start_date, $end_date, $type, $category, $loginID);
                }
            } else {
                return array('error'=>'Enable to process your request. You are not authorised to perform winning task');
            }
        } else {
            return array('error'=>'Enable to process your request. Wrong parameters value given. Please check manual for more info');
        }
    }

    /* return raffle select date range requested */
    public static function raffle_date_range($type)
    {
        /* check a winner has been selected for current type */
        if (self::has_winner_selected($type)){
            if ($type == 'weekly'){
                return array('result'=>thread_ctrl::get_start_end_date(date('W'), date('Y')));
            } else if ($type == 'monthly'){
                return array('result'=>thread_ctrl::get_start_end_date_month());
            } else {
                return array('error'=>'wrong type given. please specify weekly or monthly');
            }
        } else {
            if ($type == 'weekly' || $type == 'monthly'){
                return array('result'=> self::last_date_range($type));
            } else {
                return array('error'=>'wrong type given. please specify weekly or monthly');
            }
        }

    }

    /* return date range of last week or month */
    public static function last_date_range($type)
    {
        if ($type == 'weekly'){
            $w_range = thread_ctrl::get_start_end_date(date('W'), date('Y'));
            return array(
                'week_start'=>date('Y-m-d', strtotime("-7 days", strtotime($w_range['week_start']))),
                'week_end'=>date('Y-m-d', strtotime("-7 days", strtotime($w_range['week_end'])))
            );
        } else {
            $m_range = thread_ctrl::get_start_end_date_month();
            return array(
                'start_date'=>date('Y-m-d', strtotime("-1 months", strtotime($m_range['start_date']))),
                'end_date'=>date('Y-m-d', strtotime("-1 months", strtotime($m_range['end_date'])))
            );
        }
    }

    /* reset selected winner in current range */
    public static function raffle_reset_winner($type, $loginID, $remote_address)
    {
        if ((new login($loginID))->role == '2'){
            /* check remote_address for security */
            $current_session = login::has_session_started($loginID, $remote_address);
            if (is_null($current_session) || $current_session === false){
                return array('error'=>'Enable to process your request. You are not authorised to perform winning task');
            } else {
                if ($type == 'weekly'){
                    $date = date('W');
                    if (db::sql("DELETE FROM `winners` WHERE `week` = '$date';", DB_NAME)){
                        return array('result'=>'The current weekly winner has been successfully reset');
                    } else {
                        return array('error'=>'Enable to reset current weekly winner. You have not selected a winner for current week');
                    }
                } else {
                    $date = date('M - Y');
                    if (db::sql("DELETE FROM `winners` WHERE `month` = '$date';", DB_NAME)){
                        return array('result'=>'The current monthly winner has been successfully reset');
                    } else {
                        return array('error'=>'Enable to reset monthly winner. You have not selected a winner for current month!');
                    }
                }
            }
        } else {
            return array('error'=>'Enable to process your request. You are not authorised to perform winning task');
        }
    }

    /* check if winner has selected for last week or month */
    public static function has_winner_selected($type)
    {
        if ($type == 'weekly'){
            $date = date('W');
            return mysqli_num_rows(db::sql("SELECT * FROM `winners` WHERE `week` = '$date'", DB_NAME));
        } else {
            $date = date('M - Y');
            return mysqli_num_rows(db::sql("SELECT * FROM `winners` WHERE `month` = '$date'", DB_NAME));
        }
    }



    /* select top players as a winner */
    private static function process_raffle_selection($start_date, $end_date, $type, $category, $loginID)
    {
        $players = self::get_players_stats($start_date, $end_date, null, ($category == 'weekly') ? 'W': 'M');

        $top = 0;
        $random = 0;
        $winners = null;
        /* top selection operation */
        if (isset($players['data'])){
            if ($type == 'top'){
                $top = 1;
                /* Sort by score using anonymous function */
                uasort($players['data'], function($x, $y){
                    return ($x['score'] < $y['score']);
                });
                /* get top scorer */
                $top_score = 0;
                foreach($players['data'] as $player){
                    $top_score = $player['score'];
                    break;
                }
                /* unset non top score player */
                foreach($players['data'] as $key => $player){
                    if ($player['score'] != $top_score){
                        unset($players['data'][$key]);
                    }
                }
            } else {
                /* random selection operation */
                $random = 1;
            }

            /* create a pool as per user number of entries */
            $winners = self::process_pool_entries($players['data']);
            //echo '<pre>'. print_r($winners, true).'</pre>';

            shuffle($winners);
        }

        if (is_null($winners)){
            return array('error'=>'Enable to process your request. No data found from '. $start_date. ' to '. $end_date);
        } else {
            /* allow only to perform this request by the next 3 days to the end of the given range */
           $allowed_days = array(
               date('Y-m-d', strtotime("$end_date +1 days")),
               date('Y-m-d', strtotime("$end_date +2 days")),
               date('Y-m-d', strtotime("$end_date +3 days")),
           );
           if (in_array(date('Y-m-d'), $allowed_days)){
               /* process a winner for the requested category */
               $selectRandomTop10 = (count($winners) > 10) ? rand(0, 10) : 0;
               $process_result = ($category == 'weekly') ? self::process_winner($winners[$selectRandomTop10], $top, $random, 1, $loginID) : self::process_winner($winners[$selectRandomTop10], $top, $random, 2, $loginID);
               if (is_null($process_result) || ($process_result === false)){
                   return array('error'=>'Enable to process your request. A '. $category.' winner is already been selected. Please refresh winners table');
               } else {
                   /* reset users stats draw */
                    user::reset_draw_stats_entries();
                   return array('result'=>$winners[$selectRandomTop10]['name'].' - '. $winners[$selectRandomTop10]['msisdn'].' has been selected as the '. $category.' winner and successfully recorded');
               }
           } else {

               return array('error'=>'Enable to process your request. Winner can only be selected by the next 3 days of the end of given range. Please do a winner selection on '. implode(' or ', $allowed_days)) ;
           }
        }
    }

    /* create a pool as per user number of entries */
    private static function process_pool_entries($data)
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
        }
        return $users_pool;
    }

    /* process winner */
    private static function process_winner($player, $top=0, $random=0, $category, $loginID)
    {
        $process_result = null;
        /* determine weekly or monthly */
        if ($category == 1){
            /* Weekly */
            $current_week = date('W');
            $process_result = db::sql("INSERT INTO `winners` (user_id, score, entries, top_selected, random_selected, week_selected, `week`, date_created, created_by)
                VALUES('". user::get_user_id($player['msisdn'])."', '".$player['score']."', '". $player['entries']."', '$top', '$random', 1, '$current_week', CURRENT_TIMESTAMP, $loginID )", DB_NAME);
        } else {
            /* Monthly */
            $curren_month = date('M - Y');
            $process_result = db::sql("INSERT INTO `winners` (user_id, score, entries, top_selected, random_selected, month_selected, `month`, date_created, created_by)
                VALUES('". user::get_user_id($player['msisdn'])."', '".$player['score']."', '". $player['entries']."', '$top', '$random', 1, '$curren_month', CURRENT_TIMESTAMP, $loginID )", DB_NAME);
        }
        return $process_result;
    }

    /* delay first content delivery request controller
        @param: $sent = 0 : insert | 1: update
    */
    public static function delay_content_delivery($sent, $user_id, $service_id, $state=null, $msisdn=null, $sleep=null)
    {
        $date = date('Y-m-d');
        if ($sent){
            /* update content delivery */
            return (is_null($state)) ?
                db::sql("UPDATE `tl_delay_content_delivery` SET sent = '$sent', process_end = CURRENT_TIMESTAMP WHERE user_id = '$user_id' AND date_created = '$date' AND service_id = '$service_id';", "tl_gateway") :
                (
                    ($state == 0) ?
                        db::sql("UPDATE `tl_delay_content_delivery` SET sent = '$state', process_end = CURRENT_TIMESTAMP WHERE user_id = '$user_id' AND date_created = '$date' AND service_id = '$service_id';", "tl_gateway")  :
                        db::sql("UPDATE `tl_delay_content_delivery` SET process_start = CURRENT_TIMESTAMP WHERE user_id = '$user_id' AND date_created = '$date' AND service_id = '$service_id';", "tl_gateway")
                );
        } else {
            /* log current request upon subscription */
            return (is_int(db::sql("INSERT INTO `tl_delay_content_delivery` (user_id, service_id, msisdn, sleep, sent, date_created)
            VALUES('$user_id', '$service_id', '$msisdn', '$sleep', '$sent', '$date');", "tl_gateway"))) ? true : false;
        }
    }

    /* get queuing users first content */
    public static function get_first_contents_users()
    {
        $result = db::sql("SELECT * FROM `tl_delay_content_delivery` WHERE process_start IS NULL ;", "tl_gateway");
        $users = null;
        if (mysqli_num_rows($result)){
            while(list($user_id, $service_id, $msisdn, $sleep) = mysqli_fetch_array($result)){
                $users[] = array('user_id'=>$user_id, 'service_id'=>$service_id, 'msisdn'=>$msisdn, 'sleep'=>$sleep);
            }
        }
        return $users;
    }

    /* cleanup today users first content request */
    public static function cleanup_content_delivery()
    {
        return db::sql("DELETE FROM `tl_delay_content_delivery`", "tl_gateway");
    }

    /* cleanup notify queues */
    public static function cleanup_notify_queues()
    {
        return db::sql("DELETE FROM `tl_datasync_notify`;", "tl_datasync");
    }

    /* cleanup data-sync queues */
    public static function cleanup_datasync_queues()
    {
        return db::sql("DELETE FROM `tl_datasync_trivia`;", "tl_datasync");
    }

    /* cleanup data-sync queues */
    public static function cleanup_bulk_sync_queues()
    {
        return db::sql("DELETE FROM `tl_bulk_sync`;", "tl_gateway");
    }

    /* cleanup renewal request and unconfirmed from the gateway */
    public static function cleanup_gateway_free_service()
    {
        $services = services::services_list();
        foreach ($services as $service ) {

        }
    }

    /* reset users service broadcast tracking logs */
    public static function reset_services_broadcast_tracking_log()
    {
        return db::sql("UPDATE `tl_services` SET last_process_id = 0;", DB_NAME);
    }

    /* billing report
     * @type: dashboard : return current billing the log stats
     */
    public static function billing_report($type, $service_id)
    {
        if ($type == 'dashboard'){
            /* check if individual service is requested */
            if ($service_id != 'all'){
                $return_process = array(
                    'total_new_day' => user::sum_new_today_subs($service_id),
                    'total_subs' => user::sum_totals_subs($service_id, null, true),
                    'total_unsubs_day' => user::sum_total_unsubs($service_id,  date('Y-m-d')),
                    'total_unsubs'=> user::sum_total_unsubs($service_id),
                    'total_bills_day' => number_format(self::totals_billing_services($service_id, date('Y-m-d')), 2),
                    'total_bills' => number_format(self::totals_billing_services($service_id, null, true), 2)
                );
            } else {
                /* return for all services */
                $result = null;
                $services = self::services_list();
                if ($services){
                    foreach($services as $service){
                        /* don't report on Rest-Service type */
                        if ($service['type'] != '4'){
                            $result[] = array(
                                'service_name' => $service['name'],
                                'total_new_day' => user::sum_new_today_subs($service['id']),
                                'total_unsubs_day' => user::sum_total_unsubs($service['id'],  date('Y-m-d')),
                                'total_susp_day' => services::get_today_suspended_users($service['id']),
                                'total_play_rate'=> services::get_play_rate($service['id']),
                                'total_subs' => user::sum_totals_subs($service['id'], null, true),
                                'total_bills_day' => number_format(self::totals_billing_services($service['id'], date('Y-m-d')), 2),
                                'total_bills' => number_format(self::totals_billing_services($service['id'], null, true, true), 2)
                            );
                        }
                    }
                }

                $return_process = (is_null($result)) ? array('data'=>array(array(
                    'service_name'=>'No service exits. Please use manage service to add new service',
                    'total_new_day' => '',
                    'total_unsubs_day' => '',
                    'total_susp_day' => '',
                    'total_subs' => '',
                    'total_bills_day' => '',
                    'total_bills' => ''
                    ))) :
                    array('data'=>$result);
            }

        } else {
            /* return the table report */
            $return_process = ($type == 'table') ? array('data' => self::billing_timelines_details($service_id, true))
                : ( ($type == 'chart') ? array('data' => self::billing_timelines_details($service_id, true, true))
                    : ( $type == 'chart_month' ? array('data' => self::billing_monthly_timelines_details($service_id))
                        : array('error'=>'Enable to fetch billing report. Wrong type submitted. Report to System Administrator!')));
        }

        return $return_process;
    }

    /* return service billing amount
     * @service_id: return total bills for the current service_id
     * @today: return total bills on the specified date or the target service_id if specified
     * @total_bill_by: return total bills since the target service_id (please note that the service_id is required)
     * or else service inception will not be taken into account in the return value
    */
    public static function totals_billing_services($service_id, $target_day=null, $since_inception=null, $current_month=null)
    {
        /* get the start date */
        $service_list = thread_ctrl::all_service_status($service_id);
        $start_date = date_format(date_create($service_list[0][1]), 'Y-m-d');
        $result = null;

        if (is_null($target_day)){
            if (is_null($current_month)){
                $result = db::sql("SELECT resp_product_id FROM `tl_sync_services_requests` WHERE req_service = '$service_id' AND resp_type IN (1, 3) AND gracePeriod = 0 AND date_created > SUBDATE('$start_date', 1);", "tl_gateway");
            } else {
                $date_range = thread_ctrl::get_start_end_date_month();
                $result = db::sql("SELECT resp_product_id FROM `tl_sync_services_requests` WHERE req_service = '$service_id' AND resp_type IN (1, 3) AND gracePeriod = 0
                      AND date_created > SUBDATE('". $date_range['start_date']."', 1) AND date_created < DATE_ADD('". $date_range['end_date']."', INTERVAL 1 DAY);", "tl_gateway");
            }
        } else {
            /* check if total bills is requested */
            if ($since_inception){
                $target_day = is_null($target_day) ? '' : date('Y-m-d', strtotime("$target_day +1 days"));
                $result = db::sql("SELECT resp_product_id FROM `tl_sync_services_requests` WHERE req_service = '$service_id' AND resp_type IN (1, 3) AND gracePeriod = 0 AND date_created > SUBDATE('$start_date', 1) AND date_created < '$target_day%';", "tl_gateway");
            } else {
                $result = db::sql("SELECT resp_product_id FROM `tl_sync_services_requests` WHERE req_service = '$service_id' AND resp_type IN (1, 3) AND gracePeriod = 0 AND date_created LIKE '$target_day%';", "tl_gateway");
            }
        }

        $total_bill_services = 0;
        if (mysqli_num_rows($result)){
            while(list($resp_product_id) = mysqli_fetch_array($result)){
                $product_type = self::service_billing_cycle_sdp_product_related($service_id, $resp_product_id);
                $total_bill_services += is_array($product_type) ? $product_type['rate'] : 0;
            }
        }
        return $total_bill_services;
    }

    /* get expected billing amount as per user base */
    public static function totals_billing_services_expected($service_id, $today)
    {
        $total_bill_services = 0;
        $today_bill_services = 0;

        /* if the current service has a free period, we detect today expected bill from the total */
        if ((new services($service_id))->free_period > 0){
            /* return today subscribers to current service */
            $active_subscribers = user::fetch_active_subs($service_id, null, null, $today);
            if ($active_subscribers){
                foreach($active_subscribers as $active){
                    $user_product_id = user::service_product_selected((new user($active['id']))->services_stats, $service_id);
                    if ($user_product_id){
                        $product_type = self::service_billing_cycle_sdp_product_related($service_id, null, $user_product_id);
                        $today_bill_services += is_array($product_type) ? $product_type['rate'] : 0;
                    }
                }
            }
        }

        /* return total subscribers to current service */
        $active_subscribers = user::fetch_active_subs($service_id);
        if ($active_subscribers){
            foreach($active_subscribers as $active){
                $user_product_id = user::service_product_selected((new user($active['id']))->services_stats, $service_id);
                if ($user_product_id){
                    $product_type = self::service_billing_cycle_sdp_product_related($service_id, null, $user_product_id);
                    $total_bill_services += is_array($product_type) ? $product_type['rate'] : 0;
                }
            }
        }

        return ($total_bill_services - $today_bill_services);
    }

    /* return service monthly report */
    public static function billing_monthly_timelines_details($service_id)
    {
        /* get the start date */
        $service_list = thread_ctrl::all_service_status($service_id);
        $start_date = date('Y-m-d', strtotime($service_list[0][1]));
        $current_month = thread_ctrl::get_start_end_date_month();
        /* evaluate month difference */
        $interval = date_diff(date_create(date('Y-m-d', strtotime($start_date))), date_create(date('Y-m-d', strtotime($current_month['start_date']))));
        $month_diff = (int) $interval->format('%m'); ++$month_diff;
        $billing_result = array();
        while($month_diff > -1){
            $target_month = thread_ctrl::get_start_end_date_month($month_diff);
            $result = db::sql("SELECT resp_product_id FROM `tl_sync_services_requests` WHERE req_service = '$service_id' AND resp_type IN (1, 3) AND gracePeriod = 0
                      AND date_created > SUBDATE('". $target_month['start_date']."', 1) AND date_created < DATE_ADD('". $target_month['end_date']."', INTERVAL 1 DAY);", "tl_gateway");
            $total_bill_services = 0;
            if (mysqli_num_rows($result)){
                while(list($resp_product_id) = mysqli_fetch_array($result)){
                    $product_type = self::service_billing_cycle_sdp_product_related($service_id, $resp_product_id);
                    $total_bill_services += is_array($product_type) ? $product_type['rate'] : 0;
                }
            }
            $billing_result[] = array('date'=>date('Y-m', strtotime("". $target_month['start_date']."")), 'total'=>$total_bill_services);

            --$month_diff;
        }
        return $billing_result;
    }


    /* return service billing details */
    public static function billing_timelines($service_id)
    {
        /* get the start date */
        $service_list = thread_ctrl::all_service_status($service_id);
        $date = date_format(date_create($service_list[0][1]), 'Y-m-d');

        $result = db::sql("SELECT DISTINCT date_created FROM `tl_sync_services_requests` WHERE date_created > SUBDATE('$date', 1) ORDER BY date_created ASC;", "tl_gateway");
        $day = 0;
        $return_process = null;
        if (mysqli_num_rows($result)){
            while(list($date_created) = mysqli_fetch_array($result)){
                $return_process[] = array(
                    'day'=> ++$day,
                    'date'=>$date_created,
                    'total_new_day'=> user::sum_new_today_subs($service_id, $date_created, null, true),
                    'total_unsubs_day' => user::sum_total_unsubs($service_id, $date_created),
                    'total_subs' => user::sum_totals_subs($service_id, $date_created, true),
                    'total_day_bills' => CURRENCY.' '. number_format(self::totals_billing_services($service_id, $date_created), 2),
                    'target_day_bills' => CURRENCY.' '. number_format(self::totals_billing_services_expected($service_id, $date_created), 2),
                    'rate_day_bills' => (self::totals_billing_services_expected($service_id, $date_created) == 0) ? '0.00%':
                        number_format( (((self::totals_billing_services($service_id, $date_created) * 100) / self::totals_billing_services_expected($service_id, $date_created)) > 100 ) ? 100 :
                            ((self::totals_billing_services($service_id, $date_created) * 100) / self::totals_billing_services_expected($service_id, $date_created)) , 2). '%',
                    'repeat_bills' => number_format( ((floatval(user::sum_totals_subs($service_id, $date_created, true)) - (floatval(user::sum_new_today_subs($service_id, $date_created)) + floatval(user::sum_total_unsubs($service_id, $date_created)))) < 0 ) ? 0 : (floatval(user::sum_totals_subs($service_id, $date_created, true)) - (floatval(user::sum_new_today_subs($service_id, $date_created)) + floatval(user::sum_total_unsubs($service_id, $date_created)))), 0),
                    'total_overall_bills' => CURRENCY.' '. number_format(self::totals_billing_services($service_id, $date_created, true), 2)
                );
            }
        }
        return $return_process;
    }

    /* return service billing details */
    public static function billing_timelines_details($service_id, $display=null, $chart=null)
    {
        $result = (is_null($display)) ?
            db::sql("SELECT `day`,	`date`,	total_new_day, total_unsubs_day, total_subs, total_day_bills, target_day_bills, rate_day_bills, repeat_bills, total_overall_bills, play_rate FROM `tl_services_billing` WHERE service_id = '$service_id'", DB_NAME):
            (
                ($chart) ?
                    db::sql("SELECT `day`,	`date`,	total_new_day, total_unsubs_day, total_subs, total_day_bills, target_day_bills, rate_day_bills, repeat_bills, total_overall_bills, play_rate FROM `tl_services_billing` WHERE service_id = '$service_id' ORDER BY `day` DESC LIMIT 7;", DB_NAME)
                    :  db::sql("SELECT `day`,	`date`,	total_new_day, total_unsubs_day, total_subs, total_day_bills, target_day_bills, rate_day_bills, repeat_bills, total_overall_bills, play_rate FROM `tl_services_billing` WHERE service_id = '$service_id' ORDER BY `day` DESC;", DB_NAME)
            );

        $return_process = null;
        if (mysqli_num_rows($result)){
            while(list($day, $date,	$total_new_day, $total_unsubs_day, $total_subs, $total_day_bills, $target_day_bills, $rate_day_bills, $repeat_bills, $total_overall_bills, $play_rate) = mysqli_fetch_array($result)){
                $return_process[] = array(
                    'day'=> $day,
                    'date'=> ($chart) ? substr($date, 5) : $date,
                    'total_new_day'=> $total_new_day,
                    'total_unsubs_day' => $total_unsubs_day,
                    'total_play_rate' => ($chart) ? (string) round((floatval($play_rate)), 0, PHP_ROUND_HALF_UP) : $play_rate,
                    'total_subs' => $total_subs,
                    'total_day_bills' => ($chart) ? ((strpos($total_day_bills, ',') !== false) ? str_replace(',', '', $total_day_bills) : $total_day_bills) : CURRENCY. ' '. $total_day_bills,
                    'target_day_bills' => CURRENCY. ' '. $target_day_bills,
                    'rate_day_bills' => $rate_day_bills. '%',
                    'repeat_bills' => ($chart) ? ((strpos($repeat_bills, ',') !== false) ? str_replace(',', '', $repeat_bills) : $repeat_bills) : $repeat_bills,
                    'total_overall_bills' => ($chart) ? ((strpos($total_overall_bills, ',') !== false) ? str_replace(',', '', $total_overall_bills) : $total_overall_bills) : CURRENCY. ' '. $total_overall_bills
                );
            }
        }
        return $return_process;
    }


    /* return service billing details */
    public static function billing_timelines_pool($service_id, $target=null)
    {
        /* get the start date */
        $service_list = thread_ctrl::all_service_status($service_id);
        $start_date = date_format(date_create($service_list[0][1]), 'Y-m-d');

        $result = is_null($target) ?
            db::sql("SELECT DISTINCT date_created FROM `tl_sync_services_requests` WHERE date_created > SUBDATE('$start_date', 1) ORDER BY date_created ASC;", "tl_gateway") :
            db::sql("SELECT DISTINCT date_created FROM `tl_sync_services_requests` WHERE date_created = '$target'", "tl_gateway");

        $day = 0;
        $return_process = null;
        if (mysqli_num_rows($result)){
            while(list($date_created) = mysqli_fetch_array($result)){
                $return_process[] = array(
                    'day'=> ++$day,
                    'date'=>$date_created,
                    'total_new_day'=> user::sum_new_today_subs($service_id, $date_created, null, true),
                    'total_unsubs_day' => user::sum_total_unsubs($service_id, $date_created),
                    'total_subs' => user::sum_totals_subs($service_id, $date_created, true),
                    'total_day_bills' => number_format(self::totals_billing_services($service_id, $date_created), 2),
                    'target_day_bills' => number_format(self::totals_billing_services_expected($service_id, $date_created), 2),
                    'rate_day_bills' => (self::totals_billing_services_expected($service_id, $date_created) == 0) ? 0.00:
                        number_format( (((self::totals_billing_services($service_id, $date_created) * 100) / self::totals_billing_services_expected($service_id, $date_created)) > 100 ) ? 100 : ((self::totals_billing_services($service_id, $date_created) * 100) / self::totals_billing_services_expected($service_id, $date_created)) , 2),
                    'repeat_bills' => number_format( ((floatval(user::sum_totals_subs($service_id, $date_created, true)) - (floatval(user::sum_new_today_subs($service_id, $date_created)) + floatval(user::sum_total_unsubs($service_id, $date_created)))) < 0 ) ? 0 : (floatval(user::sum_totals_subs($service_id, $date_created, true)) - (floatval(user::sum_new_today_subs($service_id, $date_created)) + floatval(user::sum_total_unsubs($service_id, $date_created)))), 0),
                    'total_overall_bills' => number_format(self::totals_billing_services($service_id, $date_created, true), 2)
                );
            }
        }
        return $return_process;
    }



    /* process current service content upload
        @service_id : required service id
        @load_perday: the number of contents to allocate per day
    */
    public static function process_upload_services($service_id, $load_perday, &$file)
    {
        /* check if the file has been uploaded */
        $upload_result = upload::process_upload_request($file);
        if ($upload_result === true){
            /* retrieve the saved file */
            $data_array = upload::fetch_file($file, 'CSV');
            if(is_null($data_array)){
                return array('error'=>'An internal error occurred - Enable to proceed - Try again. If the problem persist contact System Admin');
            } else {
                /* process service content */
                return self::process_services_contents($service_id, $load_perday, $data_array, $file);
            }
        } else {
            return $upload_result;
        }
    }
    /* process service related contents */
    private static function process_services_contents($service_id, $load_perday, $data_file, &$file)
    {
        if (isset($data_file[0]) && count($data_file[0]) > 0){
            /* check the last uploaded content date */
            $last_date_uploaded = service_messages::last_content_date_created($service_id);
            $collected_failed_q_ids = array();
           for ($i=0; $i < count($data_file); ++$i){
               $trace = explode('.', ($i / (int)$load_perday));
               $result = self::process_contents_services_required($data_file[$i], $service_id, ((new services($service_id))->service_type == 2) ?  $trace[0] : $i, $last_date_uploaded);
                if ($result !== true){
                    $collected_failed_q_ids[] = $result;
                }
           }
            return (count($collected_failed_q_ids) == 0) ? array('result'=>$file['name']. ' upload have been processed successfully!'):
                array('result'=>$file['name']. ' upload have been processed successfully with some errors. Please review error details below', 'fail'=>$collected_failed_q_ids);
        } else {
            return array('result'=>'Enable to continue - '. $file['name'].' seems to be empty');
        }
    }
    /* reprocess content for the related services as per the system requirements
    @date_adder : helps to track current date given in order to increment for each content
    */
    private static function process_contents_services_required($content_data, $service_id, $date_adder, $last_date_uploaded)
    {
        /* increment tomorrow date */
        //$date = date('Y-m-d', strtotime("+4 days"));
        //$date = date('Y-m-d');
        $date_created = date('Y-m-d', strtotime("$last_date_uploaded +$date_adder days"));

       if ((new services($service_id))->service_type == 2){
            /* trivia contents */
            $current_id = $content_data[0];
            $current_q = $content_data[1];
            $answers_init = array('correct'=>$content_data[2], 'incorrect_1'=> $content_data[3], 'incorrect_2'=>$content_data[4]);
            $answers_suffled = $answers_init; // pass this current state to track the correct answer in randomised answers
            $notify_correct = $content_data[6];
            $notify_incorrect = $content_data[8];
            shuffle($answers_suffled);
            $current_q = $current_q . ' A.'.$answers_suffled[0].' B.'.$answers_suffled[1].' C.'.$answers_suffled[2];
            /* find the correct answer in randomised entry given */
            $correct_key = null;
            foreach($answers_init as $key=>$val){
                for($i=0; $i < count($answers_suffled); ++$i){
                    if (($key == 'correct') && ($val == $answers_suffled[$i])){
                        $correct_key = $i;
                    }
                }
            }
           /* find the corresponding key answer */
            $correct_answer = ($correct_key == 0) ? 'A' : (($correct_key == 1) ? 'B': 'C');
           /* allocate some random score */
           $score = rand(6, 10);
           if (self::update_service_contents($service_id, array($current_q, $correct_answer, $notify_correct, $notify_incorrect, $score, $date_created )) === false){
               $error = (self::has_content_length($current_q)) ? 'Character max combined is above 160. Please review':
                   ((self::has_content_exits($current_q, (new services($service_id))) > 0 ) ? 'Current content exists': 'Please check irregular character encoding or the error is unknown');
               return array('id'=>$current_id, 'detail'=>$error);
           } else {
               return true;
           }
        } else {
           /* service contents */
           $current_id = $content_data[0];
           if (self::update_service_contents($service_id, array($content_data[1], $date_created)) === false){
               $error = (self::has_content_length($content_data[1])) ? 'Character max combined is above 160. Please review':
                   ((self::has_content_exits($content_data[1], (new services($service_id))) > 0 ) ? 'Current content exists': 'Please check irregular character encoding or the error is unknown');
               return array('id'=>$current_id, 'detail'=>$error);
           } else {
               return true;
           }

       }
    }

    private static function has_content_length($data)
    {
        $str_length = strlen($data);
        return ($str_length > 160) ? true : false;
    }
    private static function has_content_exits($data, $service_obj)
    {
        if ($service_obj->service_type == 2){
            return mysqli_num_rows(db::sql("SELECT * FROM `tl_service_trivia` WHERE question = '$data';", DB_NAME));
        } else {
            return mysqli_num_rows(db::sql("SELECT * FROM `tl_services_contents` WHERE message = '$data';", DB_NAME));
        }
    }

    private static function update_service_contents($service_id, $current_contents)
    {
        if ((new services($service_id))->service_type == 2){
            $question = (strpos($current_contents[0], "'") !== false) ? str_replace("'", "\'", $current_contents[0]) : $current_contents[0];
            $notify_correct = (strpos($current_contents[2], "'") !== false) ? str_replace("'", "\'", $current_contents[2]) : $current_contents[2];
            $notify_incorrect = (strpos($current_contents[3], "'") !== false) ? str_replace("'", "\'", $current_contents[3]) : $current_contents[3];
            /* insert record */
            return db::sql("INSERT INTO tl_service_trivia (question, answer, correct_notify, incorrect_notify, score, `status`, service_id, date_created)
            VALUES('$question', '$current_contents[1]', '$notify_correct', '$notify_incorrect', '$current_contents[4]', 1, '$service_id', '$current_contents[5]' )", DB_NAME);
        } else {
            $content = (strpos($current_contents[0], "'") !== false) ? str_replace("'", "\'", $current_contents[0]) : $current_contents[0];
            /* insert record */
            return db::sql("INSERT INTO tl_services_contents (message, `status`, service_id, date_created) VALUES('$content', 1, '$service_id', '$current_contents[1]')", DB_NAME);
        }
    }

    /* get current weekly prize allocated */
    public static function get_current_weeklyprize()
    {
        /* initialise current week date range */
        $current_week = thread_ctrl::get_start_end_date(date('W'), date('Y'));
        $result = db::sql("SELECT prize, `value`, `type` FROM `services_prize` WHERE date = '".$current_week['week_end']."' AND weekly = 1 AND quantity > 0;", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($prize, $value, $type) = mysqli_fetch_array($result)){
                return array('prize'=>$prize, 'value'=>$value, 'type'=>$type);
            }
        }
        return array('prize'=>null, 'value'=>null, 'type'=>null);
    }

    /* get current month end date prize allocated */
    public static function get_current_monthlyprize()
    {
        /* get last month date */
        $date = date("Y-m-t", strtotime(date('Y-m-d')));
        $result = db::sql("SELECT prize, `value` FROM `services_prize` WHERE date = '$date' AND monthly = 1 AND quantity > 0;", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($prize, $value) = mysqli_fetch_array($result)){
                return array('prize'=>$prize, 'value'=>$value);
            }
        }
        return array('prize'=>null, 'value'=>null, 'date'=>$date);
    }

    /* return current user services subscription stated */
    public static function users_subscriptions_services($msisdn)
    {
        $services_states = array('1'=>0, '2'=>0, '3'=>0);
        $services = array(1, 2, 3);
        foreach($services as $service_id){
            if (user::has_current_subscription($msisdn, $service_id)){
                if ($service_id == 1){
                    $services_states['1'] = 1;
                } else if($service_id == 2){
                    $services_states['2'] = 1;
                } else {
                  $services_states['3'] = 1;
                }
            }
        }
        return $services_states;
    }

    /* get active or charged subscribers today for the related services */
    public static function subscribers_services_billed($service_id)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT msisdn FROM `tl_sync_services_requests` WHERE date_created = '$date' AND resp_type = 3 AND req_service = '$service_id';", "tl_gateway");
        $users = array();
        if (mysqli_num_rows($result)){
            while(list($msisdn) = mysqli_fetch_array($result)){
                $users[] = $msisdn;
            }
        }
        return $users;
    }

    /* check if current user is still in free trial period a non delivery might be the cause of network error */
    public static function has_free_period_service_billed($user_services_stats, $service_id)
    {
        if (is_array($user_services_stats)){
            /* check matching service_id */
            foreach($user_services_stats as $service_stat){
                if ($service_stat['service_id'] == $service_id){
                    $service_history = $service_stat['history'];
                    $start_date = date_create(date('Y-m-d', strtotime($service_history[0]['start_date'])));
                    $current_date = date_create(date('Y-m-d'));
                    $interval = date_diff($start_date, $current_date);
                    $lapse_time =  (int) $interval->format('%a');
                    /* check lapse time as per the given service free period */
                    if ($lapse_time > (new services($service_id))->free_period){
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /* check if the current user has still in his billing product cycle */
    public static function has_selected_billing_cycle_due($user_services_stats, $service_id)
    {
        $result = null;
        if (is_array($user_services_stats)) {
            /* check matching service_id */
            foreach ($user_services_stats as $service_stat) {
                if ($service_stat['service_id'] == $service_id) {
                    /* get products for the given service */
                    $service_products = (new services(($service_id)))->products;
                    foreach($service_products as $product){
                        if ($service_stat['product_id'] == $product['product_id']){
                            /* get user current service history */
                            $h = $service_stat['history'];
                            $current_history = end($h);
                            $result = self::bill_due_date($current_history['start_date'], self::get_billing_type_days($product['cycle']));
                        }
                    }
                }
            }
        }
        return $result;
    }

    /* get current billing cycle value */
    private static function get_billing_type_days($billing_cyle_id)
    {
        $result = db::sql("SELECT days FROM `tl_billing_type` WHERE id = '$billing_cyle_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($days) = mysqli_fetch_array($result)){
                return (int) $days;
            }
        } return 0;
    }

    /* check if the current user has billing due to current product */
    public static function bill_due_date($start_date, $billing_cycle)
    {
        $current_day = (int) date('z');
        $start_day = (int) date('z', strtotime("$start_date"));
        /* for today subscription, no billing report will be received, user is already been billed */
        if ($start_day == $current_day) return false;

        while($start_day <= $current_day){
            if ($start_day == $current_day) return true;
            $start_day += $billing_cycle;
        } return false;
    }

    /* check if current trace is for notify */
    public static function has_notify_traced($trace_id)
    {
        $result = db::sql("SELECT service_id FROM `tl_datasync_notify` WHERE trace_id = '$trace_id'", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($service_id) = mysqli_fetch_array($result)){
                return $service_id;
            }
        } return null;
    }

    /***** users entries management *********/
    public static function entry_allocation($user_id, $service_id, $draw_type)
    {
        /* process entry for newly subscribe user or bonus in performance */
        $stat_id = self::get_service_stats_id($user_id, $service_id);
        /* allocate entry */
        if (is_null($stat_id)){
            return null;
        } else {
            return self::allocate_entry($stat_id, $draw_type);
        }
    }

    /* check if stat for the target service exists */
    private static function get_service_stats_id($user_id, $service_id)
    {
        $result = db::sql("SELECT `id` FROM `tl_users_stats` WHERE service_id = '$service_id' AND user_id = '$user_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id)=mysqli_fetch_array($result)){
                return $id;
            }
        } return null;
    }

    /* allocate entry */
    private static function allocate_entry($stat_id, $draw_type)
    {
        $current_entry = self::current_entry($stat_id, $draw_type);
        switch($draw_type){
            case 'D':
                return (db::sql("UPDATE `tl_users_stats` SET day_entries = '" . ($current_entry + 1) . "' WHERE id = '$stat_id';", DB_NAME) > 0) ? true : 2;
                break;
            case 'W':
                return (db::sql("UPDATE `tl_users_stats` SET week_entries = '" . ($current_entry + 1) . "' WHERE id = '$stat_id';", DB_NAME) > 0) ? true : null;
                break;
            case 'M':
                return (db::sql("UPDATE `tl_users_stats` SET month_entries = '" . ($current_entry + 1) . "' WHERE id = '$stat_id';", DB_NAME) > 0) ? true : null;
                break;
        }
        return null;
    }

    /* get current stat_id */
    private static function current_entry($stat_id, $type)
    {
        $result = $type == 'D' ? db::sql("SELECT day_entries FROM `tl_users_stats` WHERE id = '$stat_id';", DB_NAME)
            : ($type == 'W' ? db::sql("SELECT week_entries FROM `tl_users_stats` WHERE id = '$stat_id';", DB_NAME) :
                db::sql("SELECT month_entries FROM `tl_users_stats` WHERE id = '$stat_id';", DB_NAME));

        if (mysqli_num_rows($result)){
            while(list($current_entry) = mysqli_fetch_array($result)){
                return (int) $current_entry;
            }
        }
        return null;
    }

    /* reset current user entry into the draw */
    public static function reset_allocated_entries($user_id, $service_id)
    {
        return db::sql("UPDATE `tl_users_stats` SET day_entries = 0, week_entries = 0, month_entries = 0 WHERE user_id = '$user_id' AND service_id = '$service_id';", DB_NAME);
    }

    /* log current cross_sell */
    public static function log_cross_sell($cross_sell_name, $date_string, $service_id, $state=null)
    {
        return (is_null($state)) ?
            ( (db::sql("INSERT INTO `cross_sell_sync`(cross_sell, date_string, service_id, process_start) VALUES('$cross_sell_name', '$date_string', '$service_id', CURRENT_TIMESTAMP)", "tl_gateway") !== false) ? 1: 0):
            db::sql("UPDATE `cross_sell_sync` SET process_end = CURRENT_TIMESTAMP WHERE cross_sell = '$cross_sell_name' AND date_string = '$date_string' AND service_id = '$service_id'", "tl_gateway");
    }

    /* check if cross_sell completed */
    public static function has_completed_cross_sell($cross_sell_name, $date_string, $service_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `cross_sell_sync` WHERE cross_sell = '$cross_sell_name' AND date_string = '$date_string' AND service_id = '$service_id' AND process_start IS NOT NULL;", "tl_gateway"));
    }

    /* return current suspended users for the related service */
    public static function get_today_suspended_users($service_id)
    {
        return ((new services($service_id))->service_type == 1) ?
            mysqli_num_rows(db::sql("SELECT * FROM tl_servicing_contents WHERE suspended = 1 AND service_id = '$service_id'", "tl_datasync"))
            : mysqli_num_rows(db::sql("SELECT * FROM tl_datasync_trivia WHERE suspended = 1 AND service_id = '$service_id'", "tl_datasync"));
    }

    /* return current service playrate */
    public static function get_play_rate($service_local_id)
    {
        if ((new services($service_local_id))->service_type == 2){
            /* get current users that have received today trivia */
            $total_broadcasted = mysqli_num_rows(db::sql("SELECT DISTINCT user_id FROM tl_datasync_trivia", "tl_datasync"));
            if (is_int($total_broadcasted)){
                if ($total_broadcasted == 0) return number_format(0, 2);
                return number_format((self::play_rate_users($service_local_id) / $total_broadcasted) * 100, 2);
            }

        } return number_format(0, 2);
    }

    /* collect today users playrate list */
    private static function play_rate_users($service_local_id)
    {
        $result = db::sql("SELECT COUNT(user_id) AS play_rate FROM `tl_datasync_trivia` WHERE service_id = '$service_local_id' GROUP BY user_id;", "tl_datasync");
        $play_rate_users = 0;
        if (mysqli_num_rows($result)) {
            while (list($play_rate) = mysqli_fetch_array($result)) {
                if ((int)$play_rate > 1) ++$play_rate_users;
            }
        } return $play_rate_users;
    }


    /*************** all services operations task **************/

    public static function get_local_service_id($sdp_service_id)
    {
        $result = db::sql("SELECT service_local_id FROM `tl_services` WHERE service_sdp_id = '$sdp_service_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($service_local_id)=mysqli_fetch_array($result)){
                return (int) $service_local_id;
            }
        }
        return null;
    }

    public static function get_local_service_id_by_accessCode($sdp_accesscode)
    {
        $result = db::sql("SELECT service_local_id FROM `tl_services` WHERE accesscode = '$sdp_accesscode';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($service_local_id)=mysqli_fetch_array($result)){
                return (int) $service_local_id;
            }
        }
        return null;
    }

    public static function get_local_product_id($service_id, $sdp_product_id)
    {
        $result = db::sql("SELECT id FROM `tl_products` WHERE product_sdp_id = '$sdp_product_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($product_id) = mysqli_fetch_array($result)){
                /* check if the current product_id is associated to the current service */
                if (self::is_product_linked($service_id, $product_id)){
                    return $product_id;
                }
            }
        } return null;
    }

    /* check if the current product is linked to the current service */
    public static function is_product_linked($service_id, $product_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_services_products` WHERE product_id = '$product_id' AND service_local_id = '$service_id';", DB_NAME));
    }

    public static function get_sdp_service_id($service_local_id)
    {
        $result = db::sql("SELECT service_sdp_id FROM `tl_services` WHERE service_local_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($service_sdp_id)=mysqli_fetch_array($result)){
                return $service_sdp_id;
            }
        }
        return '27012000001111';
    }

    public static function get_sdp_product_id_by_billing_cycle($service_local_id, $billing_cycle_id)
    {
        $product_ids = (new services($service_local_id))->products;
        foreach($product_ids as $product){
            if ($product['cycle'] == $billing_cycle_id){
                return $product['product_sdp_id'];
            }
        } return '2701220000001669';
    }

    public static function get_sdp_product_id_by_product_id($service_product_list, $product_id)
    {
        if (is_array($service_product_list)){
            foreach($service_product_list as $product){
                if ($product['product_id'] == $product_id){
                    return $product['product_sdp_id'];
                }
            } return false;
        } else {
            return null;
        }
    }

    /* return service credentials if needed */
    public static function service_credentials($service_local_id)
    {
        $current_service = new services($service_local_id);
        $timestamp = date('YmdHis');
        $password = $current_service->sp_id . $current_service->sp_password . $timestamp;
        $hashedPassword = md5($password);
        return array('timestamp'=>$timestamp, 'hashedpassword'=>$hashedPassword);
    }

    /* log the last entry */
    public static function log_last_processID($last_entryID, $service_local_id)
    {
        return db::sql("UPDATE `tl_services` SET last_process_id = '$last_entryID' WHERE service_local_id = '$service_local_id';", DB_NAME);
    }

    /* prepare today content to be broadcast for current user
    on success : a data_list containing msisdn, tracer_id, content_id, message is returned */
    public static function sync_data($current_service, $user_id, $msisdn, $message=null)
    {
        $result = null;
        $last_brcast_id = null;
        /* check current service broadcast type */
        if ($current_service->broadcast_type == 1){
            $last_brcast_id = self::user_activity_stat_service_related($current_service->service_local_id, $user_id);
        }
        $data_list = is_null($last_brcast_id) ? self::fetch_content(null, $current_service, $message, $user_id)
            : self::fetch_content(self::has_content_related($current_service, $last_brcast_id), $current_service, $message, $user_id);
        if ($data_list){
            if (self::start_sync($data_list, $current_service->service_local_id, $user_id)){
                /* fetch tracer_id after data_sync creation */
                for($i = 0 ; $i < count($data_list); ++$i){
                    $tracer_id = self::fetch_tracer_id($user_id, $current_service->service_local_id, $data_list[$i][0]);
                    /* push current tracer_id and msisdn into current list */
                    array_unshift($data_list[$i], $tracer_id);
                    array_unshift($data_list[$i], $msisdn);
                }
                $result = $data_list;
            }
        }
        return $result;
    }


    /* get the last broadcast id for the related service */
    public static function user_activity_stat_service_related($service_local_id, $user_id)
    {
        $user = new user($user_id);
        $current_stats = $user->activity_stats;
        if (is_array($current_stats)){
            foreach($current_stats as $stat){
                if($stat['service_id'] == $service_local_id){
                    return (int) $stat['last_brcast_id'];
                }
            }
        }
        return null;
    }

    /* check current user has available content for service broadcast type 1, start_off_service */
    public static function is_content_service_available($current_service, $user_id)
    {
        $last_brcast_id = self::user_activity_stat_service_related($current_service->service_local_id, $user_id);
        if (is_null($last_brcast_id)){
            return null;
        } else {
            if (self::has_content_related($current_service, $last_brcast_id)){
                return true;
            } else {
                return null;
            }
        }
    }

    /* add users in skipped queued */
    public static function add_skipped_users($service_local_id, $user_id)
    {
        db::sql("INSERT INTO `tl_servicing_skipped` (service_id, user_id, date_created, `date`) VALUES('$service_local_id', '$user_id', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);", "tl_datasync");
    }
    /* remove users in skipped queued */
    public static function remove_skipped_users($service_local_id, $user_id)
    {
        $date = date('Y-m-d');
        db::sql("DELETE FROM `tl_servicing_skipped` WHERE service_id = '$service_local_id' AND user_id =  '$user_id' AND `date` = '$date';", "tl_datasync");
    }

    /* retry service manually for skipped users */
    public static function retry_service_skipped_users($service_local_id)
    {
        $current_service = new services($service_local_id);
        /* check currently running system threads */
        $running_threads = thread_ctrl::running_threads('threading_contents.php');

        if ($running_threads < $current_service->total_threads_allowed){

            $allocated_res = $current_service->total_threads_allowed - $running_threads;

            $users_list = user::fetch_active_skipped_subs($service_local_id, $allocated_res);

            /* set servicing users operations */
            $set_thread_users_operations = 'echo 0 ';

            /* start rolling out thread for the given subscribers */
            if ($users_list){
                /* check if subscribers haven't already been queued for today service */
                foreach($users_list as $key => $user_id){
                    if (thread_ctrl::has_queued($user_id, $service_local_id)){
                        unset($users_list[$key]);
                    }

                    /* check if the user is first_content_broadcast_queue */
                    if (thread_ctrl::has_first_content_queued($user_id, $service_local_id)){
                        unset($users_list[$key]);
                    }
                }

                /******* START THREADING *********/
                foreach($users_list as $user_id){
                    /* check for content broadcast type 1, if there's a content available for this user */
                    if ($current_service->broadcast_type == 1){
                        if (is_null(self::is_content_service_available($current_service, $user_id))){
                            continue;
                        }
                    }

                    /* log current thread for tracking operation time */
                    thread_ctrl::log($user_id, 0, $service_local_id);
                    /* wait a random number of sec before starting current thread */
                    $wait_period = rand(0, 5);
                    $set_thread_users_operations .= "& php threading_contents.php $user_id $service_local_id $wait_period";
                    /* remove the users from the skipped queues */
                    self::remove_skipped_users($service_local_id, $user_id);
                }
            }
            /* start thread */
            thread_ctrl::run(null, null, $set_thread_users_operations);
        }
    }

    /* start data sync | due to unique trace_id error,
    try 5 times to get a unique tracer_id
    data passed by reference to manage resources */
    public static function start_sync(&$data, $service_local_id, $user_id, $retry=null, $cross_sell_id=null)
    {
        self::$re_try = self::$re_try + 1;

        if ($retry){
            $tracer_id = thread_ctrl::get_unique_trace_id();
            switch((new services($service_local_id))->service_type){
                case 1:
                case 3:
                    if (!is_int(db::sql("INSERT INTO `tl_servicing_contents` (tracer_id, user_id, content_id, service_id, process_start, process_state, suspended)
                        VALUES('$tracer_id', '$user_id', '$data', '$service_local_id', CURRENT_TIMESTAMP, 0, 0)", "tl_datasync"))){
                        self::$sync_result = null;
                        if (self::$re_try < 6) {
                            self::start_sync($data, $service_local_id, $user_id, 1);
                        }
                    } else {
                        self::$sync_result = true;
                    }
                    break;
                case 2:
                    if (db::sql("INSERT INTO `tl_servicing_trivia` (tracer_id, user_id, trivia_id, service_id, cross_sell_id, process_start, process_state)
                        VALUES('$tracer_id', '$user_id', '$data', '$service_local_id', '$cross_sell_id', CURRENT_TIMESTAMP, 0)", "tl_datasync") === false){
                        /* insertion failed due to unique tracer_id |
                        attempt a recursion function call */
                        self::$sync_result = null;
                        if (self::$re_try < 6) {
                            self::start_sync($data, $service_local_id, $user_id, 1);
                        }
                    } else {
                        self::$sync_result = true;
                    }

                    break;
            }

        } else {
            for($i=0; $i < count($data); ++$i){
                $tracer_id = thread_ctrl::get_unique_trace_id();
                $content_id = $data[$i][0];
                $cross_sell_id = is_int($data[$i][1]) ? $data[$i][1] : 0;
                switch((new services($service_local_id))->service_type){
                    case 1:
                    case 3:
                        if (db::sql("INSERT INTO `tl_servicing_contents` (tracer_id, user_id, content_id, service_id, process_start, process_state, suspended)
                            VALUES('$tracer_id', '$user_id', '$content_id', '$service_local_id', CURRENT_TIMESTAMP, 0, 0)", "tl_datasync") === false){
                            /* insertion failed due to unique tracer_id |
                            attempt a recursion function call */
                            self::$sync_result = null;
                            self::start_sync($content_id, $service_local_id, $user_id, 1);

                        } else {
                            self::$sync_result = true;
                        }
                        break 1;
                    case 2:
                        if (db::sql("INSERT INTO `tl_servicing_trivia` (tracer_id, user_id, trivia_id, service_id, cross_sell_id, process_start, process_state)
                        VALUES('$tracer_id', '$user_id', '$content_id', '$service_local_id', '$cross_sell_id', CURRENT_TIMESTAMP, 0)", "tl_datasync") === false){
                            /* insertion failed due to unique tracer_id |
                            attempt a recursion function call */
                            self::$sync_result = null;
                            self::start_sync($content_id, $service_local_id, $user_id, 1, $cross_sell_id);

                        } else {
                            self::$sync_result = true;
                        }
                        break 1;
                }
            }
        }

        return self::$sync_result;
    }

    /* Update current broadcast operation */
    public static function update_data_sync($tracer_id, $user_id, $current_service, $state, $trivia_id=null, $cross_sell_id=null, $user_answer=null)
    {
        switch($current_service->service_type){
            case 1:
            case 3:
                return db::sql("UPDATE `tl_servicing_contents` SET process_end = CURRENT_TIMESTAMP , process_state = '$state'
                WHERE tracer_id = '$tracer_id' AND user_id = '$user_id' AND service_id = '$current_service->service_local_id'", "tl_datasync");
                break;
            case 2:
                if (is_null($trivia_id)){
                    return db::sql("UPDATE `tl_servicing_trivia` SET process_end = CURRENT_TIMESTAMP , process_state = '$state'
                    WHERE tracer_id = '$tracer_id' AND user_id = '$user_id' AND service_id = '$current_service->service_local_id'", "tl_datasync");
                } else {
                    /* process cross_sell request */
                    $score = ($trivia_id == 0) ? (
                        (self::process_cross_sell_request(trim($user_answer), $user_id, $cross_sell_id) === true) ? 'correct' : 'incorrect'
                    ) : (
                        (self::score_user_answer($trivia_id, trim(strtoupper($user_answer)), $user_id, $current_service->service_local_id)) ? 'correct' : 'incorrect'
                    );
                    if (db::sql("UPDATE `tl_servicing_trivia` SET user_answer = '$user_answer' , score = '$score', process_end = CURRENT_TIMESTAMP , process_state = '$state'
                WHERE tracer_id = '$tracer_id' AND user_id = '$user_id' AND service_id = '$current_service->service_local_id'", "tl_datasync")){
                        return $score;
                    } else {
                        return null;
                    }
                }
                break;
            default:
                return null;
                break;
        }
    }

    /* process user cross_sell_request */
    private static function process_cross_sell_request($user_answer, $user_id, $cross_sell_id)
    {
        if (in_array(strtolower($user_answer), array('a', 'yes', 'y', 'more', 'ok', 'yeah'))){
            /* trigger subscription request */
            user::request_service_related('0'. substr(user::get_user_msisdn($user_id), 2), 'activation', $cross_sell_id);
            return true;
        } else {
            return false;
        }
    }

    /* Score current user question */
    private static function score_user_answer($trivia_id, $user_answer, $user_id, $service_local_id)
    {

        $result = db::sql("SELECT `answer`, `score` FROM `tl_service_trivia` WHERE id = '$trivia_id'", DB_NAME);
        $score = null;
        if (mysqli_num_rows($result)){
            while(list($answer, $points) = mysqli_fetch_array($result)){
                if ($answer == strtoupper($user_answer)){
                    $score = true;
                    /* update user overall score */
                    self::update_service_score($user_id, $points, $service_local_id);
                }
            }
        }
        return $score;
    }

    /* update user overall score, user stats */
    private static function update_service_score($user_id, $points, $service_local_id)
    {
        /* get current points */
        $overall_points = user::service_points_related((new user($user_id))->activity_stats, $service_local_id) + (int)$points;
        db::sql("UPDATE `tl_users_stats` SET `score` = '$overall_points' WHERE `user_id` = '$user_id' AND service_id = '$service_local_id'", DB_NAME);
    }

    /* retrieve tracer_id for broadcast operation */
    public static function fetch_tracer_id($user_id, $service_local_id, $content_id)
    {
        $result = null;
        switch((new services($service_local_id))->service_type){
            case 1:
            case 3:
                $result = db::sql("SELECT `tracer_id` FROM `tl_servicing_contents`
                WHERE `user_id` = '$user_id' AND `content_id` = '$content_id' AND service_id = '$service_local_id' ORDER BY process_start DESC LIMIT 1", "tl_datasync");
                break;
            case 2:
                $result = db::sql("SELECT `tracer_id` FROM `tl_servicing_trivia`
                WHERE `user_id` = '$user_id' AND `trivia_id` = '$content_id' AND service_id = '$service_local_id'", "tl_datasync");
                break;
        }
        if ($result){
            if (mysqli_num_rows($result)){
                while(list($tracer) = mysqli_fetch_array($result)){
                    return $tracer;
                }
            }
        }

        return null;
    }

    /*check if current user has any pending service content */
    public static function has_pending_content($user_id, $current_service)
    {
        $result = ($current_service->service_type == 1) ?
            db::sql("SELECT `tracer_id`, `content_id`, service_id FROM `tl_servicing_contents` WHERE `user_id` = '$user_id' AND process_state IN (3, 0) AND service_id = '$current_service->service_local_id'", "tl_datasync"):
            db::sql("SELECT `tracer_id`, `trivia_id`, cross_sell_id FROM `tl_servicing_trivia` WHERE `user_id` = '$user_id' AND process_state = 2 AND service_id = '$current_service->service_local_id'", "tl_datasync");;
        if (mysqli_num_rows($result)){
            while(list($tracer_id, $related_content_id, $cross_sell_id) = mysqli_fetch_array($result)){
                /* check if the related contents service id */
                if (self::is_content_service_related($related_content_id, $current_service)){
                    return $current_service->service_type == 1 ? true : array('tracer_id'=>$tracer_id, 'trivia_id'=>(int) $related_content_id, 'cross_sell_id'=>(int) $cross_sell_id);
                } else {
                    /* for trivia service with cross sell enabled, return data list */
                    return $current_service->service_type == 1 ? null : (
                        $current_service->cross_sell_services == 1 ?  array('tracer_id'=>$tracer_id, 'trivia_id'=>(int) $related_content_id, 'cross_sell_id'=>(int) $cross_sell_id) : null
                    );
                }
            }
        }
        return null;
    }

    /* check if current user has in progress datasync */
    public static function has_progress_datasync($user_id, $service_local_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_datasync_trivia` WHERE service_id = '$service_local_id' AND user_id = '$user_id' AND delivered = 0", "tl_datasync"));
    }

    public static function is_content_service_related($content_id, $current_service)
    {
        return $current_service->service_type == 1 ?
            mysqli_num_rows(db::sql("SELECT * FROM `tl_services_contents` WHERE id = '$content_id' AND service_id = '$current_service->service_local_id';", DB_NAME)):
            mysqli_num_rows(db::sql("SELECT * FROM `tl_service_trivia` WHERE id = '$content_id' AND service_id = '$current_service->service_local_id';", DB_NAME));
    }

    /* check service availability */
    public static function service_availability($current_service)
    {
        $date = date('Y-m-d');
        return $current_service->service_type == 1 ?
            mysqli_num_rows(db::sql("SELECT * FROM `tl_services_contents` WHERE service_id = '$current_service->service_local_id' AND `date_created` LIKE '$date%' LIMIT 1", DB_NAME)):
            mysqli_num_rows(db::sql("SELECT * FROM `tl_service_trivia` WHERE service_id = '$current_service->service_local_id' AND `date_created` LIKE '$date%' LIMIT 1", DB_NAME));
    }

    /* servicing operation thread on user subscription */
    public static function start_broadcast_queued($current_service, $user_id, $user_msisdn, $gwtype, $gwparams, $current_score=null, $related_content=null)
    {
        global $loggerObj;
        /* track if the user content has been processed  */
        $result = null;
        $broadcast_settings = settings::get_broadcast_scheduled_set(0);

        /*  check if the requested service has started */
        if (thread_ctrl::has_queued_started(null, $current_service->service_local_id)){
            /* service has started */
            /* fetch content that fails and still being in a queue */
            $data_list = self::fetch_queued_data($current_service, $user_id, $user_msisdn);

            if ($data_list){
                /* for service type trivia we forward notify and prepare data_sync */
                if ($current_service->service_type == 2){

                    $notify_message = (isset($related_content['trivia_id']) && $related_content['trivia_id'] == 0) ?
                        services::fetch_notify_related_message($related_content['cross_sell_id'], $related_content['trivia_id'], $current_score):
                        services::fetch_notify_related_message($current_service->service_local_id, isset($related_content['trivia_id']) ? $related_content['trivia_id'] : null , $current_score);

                    if ($notify_message){
                        if (self::prepare_datasync_trivia($data_list, $current_service->service_local_id, rand(0, 1))) {
                            $result = self::process_broadcast_request($data_list, $user_id, $current_service, $gwparams, $gwtype, $current_score, $notify_message);
                        }
                    }else {
                        /* no_previous_q or next q still in the data-sync queue */
                        $data_list = self::fetch_queued_data($current_service, $user_id, $user_msisdn, $related_content);
                        $result = self::process_broadcast_request($data_list, $user_id, $current_service, $gwparams, $gwtype);
                    }
                } else {
                    $result = self::process_broadcast_request($data_list, $user_id, $current_service, $gwparams, $gwtype);
                }
            } else {
                /* no data found, these are possible scenario:
                 * user is replying while reaching a daily quota : current_score is null : log a closing message
                 * user answers the last question: send a notify and log a closing message points
                */
                if ($current_service->service_type == 2){
                    if ($current_score){
                        /* closing points */
                        $notify_message = (isset($related_content['trivia_id']) && $related_content['trivia_id'] == 0) ?
                            services::fetch_notify_related_message($related_content['cross_sell_id'], $related_content['trivia_id'], $current_score):
                            services::fetch_notify_related_message($current_service->service_local_id, $related_content['trivia_id'], $current_score);

                        $result = self::process_broadcast_request($data_list, $user_id, $current_service, $gwparams, $gwtype, $current_score, $notify_message);

                        /* log the closing message */
                        self::log_closing_message($current_service->service_local_id, $user_msisdn, $related_content['trivia_id'], 'closing_points');
                    } else {
                        /* closing notify if no yet */
                        if (self::has_closing_message($current_service->service_local_id, $user_msisdn, 'closing') == 0){
                            self::log_closing_message($current_service->service_local_id, $user_msisdn, 0, 'closing');
                        }
                    }
                }
            }
        } else if (thread_ctrl::has_queued_started(null, $current_service->service_local_id) == 0) {
            /* service hasn't started */
            $result = self::process_broadcast_request(array($user_msisdn, thread_ctrl::get_unique_trace_id(), null, "We know you can't wait to play! Stay tuned, your $current_service->name trivia will be sent shortly ..."),
                $user_id, $current_service, $gwparams, $gwtype);

        } else if (date('H:i:s') > $broadcast_settings['close_time']){
            /* the service has closed */
            $result = self::process_broadcast_request(array($user_msisdn, thread_ctrl::get_unique_trace_id(), null, "We know you can't wait to play! Stay tuned for more chances to win."),
                $user_id, $current_service, $gwparams, $gwtype);
        }

        return $result;
    }

    public static function process_broadcast_request($data_list, $user_id, $current_service, $gwparams, $gwtype, $current_score=null, $notify_message=null)
    {
        global $loggerObj;
        /* reporting purposed */
        $processed = array('result'=>null, 'tracer_id'=>$data_list[1], 'sent'=>0, 'error'=>null);

        if ($current_service->service_type == 2){
            if ($notify_message){
                /* if data_list is NULL on closing create required variables for processing */
                if (is_null($data_list)){
                    $data_list = array(user::get_user_msisdn($user_id), null, 0, null);
                }
                /* update current trace for notify */
                $data_list[1] = thread_ctrl::get_unique_trace_id();

                if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
                    $notify_message = $current_service->name . ': '. $notify_message;
                }

                /* attach notify message */
                $data_list[3] = $notify_message;

                /* log current notify to trace report */
                services::prepare_datasync_notify($data_list, $current_score, null, null, rand(0, 1), $current_service->service_local_id, true);

                /* log current process has started */
                //services::log_notify_thread_started($data_list[1]);

                $processed = array('result'=>null, 'notify'=>$data_list[3], 'sent'=>0);
            } else {
                /* send datasync on retry or awaiting on the queue */
                if ( (strlen($current_service->name) + strlen($data_list[3])) < 159){
                    $data_list[3] = $current_service->name . ': '. $data_list[3];
                }

                $processed = array('result'=>null, 'data'=>$data_list[3], 'sent'=>0);
            }
        }

        /* push gwparams to the data */
        array_push($data_list, $gwparams);

        /* create a json object */
        $jsonData = json_encode($data_list, true);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $jsonData
            )
        );
        $context  = stream_context_create($opts);
        /* determine incoming gateway type */
        $response = ($gwtype == 'sms')? file_get_contents('http://localhost'.DEFAULT_PORT.'/gateway/sms/?service_type=content', false, $context):
            file_get_contents('http://localhost'.DEFAULT_PORT.'/gateway/ussd/?service_type=content', false, $context);

        /* process | Update current broadcast operation result in datasync */
        $response = json_decode($response, true);

        /* result | success is automatic demo with no user interaction */
        if ($response['result'] == 'success'){
            if (in_array($current_service->service_type, array(1, 3))){
                if (self::update_data_sync($data_list[1], $user_id, $current_service, 1)){
                    $loggerObj->LogInfo('tracer_id:'.$data_list[1].' | '. $response['message']);
                    /* update user stat for the current broadcast */
                    services::log_user_service_broadcast($user_id, $current_service->service_local_id, $data_list[2]);
                }
            } else {
                if ($notify_message){
                    if (services::prepare_datasync_notify($data_list, null, true, null, null, $current_service->service_local_id)) {
                        $loggerObj->LogInfo("Notify Sent successfully !");
                    }
                }else {
                    /* update servicing trivia state */
                    if (self::update_data_sync($data_list[1], $user_id, $current_service, 2)){
                        $loggerObj->LogInfo('tracer_id:'.$data_list[1].' | '. $response['message']);
                    }

                    /* check current datasync trivia in order to update user stat */
                    if (self::current_datasync_trivia_length($current_service->service_local_id, $user_id) == 1){
                        $last_brdcast_id = self::last_datasync_trivia_id($current_service->service_local_id, $user_id);
                        if ($last_brdcast_id){
                            services::log_user_service_broadcast($user_id, $current_service->service_local_id, $last_brdcast_id);
                        }
                    }

                    /* update trivia datasync success sent */
                    if (services::update_datasync_trivia($current_service->service_local_id, $user_id, $data_list[2])){
                        $loggerObj->LogInfo("Successfully updated datasync for MSISDN | ". user::get_user_msisdn($user_id)." | trivia_id: $data_list[2]");
                    } else {
                        $loggerObj->LogInfo("Error updating datasync for MSISDN | ". user::get_user_msisdn($user_id)." | trivia_id: $data_list[2]");
                        services::log_datasync_thread_started($data_list[1], true);
                    }
                }
            }

            $processed['sent'] = 1;
            $processed['result'] = true;
        } else {
            if (in_array($current_service->service_type, array(1, 3))){
                /* SDP communication failure */
                if (self::update_data_sync($data_list[1], $user_id, $current_service, 3)){
                    $loggerObj->LogInfo('tracer_id:'.$data_list[1].' | '. $response['message']);
                }
            } else {
                if ($notify_message){
                    $loggerObj->LogInfo('tracer_id :'. $data_list[1]. ' | '. $response['message']);
                    /* log current process has ended */
                    services::log_notify_thread_started($data_list[1], true);
                } else {
                    if (self::update_data_sync($data_list[1], $user_id, $current_service, 3)){
                        $loggerObj->LogInfo('tracer_id:'.$data_list[1].' | '. $response['message']);
                    }
                    services::log_datasync_thread_started($data_list[1], true);
                }
            }
            $processed['error'] = $response['message'];
        }

        return $processed;
    }

    /* get current datasync queue for current service and user */
    private static function current_datasync_trivia_length($service_local_id, $user_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_datasync_trivia` WHERE user_id = '$user_id' AND service_id = '$service_local_id';", "tl_datasync"));
    }

    private static function last_datasync_trivia_id($service_local_id, $user_id)
    {
        $result = db::sql("SELECT MAX(trivia_id) FROM `tl_servicing_trivia` WHERE user_id = '$user_id' AND service_id = '$service_local_id';", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($last_brdcast_id) = mysqli_fetch_array($result)){
                return (int) $last_brdcast_id;
            }
        } return null;
    }


    /* retrieve content in queued operation, content that failed to a network error or still being in a queue */
    private static function fetch_queued_data($current_service, $user_id, $msisdn, $tracer_id=null)
    {
        /* instantiate current service */

        $result = $current_service->service_type == 1 ?
            db::sql("SELECT `tracer_id`, `content_id`, service_id FROM `tl_servicing_contents` WHERE `user_id` = '$user_id'
                  AND service_id = '$current_service->service_local_id' AND `process_state` IN (3,0)", "tl_datasync") :
            (
                (is_null($tracer_id)) ?
                    db::sql("SELECT `tracer_id`, `trivia_id`, cross_sell_id FROM `tl_servicing_trivia` WHERE `user_id` = '$user_id'
                  AND `process_state` IN (3,0) AND service_id = '$current_service->service_local_id' ORDER BY `process_state` DESC LIMIT 1", "tl_datasync") :
                    db::sql("SELECT `tracer_id`, `trivia_id`, cross_sell_id FROM `tl_servicing_trivia` WHERE tracer_id = '$tracer_id' AND service_id = '$current_service->service_local_id'", "tl_datasync")
            );
        $queued_content = null;
        $cross_sell_id_tmp = null;
        if (mysqli_num_rows($result)){
            while(list($tracer_id, $content_id, $cross_sell_id) = mysqli_fetch_array($result)){
                $queued_content = array($msisdn, $tracer_id, $content_id);
                $cross_sell_id_tmp = $cross_sell_id;
            }
        }
        /* fetch the content for current content_id */
        if ($queued_content){
            $content = self::fetch_content($queued_content[2], $current_service, null, null, $cross_sell_id_tmp);
            if ($content){
                array_push($queued_content, (is_array($content[0])) ? $content[0][1] : $content[0]);
            }
        }
        return $queued_content;
    }

    /* return content
        return contents service, trivia, a related cross_sell message
    */
    public static function fetch_content($content_id=null, $current_service, $message=null, $user_id=null, $cross_sell_service_id=null, $trivia_date_target=null)
    {
        $result = null;
        $content = null;
        $date = is_null($trivia_date_target) ? date('Y-m-d') : $trivia_date_target;
        /* determine a service type scenario */
        switch($current_service->service_type){
            case 1: //service content
                $result = (is_null($content_id)) ?
                    db::sql("SELECT `id`, `message` FROM `tl_services_contents` WHERE `status` = 1 AND service_id = '$current_service->service_local_id' AND date_created LIKE '$date%' LIMIT 1", DB_NAME):
                    db::sql("SELECT `id`, `message` FROM `tl_services_contents` WHERE `id` = '$content_id'", DB_NAME);

                if (mysqli_num_rows($result)){
                    while(list($id, $message) = mysqli_fetch_array($result)){
                        $content[] = array($id, $message);
                    }
                }
                break;
            case 2: // service trivia
                if (is_null($content_id)){
                    $result = db::sql("SELECT `id`, `question` FROM `tl_service_trivia` WHERE `status` = 1 AND service_id = '$current_service->service_local_id' AND date_created LIKE '$date%'", DB_NAME);
                    if (mysqli_num_rows($result)) {
                        while (list($id, $question,) = mysqli_fetch_array($result)) {
                            $content[] = array($id, $question);
                        }
                        /* randomise currently added contents and limit to the defined service broadcast length */
                        shuffle($content);
                        foreach ($content as $key => $value) {
                            if ($key > ($current_service->broadcast_length - 1)) {
                                unset($content[$key]);
                            }
                        }
                        /* check cross sell is enable for the current service */
                        if ($current_service->cross_sell_services){
                            $content = self::add_cross_sell_services($user_id, $content, $current_service, $message);
                        }
                    } else {
                        /* proceed with cross_sell as enabled */
                        if ($current_service->cross_sell_services){
                            $content = self::add_cross_sell_services($user_id, $content, $current_service, $message);
                        }
                    }
                } else {
                    /* check the content_id is a type date format in order to fetch related content for service trivia broadcast type 1
                    during automation broadcast */
                    if (strpos($content_id, '-') !== false){
                        return self::fetch_content(null, $current_service, $message, $user_id, $cross_sell_service_id, $content_id);
                    } else {
                        /* return the question and the score */
                        $result = db::sql("SELECT `question`, `score` FROM `tl_service_trivia` WHERE `status` = 1 AND service_id = '$current_service->service_local_id' AND `id` = '$content_id'", DB_NAME);
                        if (mysqli_num_rows($result)){
                            while(list($q, $score) = mysqli_fetch_array($result)){
                                $content = array($q, $score);
                            }
                        } else {
                            /* a cross sell trivia 0 has been passed, return 0 pts and its cross_sell message */
                            $related_message = service_messages::fetch_related_messages($cross_sell_service_id, 7);
                            $content = array($related_message['message'], 0);
                        }
                    }
                }

                break;
        }

        return $content;
    }


    /* add a cross sell question for non subscribed service */
    private static function add_cross_sell_services($user_id, $data, $current_service, $message)
    {
        $res = null;
        /* check related services for the current user for a cross_sell */
         $cross_sell_service_id = self::select_cross_sell_service($user_id, $current_service->service_local_id);

        if ($cross_sell_service_id){
            /* message is null in automated broadcast.
            check if cross_sell upon subscription is required for the target service
            update: check if cross_sell on first interaction is required for the current service on automated broadcast
            */
            $pos = is_null($message) ? ( $current_service->cross_sell_on_first == 1 ? 0 : rand(1, ($current_service->broadcast_length - 1))) :
                ($current_service->cross_sell_on_sub == 1 ? 0 : rand(1, ($current_service->broadcast_length - 1)));
            if ($data){
                foreach($data as $key => $ls){
                    if ($key == $pos){
                        $res[] = array(0, $cross_sell_service_id);
                    }
                    $res[] = $ls;
                }
            } else {
                if ($current_service->cross_sell_on_sub){
                    $res[] = array(0, $cross_sell_service_id);
                }
            }

        }
        return (is_null($res)) ? $data : $res;
    }

    /* get a random cross_sell_service for the given subscriber */
    private static function select_cross_sell_service($user_id, $service_local_id)
    {
        $current_user = new user($user_id);
        $related_cross_sells = service_messages::get_cross_sell_services($service_local_id);
        $service_related = null;
        if ($related_cross_sells){
            foreach($related_cross_sells as $service_id){
                if (!user::has_current_subscription($current_user->services_stats, $service_id)){
                    $service_related[] = $service_id;
                }
            }
            /* shuffle related service and return one service */
            if ($service_related){
                shuffle($service_related);
                return (int) $service_related[0];
            }
        }
        return $service_related;
    }


    /* return the next content_id for content_type service if required arg (last_brcast_id) and date_created for
    trivia_type service is present or else simply check if there's any contents created */
    public static function has_content_related($current_service, $last_brcast_id=null)
    {
        $result = $current_service->service_type == 1 ?
            (
                is_null($last_brcast_id) ?
                db::sql("SELECT  id FROM `tl_services_contents` WHERE service_id = '$current_service->service_local_id'", DB_NAME):
                db::sql("SELECT  id FROM `tl_services_contents` WHERE service_id = '$current_service->service_local_id' AND id > '$last_brcast_id' ORDER BY id ASC LIMIT 1;", DB_NAME)
            ) :
            (
                is_null($last_brcast_id) ?
                db::sql("SELECT  id FROM `tl_service_trivia` WHERE service_id = '$current_service->service_local_id'", DB_NAME):
                    /* for the trivia type content, we retrieve the date _created of the last broadcast id */
                db::sql("SELECT date_created FROM `tl_service_trivia` WHERE service_id = '$current_service->service_local_id' AND id > '$last_brcast_id' ORDER BY id ASC LIMIT 1;", DB_NAME)
            );

        if (mysqli_num_rows($result)){
            if (is_null($last_brcast_id)){
                return true;
            }
            while(list($related_type) = mysqli_fetch_array($result)){
                return ($current_service->service_type == 1) ? $related_type : date('Y-m-d', strtotime("$related_type"));
            }
        }
        return null;
    }

    public static function get_service_opening_message($current_service, $user_id, $msisdn)
    {
        /* check current user performance
            @val_history: yesterday, never, first, last play date
      */
        $selected_opening = null;
        $activity = self::user_recent_score($current_service, $user_id);
        $points = (int)$activity['points'];

        switch($activity['history']){
            case 'first':
                $related_message = service_messages::fetch_related_messages($current_service->service_local_id, 1);
                $selected_opening = $related_message['message'];
                break;
            case 'yesterday':
                /* check user performance */
                $average_pts = (int) ($current_service->broadcast_length * 10) / 2;
                $message_type_id = $points == 0 ? 3 : ($points < $average_pts ? 2 : 6);
//                $message_type_id = ($points < 15) ? 3 : (($points > 15 && $points < 25) ? 2 : 6);
                $related_message = service_messages::fetch_related_messages($current_service->service_local_id, $message_type_id);
                $selected_opening = $msisdn.', '. $related_message['message'];
                break;
            case 'never':
                $related_message = service_messages::fetch_related_messages($current_service->service_local_id, 5);
                $selected_opening = $msisdn.', '. $related_message['message'];
                break;
            default:
                $related_message = service_messages::fetch_related_messages($current_service->service_local_id, 4);
                $selected_opening = $msisdn.', '. $related_message['message'];
                break;
        }
        return $selected_opening;
    }


    /* return user yesterday points
    @return array(user_current_points, history)
    @val_history: yesterday, never, first, last play date
    */
    public static function user_recent_score($current_service, $user_id, $today=null)
    {
        $current_user = new user($user_id);

        $date = (is_null($today)) ? date('Y-m-d', strtotime("yesterday")) : date('Y-m-d');
        $result = db::sql("SELECT score, trivia_id FROM `tl_servicing_trivia` WHERE user_id = '$user_id' AND process_start LIKE '$date%' AND user_answer IS NOT NULL AND service_id = '$current_service->service_local_id';", "tl_datasync_archives");
        $points = 0;
        $yesterday = null;
        $never = null;
        $process_date_tracker = null;
        if (mysqli_num_rows($result)){
            $yesterday = true;
            while(list($score, $trivia_id) = mysqli_fetch_array($result)){
                /* get score for the given trivia_id*/
                $q = self::fetch_content($trivia_id, $current_service);
                $points += ($score == 'correct') ? $q[1] : 0;
            }
        } else {
            /* recent score for the related date */
            $result = db::sql("SELECT score, trivia_id, process_end FROM `tl_servicing_trivia` WHERE user_id = '$user_id' AND user_answer IS NOT NULL AND service_id = '$current_service->service_local_id' ORDER BY process_end DESC LIMIT $current_service->broadcast_length;", "tl_datasync_archives");
            if (mysqli_num_rows($result)){
                while(list($score, $trivia_id, $process_date) = mysqli_fetch_array($result)){
                    if (is_null($process_date_tracker)){
                        $process_date_tracker = date('Y-m-d', strtotime($process_date));
                    } else {
                        if ($process_date_tracker !== date('Y-m-d', strtotime($process_date))){
                            break;
                        }
                    }
                    /* get score for the given trivia_id*/
                    $q = self::fetch_content($trivia_id, $current_service);
                    $points += ($score == 'correct') ? $q[1] : 0;
                }
            } else {
               /* check if a first time subscriber to current service */
                $user_service_histories = user::service_history_related($current_user->services_stats, $current_service->service_local_id);
                $service_history = (is_array($user_service_histories)) ? $user_service_histories[0] : null;
                if ($service_history){
                    if ( date('Y-m-d', strtotime($service_history['start_date'])) == date('Y-m-d') ){
                        $never = false;
                    } else {
                        $never = true;
                    }
                }
            }
        }
        return array('points'=>$points, 'history'=> (($yesterday == true) ? ( (is_null($today)) ? 'yesterday' : 'today') : ( ($never === true) ? 'never': (($never === false) ? 'first' : date('M d', strtotime($process_date_tracker))) ) ));
    }

    /* fetch notify related message */
    public static function fetch_notify_related_message($service_local_id, $trivia_id, $score)
    {
        /* for cross_sell trivia we return true */
        if ($trivia_id == '0'){
            $related_message = service_messages::fetch_related_messages($service_local_id, 7);
            return ($score == 'correct') ? $related_message['correct']: $related_message['incorrect'];
        }

        $result = db::sql("SELECT correct_notify, incorrect_notify FROM `tl_service_trivia` WHERE id = '$trivia_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($correct, $incorrect) = mysqli_fetch_array($result)){
                return ($score == 'correct') ? $correct : $incorrect;
            }
        }
        return null;
    }

    /******* service trivia datasync ********/
    /* prepare datasync
    @trivia_id: will help to check before sending the next q if notify for the previous was sent
    */
    public static function prepare_datasync_trivia($data_list, $service_id, $sleep)
    {
        $result = db::sql("INSERT INTO tl_datasync_trivia (msisdn, trace_id, trivia_id, user_id, sleep, sent, date_created, service_id, delivered)
        VALUES('$data_list[0]', '$data_list[1]', '$data_list[2]', '". user::get_user_id($data_list[0])."', '$sleep', 0, CURRENT_TIMESTAMP, '$service_id', 0)", "tl_datasync");
        return ($result == 0) ? true : null;
    }

    /* prepare notify_queue */
    public static function prepare_datasync_notify($data_list=null, $score=null, $update=null, $state=null, $sleep=null, $service_id=null, $start_process=null)
    {
        $sleep = (is_null($sleep)) ? 0 : $sleep;
        if (is_null($update)){
            return  is_null($start_process) ?
                db::sql("INSERT INTO tl_datasync_notify (msisdn, trace_id, trivia_id, score, sent, delivered, date_created, sleep, suspended, service_id, user_id)
        VALUES('$data_list[0]', '$data_list[1]', '$data_list[2]', '$score', 0, 0, CURRENT_TIMESTAMP, '$sleep', 0, '$service_id', '". user::get_user_id($data_list[0])."')", "tl_datasync"):
                db::sql("INSERT INTO tl_datasync_notify (msisdn, trace_id, trivia_id, score, sent, delivered, date_created, sleep, suspended, service_id, user_id, process_start)
        VALUES('$data_list[0]', '$data_list[1]', '$data_list[2]', '$score', 0, 0, CURRENT_TIMESTAMP, '$sleep', 0, '$service_id', '". user::get_user_id($data_list[0])."', CURRENT_TIMESTAMP)", "tl_datasync");
        } else {
            return (is_null($state)) ? db::sql("UPDATE tl_datasync_notify SET sent = 1, process_end = CURRENT_TIMESTAMP WHERE trace_id = '$data_list[1]' AND service_id = '$service_id'", "tl_datasync"):
                db::sql("UPDATE tl_datasync_notify SET sent = '$state', delivered = '$state', process_start = CURRENT_TIMESTAMP, process_end = CURRENT_TIMESTAMP WHERE trace_id = '$data_list' AND service_id = '$service_id'", "tl_datasync");
        }
    }

    /* check still processing thread */
    public static function log_notify_thread_started($trace_id, $update=null, $start=0)
    {
        return (is_null($update)) ?
            db::sql("UPDATE `tl_datasync_notify` SET process_start = CURRENT_TIMESTAMP WHERE trace_id = '$trace_id'", "tl_datasync"):
            db::sql("UPDATE `tl_datasync_notify` SET process_end = CURRENT_TIMESTAMP, `start` = '$start' WHERE trace_id = '$trace_id'", "tl_datasync");
    }

    /* log points allocated on notify */
    public static function has_notify_started($service_local_id, $user_id, $trivia_id)
    {
       return mysqli_num_rows(db::sql("SELECT * FROM `tl_datasync_notify` WHERE user_id = '$user_id' AND trivia_id = '$trivia_id' AND service_id = '$service_local_id' AND process_end IS NULL", "tl_datasync"));
    }


    /* update current user last content broadcasted for the related service */
    public static function log_user_service_broadcast($user_id, $service_local_id, $brcast_id)
    {
        return db::sql("UPDATE `tl_users_stats` SET last_brcast_id = '$brcast_id' WHERE user_id = '$user_id' AND service_id = '$service_local_id';", DB_NAME);
    }

    /* collect all today notifies in the queues */
    public static function get_data_notifies($service_local_id, $retries=null)
    {
        $result = (is_null($retries)) ?
            db::sql("SELECT DISTINCT user_id FROM `tl_datasync_notify` WHERE service_id = '$service_local_id' AND sent = 0 AND suspended = 0 AND process_start IS NULL ORDER BY date_created ASC", "tl_datasync"):
            db::sql("SELECT DISTINCT user_id FROM `tl_datasync_notify` WHERE sent = 0 AND service_id = '$service_local_id' AND process_end IS NOT NULL ORDER BY date_created ASC", "tl_datasync");
        $users = null;
        if (mysqli_num_rows($result)){
            while(list($user_id) = mysqli_fetch_array($result)){
                $users[] = $user_id;
            }
        }
        return $users;
    }

    /* collect queued notifications for current user */
    public static function get_data_notify($service_local_id, $user_id=null)
    {
        /* ordering randomly */
        $res = rand(0, 3); $orderby = $res == 0 ? 'ORDER BY date_created ASC LIMIT 100' : 'ORDER BY date_created DESC LIMIT 100';

        $result = is_null($user_id) ?
            db::sql("SELECT user_id, trace_id, trivia_id, score, sleep, msisdn FROM `tl_datasync_notify` WHERE sent = 0 AND suspended = 0 AND service_id = '$service_local_id' ". $orderby, "tl_datasync"):
            db::sql("SELECT user_id, trace_id, trivia_id, score, sleep, msisdn FROM `tl_datasync_notify` WHERE user_id = '$user_id' AND sent = 0 AND suspended = 0 AND service_id = '$service_local_id' ORDER BY date_created ASC LIMIT 1;", "tl_datasync");
        $data_notify = null;
        if (mysqli_num_rows($result)){
            while(list($user_id, $trace_id, $trivia_id, $score, $sleep, $msisdn) = mysqli_fetch_array($result)){
                $data_notify[] = array('user_id'=>$user_id, 'trace_id'=>$trace_id, 'trivia_id'=>$trivia_id, 'score'=>$score, 'sleep'=>$sleep, 'msisdn'=>$msisdn);
            }
        }
        return $data_notify;
    }

    /* update suspension on notify_queue so resources are not locked by suspended users */
    public static function update_suspended_notify($msisdn, $state, $service_local_id)
    {
        return db::sql("UPDATE `tl_datasync_notify` SET suspended = '$state' WHERE msisdn = '$msisdn' AND service_id = '$service_local_id'", "tl_datasync");
    }

    /* get queuing users first content */
    public static function get_data_contents_users($service_local_id, $retries=null, $unsuspended=null)
    {
        $result = (is_null($retries)) ?
            db::sql("SELECT trace_id, user_id, sleep, trivia_id FROM `tl_datasync_trivia` WHERE service_id = '$service_local_id' AND sent = 0 AND suspended = 0 AND process_start IS NULL ORDER BY date_created DESC", "tl_datasync"):
            (
                (is_null($unsuspended)) ?
                db::sql("SELECT trace_id, user_id, sleep, trivia_id FROM `tl_datasync_trivia` WHERE sent = 0 AND suspended = 1;", "tl_datasync"):
                db::sql("SELECT trace_id, user_id, sleep, trivia_id FROM `tl_datasync_trivia` WHERE sent = 0 AND suspended = 0 AND process_end IS NOT NULL;", "tl_datasync")
            );
        $users = null;
        if (mysqli_num_rows($result)){
            while(list($trace_id, $user_id, $sleep, $trivia_id) = mysqli_fetch_array($result)){
                /* log current datasync has started to resolve duplicate question being sent */
                $users[] = array('user_id'=>$user_id, 'trace_id'=>$trace_id, 'sleep'=>$sleep, 'trivia_id'=>$trivia_id);
            }
        }
        return $users;
    }

    /* check if current notify has been pushed */
    public static function has_notify_sent($service_local_id, $user_id, $trivia_id)
    {
        $result = db::sql("SELECT delivered FROM `tl_datasync_notify` WHERE user_id = '$user_id' AND trivia_id = '$trivia_id' AND service_id = '$service_local_id'", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($delivered) = mysqli_fetch_array($result)){
                return (int) $delivered;
            }
        }
        return null;
    }

    /* collect successful notify users */
    public static function fetch_notify_sent($service_local_id)
    {
        $result = db::sql("SELECT user_id, trivia_id FROM `tl_datasync_notify` WHERE service_id = '$service_local_id' AND delivered = 1", "tl_datasync");
        $notified_users = null;
        if (mysqli_num_rows($result)){
            while(list($user_id, $trivia) = mysqli_fetch_array($result)){
                $notified_users[] = array('user_id'=>$user_id, 'trivia'=>$trivia);
            }
        } return $notified_users;
    }

    /* check if current notify for related user being suspended to avoid locking datasync resources */
    public static function has_notify_suspended($service_local_id, $user_id, $trivia_id)
    {
        $result = db::sql("SELECT suspended FROM `tl_datasync_notify` WHERE user_id = '$user_id' AND trivia_id = '$trivia_id' AND service_id = '$service_local_id';", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($suspended) = mysqli_fetch_array($result)){
                return (int) $suspended;
            }
        }
        return null;
    }

    /* fetch currently suspended users in the notify queues */
    public static function fetch_notify_suspended($service_local_id)
    {
        $result = db::sql("SELECT DISTINCT user_id FROM `tl_datasync_notify` WHERE suspended = 1 AND service_id = '$service_local_id';", "tl_datasync");
        $suspended_users = null;
        if (mysqli_num_rows($result)){
            while(list($user_id) = mysqli_fetch_array($result)){
                $suspended_users[] = $user_id;
            }
        } return $suspended_users;
    }

    /* update datasync to suspended */
    public static function update_datasync_suspended($service_local_id, $user_id, $trivia, $retries=null)
    {
        return (is_null($retries)) ?
            db::sql("UPDATE `tl_datasync_trivia` SET suspended = 1 WHERE user_id = '$user_id' AND trivia_id = '$trivia' AND service_id = '$service_local_id';", "tl_datasync"):
            db::sql("UPDATE `tl_datasync_trivia` SET suspended = 0 WHERE user_id = '$user_id' AND trivia_id = '$trivia' AND service_id = '$service_local_id';", "tl_datasync");
    }

    /* log current datasync has started */
    public static function log_datasync_thread_started($trace_id, $update=null)
    {
        return (is_null($update)) ?
            db::sql("UPDATE `tl_datasync_trivia` SET process_start = CURRENT_TIMESTAMP WHERE trace_id = '$trace_id';", "tl_datasync") :
            db::sql("UPDATE `tl_datasync_trivia` SET process_end = CURRENT_TIMESTAMP WHERE trace_id = '$trace_id';", "tl_datasync") ;
    }

    public static function log_closing_message($service_local_id, $user_msisdn, $trivia_id, $type)
    {
        $data_list = array($user_msisdn, thread_ctrl::get_unique_trace_id(), $trivia_id);
        /* log current notify to trace report */
        self::prepare_datasync_notify($data_list, $type, null, null, rand(0,1), $service_local_id);
    }

    /* check if the current user has already received a closing msg to avoid unnecessary traffic */
    public static function has_closing_message($service_local_id, $user_msisdn, $type)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_datasync_notify` WHERE msisdn = '$user_msisdn' AND service_id = '$service_local_id' AND score = '$type';", "tl_datasync"));
    }

    /* update datasync queue */
    public static function update_datasync_trivia($service_local_id, $user_id=null, $trivia_id=null, $delivery_state=null, $correlatorID=null)
    {
        return (is_null($delivery_state)) ?
            db::sql("UPDATE `tl_datasync_trivia` SET sent = 1, process_end = CURRENT_TIMESTAMP WHERE user_id = '$user_id' AND trivia_id = '$trivia_id' AND service_id = '$service_local_id';", "tl_datasync"):
            db::sql("UPDATE `tl_datasync_trivia` SET sent = '$delivery_state', delivered = '$delivery_state', process_end = CURRENT_TIMESTAMP WHERE trace_id = '$correlatorID' AND service_id = '$service_local_id';", "tl_datasync");
    }


    /* return recent user service activity */
    public static function services_activities($service_local_id, $user_id)
    {
        $current_service = new services($service_local_id);
        $result = ($current_service->service_type == 1) ?
            db::sql("SELECT content_id, process_end FROM `tl_servicing_contents` WHERE user_id = '$user_id' AND service_id = '$service_local_id' AND process_state = 1", "tl_datasync"):
            db::sql("SELECT trivia_id, user_answer, score, cross_sell_id, process_end FROM `tl_servicing_trivia` WHERE user_id = '$user_id' AND
                     user_answer IS NOT NULL AND service_id = '$service_local_id' ORDER BY process_end DESC LIMIT $current_service->broadcast_length", "tl_datasync");
        $process_result = null;
        if (!mysqli_num_rows($result)){
            $result = ($current_service->service_type == 1) ?
                db::sql("SELECT content_id, process_end FROM `tl_servicing_contents` WHERE user_id = '$user_id' AND service_id = '$service_local_id' AND process_state = 1 ORDER BY process_end DESC LIMIT 3", "tl_datasync_archives"):
                db::sql("SELECT trivia_id, user_answer, score, cross_sell_id, process_end FROM `tl_servicing_trivia` WHERE user_id = '$user_id' AND
                     user_answer IS NOT NULL AND service_id = '$service_local_id' ORDER BY process_end DESC LIMIT $current_service->broadcast_length", "tl_datasync_archives");
        }

        if (mysqli_num_rows($result)){
            if (mysqli_num_rows($result)){
                if ($current_service->service_type == 1){
                    while(list($content_id, $process_date) = mysqli_fetch_array($result)){
                        $current_q = self::fetch_content($content_id, $current_service);
                        $process_result[] = array('content'=>$current_q[0][1], 'date'=>thread_ctrl::get_time_ago(strtotime($process_date)));
                    }
                } else {
                    while(list($trivia_id, $answer, $score, $cross_sell_id, $process_date) = mysqli_fetch_array($result)){
                        /* get question */
                        $current_q = self::fetch_content($trivia_id, $current_service, null, null, $cross_sell_id);
                        $correction = ($score == 'correct') ? 1 : 0;
                        $earned = ($score == 'correct') ? $current_q[1] : '0';
                        /* get correct answer */
                        $correct_answer = ($earned == '0') ? self::fetch_notify_related_message($service_local_id, $trivia_id, $score) : null;
                        $process_result[] = array('content'=>$current_q[0], 'answer'=>$answer, 'correct'=>$correction, 'earned'=>$earned, 'correct_answer'=>$correct_answer, 'date'=>thread_ctrl::get_time_ago(strtotime($process_date)));
                    }
                }
            }
        }

        return $process_result;
    }


    /****** services log ********/

    /* log current service scheduled starting log */
    public static function log_service_process($service_local_id, $process_id, $message, $result)
    {
        db::sql("INSERT INTO `tl_services_logs` (service_local_id, process_id, date_created, message, `date`, result)
                VALUES('$service_local_id', '$process_id', CURRENT_TIMESTAMP, '$message', CURRENT_TIMESTAMP, '$result')", DB_NAME);
    }


    /****** retries services *****/

    /* get all retries users for the given service_local_id */
    public static function retried_users($service_local_id)
    {
        $result = db::sql("SELECT user_id FROM `tl_servicing_contents` WHERE process_state = 3 AND service_id = '$service_local_id';", "tl_datasync");
        $users = null;
        if (mysqli_num_rows($result)){
            while(list($user_id)=mysqli_fetch_array($result)){
                $users[] = $user_id;
            }
        }
        return $users;
    }

    /* update current service suspension status */
    public static function update_retries_suspension($user_id, $service_local_id, $suspended)
    {
        return db::sql("UPDATE `tl_servicing_contents` SET suspended = '$suspended' WHERE service_id = '$service_local_id' AND user_id = '$user_id'", "tl_datasync");
    }

    /* fetch contents users in the queue */
    public static function contents_users($service_local_id)
    {
        $result = db::sql("SELECT user_id FROM `tl_servicing_contents` WHERE service_id = '$service_local_id' AND `process_state` = 0 LIMIT 100", "tl_datasync");
        $users = null;
        if (mysqli_num_rows($result)){
            while(list($user_id) = mysqli_fetch_array($result)){
                $users[] = $user_id;
            }
        } return $users;
    }


    /***** services updating requests ****/

    /* check keyword service relation exists */
    public function has_service_keywords($keyword_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_services_keywords` WHERE service_local_id = '$this->service_local_id' AND keyword_id = '$keyword_id';", DB_NAME));
    }
    /* add related service keywords associated */
    public function add_service_keywords($keyword_id)
    {
        if ($this->has_service_keywords($keyword_id)){
            return 0;
        }
        return db::sql("INSERT INTO `tl_services_keywords` (keyword_id, service_local_id) VALUES('$keyword_id', '$this->service_local_id');", DB_NAME);
    }
    /* add related service cross sell reference */
    public function add_crossell_service($service_id)
    {
        return db::sql("INSERT INTO `tl_services_cross_sell_references` (cross_sell_service_id, service_local_id) VALUES('$service_id', '$this->service_local_id');", DB_NAME);
    }
    /* remove current cross sell service */
    public function delete_crossell_service($service_name)
    {
        $service_id = self::service_id($service_name);
        if ($service_id){
            return db::sql("DELETE FROM `tl_services_cross_sell_references` WHERE service_local_id = '$this->service_local_id' AND cross_sell_service_id = '$service_id';", DB_NAME);
        } else {
            return array('error'=>'service '. $service_name.' couldn\'t be found in the system');
        }
    }
    /* update date range for the target service */
    public function update_service_promo_date($start_date, $end_date)
    {
        return db::sql("UPDATE `tl_services` SET start_date = '$start_date', end_date = '$end_date' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast settings */
    public function update_service_broadcast_set($broadcast)
    {
        return db::sql("UPDATE `tl_services` SET broadcast = '$broadcast' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast type */
    public function update_service_broadcast_type($broadcast_type)
    {
        return db::sql("UPDATE `tl_services` SET broadcast_type = '$broadcast_type' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast opening */
    public function update_service_broadcast_opening($broadcast_opening)
    {
        return db::sql("UPDATE `tl_services` SET opening_message = '$broadcast_opening' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast length */
    public function update_service_broadcast_length($broadcast_length)
    {
        return db::sql("UPDATE `tl_services` SET broadcast_length = '$broadcast_length' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast tps */
    public function update_service_broadcast_tps($broadcast_tps)
    {
        return db::sql("UPDATE `tl_services` SET set_thread = '$broadcast_tps' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast tps */
    public function update_service_sdp_billrate($bill_rate)
    {
        return db::sql("UPDATE `tl_services` SET billing_rate = '$bill_rate' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service free period */
    public function update_service_sdp_free_period($free_period)
    {
        return db::sql("UPDATE `tl_services` SET free_period = '$free_period' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* reset current web service accesscode */
    public function reset_service_accesscode()
    {
        /* get the app_id for corresponding service_local_id */
        $app_id = app::fetch_service_authcode($this->service_local_id, true);
        /* generate a new accesscode */
        if ($app_id){
            if (app::update_accesscode(thread_ctrl::generate_random_code(20), $app_id)){
                return true;
            }
        } return null;
    }
    /* update current service broadcast crossell */
    public function update_service_broadcast_crossell($broadcast_crossell)
    {
        return db::sql("UPDATE `tl_services` SET cross_sell_services = '$broadcast_crossell' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current service broadcast crossell */
    public function update_service_broadcast_crossell_sub($broadcast_crossell_sub)
    {
        return db::sql("UPDATE `tl_services` SET cross_sell_on_sub = '$broadcast_crossell_sub' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    public function update_service_broadcast_crossell_first($broadcast_crossell_first)
    {
        return db::sql("UPDATE `tl_services` SET cross_sell_on_first = '$broadcast_crossell_first' WHERE service_local_id = '$this->service_local_id';", DB_NAME);
    }
    /* update current content service */
    public function update_service_content($data)
    {
        if ($this->service_type == 1){
            /* verify data integrity */
            $data['message'] = (strpos($data['message'], "'") !== false) ? str_replace("'","\'", $data['message']) : $data['message'];
            return db::sql("UPDATE `tl_services_contents` SET message = '".$data['message']."' WHERE service_id = '$this->service_local_id' AND id = '".$data['content_id'] ."';", DB_NAME);
        } else {
            /* verify data integrity */
            $data['question'] = (strpos($data['question'], "'") !== false) ? str_replace("'","\'", $data['question']) : $data['question'];
            $data['answer'] = (strpos($data['answer'], "'") !== false) ? str_replace("'","\'", $data['answer']) : $data['answer'];
            $data['correct'] = (strpos($data['correct'], "'") !== false) ? str_replace("'","\'", $data['correct']) : $data['correct'];
            $data['incorrect'] = (strpos($data['incorrect'], "'") !== false) ? str_replace("'","\'", $data['incorrect']) : $data['incorrect'];
            return db::sql("UPDATE `tl_service_trivia` SET question = '".$data['question']."', answer = '".$data['answer']."', correct_notify = '". $data['correct']."',
            incorrect_notify = '".$data['incorrect'] ."', score = '".$data['score'] ."' WHERE id = '".$data['content_id'] ."' AND service_id = '$this->service_local_id';", DB_NAME);
        }
    }

    /* create a service campaign */
    private static function create_campaign_service($data)
    {
        /* check if the promotion date is correct */
        $date_promo = explode('-', $data['promo_date']);
        $start_date = date('Y-m-d', strtotime("$date_promo[0]"));
        $end_date = date('Y-m-d', strtotime("$date_promo[1]"));
        if ($start_date < date('Y-m-d')){
            return null;
        } else if (is_null($data['sp_product_data'][0]['product_id']) && $data['type_id'] != '4'){
            return 0;
        } else {
            /* correct data integrity */
            $data['name'] = (strpos($data['name'], "'") !== false) ? str_replace("'","\'", $data['name']) : $data['name'];
            $data['desc'] = (strpos($data['desc'], "'") !== false) ? str_replace("'","\'", $data['desc']) : $data['desc'];
            if ($data['type_id'] ==  '1'){
                $data['service_messages']['crossell'] = (strpos($data['service_messages']['crossell'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['crossell']) : $data['service_messages']['crossell'];
                $data['service_messages']['correct'] = (strpos($data['service_messages']['correct'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['correct']) : $data['service_messages']['correct'];
                $data['service_messages']['incorrect'] = (strpos($data['service_messages']['incorrect'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['incorrect']) : $data['service_messages']['incorrect'];
            } else if ($data['type_id'] == '2') {
                $data['service_messages']['welcome'] = (strpos($data['service_messages']['welcome'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['welcome']) : $data['service_messages']['welcome'];
                $data['service_messages']['good_score'] = (strpos($data['service_messages']['good_score'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['good_score']) : $data['service_messages']['good_score'];
                $data['service_messages']['poor_score'] = (strpos($data['service_messages']['poor_score'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['poor_score']) : $data['service_messages']['poor_score'];
                $data['service_messages']['last_play'] = (strpos($data['service_messages']['last_play'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['last_play']) : $data['service_messages']['last_play'];
                $data['service_messages']['never_play'] = (strpos($data['service_messages']['never_play'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['never_play']) : $data['service_messages']['never_play'];
                $data['service_messages']['excel_score'] = (strpos($data['service_messages']['excel_score'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['excel_score']) : $data['service_messages']['excel_score'];
                $data['service_messages']['closing'] = (strpos($data['service_messages']['closing'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['closing']) : $data['service_messages']['closing'];
            } else {
                if ($data['type_id'] == '3'){
                    $data['service_messages']['welcome'] = (strpos($data['service_messages']['welcome'], "'") !== false) ? str_replace("'","\'", $data['service_messages']['welcome']) : $data['service_messages']['welcome'];
                }
            }

            $thread_set = 50;
            $broadcast_set = $data['type_id'] == '3' || $data['type_id'] == '4' ? 0 : 1;

            /* check for an exclusive type allocated subroutine number */
            if ($data['type_id'] == '3'){
                if (service_base_code::has_routines_available() === false)
                    return array('error'=>'Enable to process your request - '. $data['name']. ' with service type exclusive has no subroutine available to be created. Please contact System Admin');
            }

            $service_local_id = db::sql("INSERT INTO `tl_services` (`name`, description, service_type, start_date, end_date, broadcast, broadcast_type, broadcast_length, cross_sell_services, cross_sell_on_sub,
                opening_message, set_thread, free_period, service_sdp_id, accesscode, sp_id, sp_password, last_process_id, date_created, active)
                VALUES('".$data['name']."', '". $data['desc']."', '".$data['type_id']."', '$start_date', '$end_date', '$broadcast_set', '". $data['br_type']."', '". $data['br_length']."', '".$data['br_cross_sell_set']."',
                '".$data['br_cross_sell_sub_set']."', '". $data['br_opening']."', '$thread_set', '". $data['sp_free_period']."', '".$data['sp_service_id']."', '".$data['sp_shortcode']."',
                '".$data['sp_id']."', '".$data['sp_password']."', 0, CURRENT_TIMESTAMP, 1);", DB_NAME);

            /* add related service messages */
            if ($service_local_id){
                if ($data['type_id'] == '1'){
                    /* content service */
                    service_messages::create_service_message($data['service_messages']['crossell'], 7, $service_local_id, $data['service_messages']['correct'], $data['service_messages']['incorrect']);

                } else if ($data['type_id'] == '2') {
                    /* trivia service */
                    //welcome
                    service_messages::create_service_message($data['service_messages']['welcome'], 1, $service_local_id);
                    //good score
                    service_messages::create_service_message($data['service_messages']['good_score'], 2, $service_local_id);
                    //poor score
                    service_messages::create_service_message($data['service_messages']['poor_score'], 3, $service_local_id);
                    //last play
                    service_messages::create_service_message($data['service_messages']['last_play'], 4, $service_local_id);
                    //never play
                    service_messages::create_service_message($data['service_messages']['never_play'], 5, $service_local_id);
                    //excel score
                    service_messages::create_service_message($data['service_messages']['excel_score'], 6, $service_local_id);
                    //Closing
                    service_messages::create_service_message($data['service_messages']['closing'], 8, $service_local_id);

                    /* add service cross sell references */
                    $service_cross_list = $data['cross_sell_list'];
                    if ($service_cross_list){
                        foreach($service_cross_list as $service_id){
                            service_messages::create_crossell_service_references($service_local_id, $service_id);
                        }
                    }
                } else if ($data['type_id'] == '3') {
                    //welcome
                    service_messages::create_service_message($data['service_messages']['welcome'], 1, $service_local_id);
                    //add a service code reference
                    service_base_code::create_references($service_local_id, $data['desc']);
                } else {

                }

                /* create keywords related */
                $service_keywords_list = $data['keywords'];
                if ($service_keywords_list){
                    foreach($service_keywords_list as $keyword){
                        /* keyword_service relation */
                        keywords::add_keyword_service_id(keywords::add_keyword($keyword), $service_local_id);
                    }
                }

                if ($data['type_id'] == '4'){
                    /* create app related services */
                    self::create_service_app(array('name'=> $data['name'], 'desc'=>$data['desc']), $service_local_id);
                } else {
                    /* create product related services */
                    self::create_service_products($data['sp_product_data'], $service_local_id);
                }

                return true;
            } else {
                return false;
            }
        }
    }

    public static function create_service_products($product_data, $service_local_id)
    {
        foreach($product_data as $product){
            if (!empty($product['product_id'])){
                /* insert current product */
                $product_id = db::sql("INSERT INTO `tl_products` (product_sdp_id, billing_rate, billing_cycle_id)
            VALUES('". $product['product_id']."', '". $product['bill_rate']."', '". $product['bill_cycle']."');", DB_NAME);
                if ($product_id){
                    /* create product_service_relation */
                    db::sql("INSERT INTO `tl_services_products` (product_id, service_local_id) VALUES('$product_id', '$service_local_id');", DB_NAME);
                }
            }
        }
    }


    /* create a service app */
    public static function create_service_app($app_data, $service_local_id)
    {
        /* insert current app */
        $auth_code = thread_ctrl::generate_random_code(20);
        $app_id = db::sql("INSERT INTO `tl_apps` (`name`, `desc`, authcode) VALUES('". $app_data['name']."', '". $app_data['desc']."', '$auth_code');", DB_NAME);
        if ($app_id){
            /* create app_service_relation */
            db::sql("INSERT INTO `tl_services_apps` (app_id, service_local_id) VALUES('$app_id', '$service_local_id');", DB_NAME);
        }
    }

    /* create a campaign draw */
    public static function create_campaign_draw($data)
    {
        /* check if the promotion date is correct */
        $date_promo = explode('-', $data['draw_date_range']);
        $start_date = date('Y-m-d', strtotime("$date_promo[0]"));
        $end_date = date('Y-m-d', strtotime("$date_promo[1]"));
        if ($start_date < date('Y-m-d')){
            return null;
        } else {
            /* correct data integrity */
            $data['name'] = (strpos($data['name'], "'") !== false) ? str_replace("'","\'", $data['name']) : $data['name'];
            $data['desc'] = (strpos($data['desc'], "'") !== false) ? str_replace("'","\'", $data['desc']) : $data['desc'];
            $data['notify'] = (strpos($data['notify'], "'") !== false) ? str_replace("'","\'", $data['notify']) : $data['notify'];
            /* check campaign draw exists */
            if (self::has_campaign_draw($data['services_draw_linked'], $data['draw_type_id'])){
                return 0;
            } else {
                $service_draw_id = db::sql("INSERT INTO `tl_services_draw_engine` (`name`, `desc`, notify, start_date, end_date, draw_type_id, draw_win_no, draw_engine_type, active, date_created, draw_win_rollout)
                              VALUES('". $data['name']."', '". $data['desc']."', '". $data['notify']."', '$start_date', '$end_date', '". $data['draw_type_id']."', '". $data['draw_num']."', '". $data['draw_engine_type']."', 1, CURRENT_TIMESTAMP, '". $data['services_draw_win_rollout']."')", DB_NAME);
                if ($service_draw_id){
                    /* add campaign draw services associated */
                    foreach($data['services_draw_linked'] as $service_id){
                        db::sql("INSERT INTO `tl_services_draw_references` (service_local_id, service_draw_id) VALUES('$service_id', '$service_draw_id');", DB_NAME);
                    }
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /* check current campaign draw */
    public static function has_campaign_draw($services_draw_linked, $draw_type_id)
    {
        /* check if associated campaign draws related to the same local service id */
        $service_draw_ids = array();
        foreach($services_draw_linked as $service_id){
            $service_draw_id = self::get_service_draw_id($service_id);
            if ($service_draw_id){
                foreach($service_draw_id as $draw_id){
                    if (!in_array($draw_id, $service_draw_ids)){
                        $service_draw_ids[] = $draw_id;
                    }
                }
            }

        }
        /* check result ::
        1 service service_draw_id : the campaign draw already exist for the services' combination selected. check draw type
        0, 2 or more service_draw_id for the services' combination selected, the campaign draw doesn't exits */

        if (count($service_draw_ids) > 0){
            /* check if same draw type */
            $service_draw_ids_created = self::get_service_draw_type_id($draw_type_id);
            if (is_null($service_draw_ids_created)) return false;
            foreach($service_draw_ids as $draw_id){
                if (in_array($draw_id, $service_draw_ids_created)){
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }
    private static function get_service_draw_id($service_local_id)
    {
        $result = db::sql("SELECT service_draw_id FROM `tl_services_draw_references` WHERE service_local_id = '$service_local_id';", DB_NAME);
        $service_draw_ids = null;
        if (mysqli_num_rows($result)){
            while(list($service_draw_id) = mysqli_fetch_array($result)){
                $service_draw_ids[] = $service_draw_id;
            }
        } return $service_draw_ids;
    }
    private static function get_service_draw_type_id($draw_type_id)
    {
        $result = db::sql("SELECT id FROM `tl_services_draw_engine` WHERE draw_type_id = '$draw_type_id';", DB_NAME);
        $service_draw_ids = null;
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
               $service_draw_ids[] = $id;
            }
        } return $service_draw_ids;
    }

    /************** Rest-APi **********/
    /* process services related list request */
    public static function process_list_request($category, $type, $service_local_id=null)
    {
        switch($category){
            case 'services':
                return array('data'=>self::services_list($type));
                break;
            case 'users':
                return array('data'=>user::fetch_active_subs($service_local_id));
                break;
            case 'service_type':
                return array('data'=>self::service_type_list());
                break;
            case 'draw_type':
                return array('data'=>self::draw_type_list());
                break;
            case 'cross_sell_service':
                return array('data'=>self::service_cross_list());
                break;
            case 'br_type':
                return array('data'=>self::service_broadcast_type_list());
                break;
            case 'data_basic':
                return array('data'=>self::service_data_basic($service_local_id));
                break;
            case 'data_content':
                return array('data'=>self::service_data_content($service_local_id));
                break;
            case 'draw_engine_list':
                return array('data'=>self::services_draw_engine_list());
                break;
            case 'draw_data_basic':
                return array('data'=>self::service_draw_data_basic($service_local_id));
                break;
            default:
                return array('error'=>$category. ' is wrong request type. Please review manual');
                break;
        }
    }

    /* process service creation */
    public static function process_service_creation($data, $type)
    {
       //echo '<pre>'. print_r($data, true) .'</pre>';
        if ($data){
            /* campaign service creation */
            $result = $type == 'service' ? self::create_campaign_service($data) : self::create_campaign_draw($data);
            if (is_null($result)){
                return array('error'=>'Enable to process your request - Invalid promotion date given. Please check start date');
            } else {
                if ($result === false){
                    return $type == 'service' ?
                        array('error'=>'Enable to process your request - The service ID/Product ID already exist. Please review') :
                        array('error'=>'Enable to process your request - Internal error occurred creating campaign draw service. Please Contact System Admin. \n Errors: '. db::$errors);
                } else {
                    if ($result == 0 && $type == 'draw'){
                        return array('error'=>'Enable to process your request - '. $data['name']. ' with services associated already exists with the given draw type selected');
                    } else if ($result == 0 && $type == 'service'){
                        return array('error'=>'Enable to process your request - '. $data['name']. ' has no valid product ID. Please review');
                    } else if (is_array($result)) {
                        return $result;
                    }
                    return array('data'=>$data['name']. ' has been successful created');
                }
            }
        } else {
            return array('error'=>'Enable to create a service campaign - service data is empty. Please try again or if the problem persist contact System Admin');
        }
    }

    /* update service related request */
    public static function process_service_update($category, $data, $service_local_id, $process, $type)
    {
        $current_service = ($type == 'service') ? new services($service_local_id) : new service_draw($service_local_id);
        switch($category){
            case 'keywords':
                if ($process == 'update'){
                    if (keywords::has_keyword_length($service_local_id) < $current_service->keywords_length){
                        if (is_string($data)){
                            $keyword_id = keywords::add_keyword($data);
                            $result = $current_service->add_service_keywords($keyword_id);
                            if ($result === 0){
                                return array('error'=>$data. ' keyword(s) already exists. Please review');
                            }
                        } else if (is_array($data)) {
                            if ( (count($data) + keywords::has_keyword_length($service_local_id)) < $current_service->keywords_length){
                                $error_list = null;
                                for($i=0; $i < count($data); ++$i){
                                    $keyword_id = keywords::add_keyword($data[$i]);
                                    $result = $current_service->add_service_keywords($keyword_id);
                                    if ($result === 0){
                                        $error_list[] = $data[$i];
                                    }
                                }
                                if (!is_null($error_list)){
                                    return array('error'=>implode(',', $error_list). ' already exist(s). Please review');
                                }
                            } else {
                                return array('error'=>'The service '. $current_service->name.' has met allowed keyword length. Please delete keywords to add current ones');
                            }
                        } else {
                            array('error'=>'Unsupported data submitted. Please review manual');
                        }
                    } else {
                        return array('error'=>'The service '. $current_service->name.' has met allowed keyword length. Please delete keywords to add current ones');
                    }
                } else {
                    /* delete */
                    if (is_string($data)){
                        $result = keywords::delete_keyword($data, $service_local_id);
                        if (!is_int($result)){
                            return $result;
                        }
                    } else if(is_array($data)) {
                        for($i=0; $i < count($data); ++$i){
                            keywords::delete_keyword($data[$i], $service_local_id);
                        }
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' keywords has been successfully updated');
                break;
            case 'crossell':
                if ($process == 'update'){
                    $current_service->add_crossell_service($data);
                } else {
                    /* delete */
                    if (is_array($data)){
                        for($i=0; $i < count($data); ++$i){
                            $current_service->delete_crossell_service($data[$i]);
                        }
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' crossell reference has been successfully updated');
                break;
            case 'date_promo':
            case 'draw_range_date':
                if ($process == 'update'){
                    if (is_string($data)){
                        $date_range = explode('|', $data);
                        $start_date = date_format(date_create($date_range[0]), 'Y-m-d');
                        $end_date = date_format(date_create($date_range[1]), 'Y-m-d');
                        /* prevent changes if the service has already started running */
                        if ($current_service->start_date == $start_date && $current_service->end_date != $end_date){
                            if ($end_date < date('Y-m-d')){
                                return array('error'=> 'End date out of range. Please choose current date or future date');
                            } else {
                                $current_service->update_service_promo_date($start_date, $end_date);
                            }
                        } else {
                            if ($current_service->start_date < date('Y-m-d')){
                                return array('error'=> $current_service->name. ' Campaign already started. Start date changes not allowed');
                            } else {
                                if ($start_date < date('Y-m-d') && $start_date < $current_service->start_date) {
                                    return array('error'=> 'Start date out of range. Please choose current date or future date');
                                } else {
                                    $current_service->update_service_promo_date($start_date, $end_date);
                                }
                            }
                        }
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' campaign date promotion has been successfully updated');
                break;
            case 'broadcast':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_broadcast_set($data);
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast setting has been successfully updated');
                break;
            case 'broadcast_type':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_broadcast_type($data);
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast type has been successfully updated');
                break;
            case 'broadcast_opening':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_broadcast_opening($data);
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast opening has been successfully updated');
                break;
            case 'broadcast_length':
                if ($process == 'update'){
                    if (is_string($data)){
                        $current_service->update_service_broadcast_length($data);
                    } else {
                        array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast length has been successfully updated');
                break;
            case 'broadcast_tps':
                if ($process == 'update'){
                    if (is_string($data)){
                        $current_service->update_service_broadcast_tps($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast TPS has been successfully updated');
                break;
            case 'sdp_bill_rate':
                if ($process == 'update'){
                    if (is_string($data)){
                        if ($current_service->start_date < date('Y-m-d')){
                            return array('error'=>'Error updating billing rate. The service campaign is already started.');
                        }
                        $current_service->update_service_sdp_billrate($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' billing rate has been successfully updated');
                break;
            case 'sdp_free_period':
                if ($process == 'update'){
                    if (is_string($data)){
                        if ($current_service->start_date < date('Y-m-d')){
                            return array('error'=>'Error updating free period. The service campaign is already started.');
                        }
                        $current_service->update_service_sdp_free_period($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' free period has been successfully updated');
                break;
            case 'accesscode':
                if ($current_service->reset_service_accesscode()){
                    array('data'=>$current_service->name. ' accesscode has been successfully reset');
                } else {
                    return array('error'=>'Error resetting  accesscode. Please try again later if the problem persist, report to DEV.');
                }
                break;
            case 'broadcast_crossell':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_broadcast_crossell($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast cross sell has been successfully updated');
                break;
            case 'broadcast_crossell_sub':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_broadcast_crossell_sub($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' broadcast cross sell upon subscription has been successfully updated');
                break;
            case 'broadcast_crossell_sub_first':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_broadcast_crossell_first($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                } return array('data'=>$current_service->name. ' broadcast cross sell first on service interaction has been successfully updated');
                break;
            case 'messages_reference':
                /* process argument will be an object containing message type and target service
                get message type id and target service id */
                if ($process && $data){
                    $message_type_id = service_messages::message_type_id($process['type']);
                    $target_service_id = services::service_id($process['target_service']);
                    if ($message_type_id && $target_service_id){
                        service_messages::update_service_message($message_type_id, $target_service_id, $data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                } else {
                    return array('error'=>'Unsupported data submitted. Please review manual');
                }
                return array('data'=>$current_service->name. ' message references have been successfully updated');
                break;
            case 'messages_content':
                if ($process == 'update'){
                    if (is_array($data)){
                        if (!$current_service->update_service_content($data)){
                            return array('error'=>'An internal error occurred processing your request. Please try gain later');
                        }
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' message content has been successfully updated');
                break;
            case 'draw_type':
                if ($process == 'update'){
                    if ($data){
                        $current_service->update_service_draw_type($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' draw type has been successfully updated');
                break;
            case 'draw_num':
                if ($process == 'update'){
                    if ($data){
                        $current_service->update_service_draw_winner_num($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' draw winner number has been successfully updated');
                break;
            case 'draw_engine_type':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_draw_select($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' draw select has been successfully updated');
                break;
            case 'draw_win_rollout':
                if ($process == 'update'){
                    if (is_int($data)){
                        $current_service->update_service_draw_winner_rollout($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' draw winner rollout has been successfully updated');
                break;
            case 'draw_linked':
                if ($process == 'update'){
                    if ($data){
                        $current_service->update_service_draw_linked($data);
                    } else {
                        return array('error'=>'Unsupported data submitted. Please review manual');
                    }
                }
                return array('data'=>$current_service->name. ' draw linked services have been successfully updated');
                break;
            default:
                return array('error'=>$category. ' is unsupported category type. Please review manual');
                break;
        }

    }




}