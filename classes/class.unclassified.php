<?php
/**
 * @provider: Mobi-Apps Telecoms Pty ltd
 * @author: Mecxi Musa
 * Servicing level 3 operations
 */
class service_level_3
{
    private static $re_try = 0;
    private static $sync_result = null;

    /* prepare Unique Code to be broadcast for current user
    on success : a data_list containing msisdn, tracer_id, code_id, code returned */
    public static function sync_data($user_id, $msisdn)
    {
        $result = null;
        $data_list = self::fetch_code();
        if ($data_list){
            if (self::start_sync($data_list, $user_id)){
                /* fetch tracer_id after data_sync creation */
                $tracer_id = self::fetch_tracer_id($user_id, $data_list[0]);
                /* push current tracer_id and msisdn into current list */
                array_unshift($data_list, $tracer_id);
                array_unshift($data_list, $msisdn);

                $result = $data_list;
            }
        }
        return $result;
    }

    /* fetch unique code for current user */
    private static function fetch_code()
    {
        /* generate unique code */
        $generated_code = self::generate_random_code();
        $code_id = db::sql("INSERT INTO `service_code` (`code`) VALUES('$generated_code')", DB_NAME);
        /* create generated code into db */
        while ($code_id === false){
            /* do a recursion */
            return self::fetch_code();
        }

        return array($code_id, self::get_code($code_id));
    }

    /* return code */
    private static function get_code($code_id)
    {
        $result = db::sql("SELECT `code` FROM `service_code` WHERE id = '$code_id'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($code) = mysqli_fetch_array($result)){
                return $code;
            }
        }
        return null;
    }

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

    /* start data sync | due to unique trace_id error,
    try 5 times to get a unique tracer_id
    data passed by reference to manage resources */
    private static function start_sync(&$data, $user_id, $retry=null)
    {
        self::$re_try = self::$re_try + 1;

        if ($retry){
            $tracer_id = thread_ctrl::get_unique_trace_id();
            if (!is_int(db::sql("INSERT INTO `servicing_level_3` (tracer_id, user_id, code_id, process_start, process_state)
                VALUES('$tracer_id', '$user_id', '$data', CURRENT_TIMESTAMP, 0)", "mtn_promo_datasync"))){
                self::$sync_result = null;
                if (self::$re_try < 6) {
                    self::start_sync($data, $user_id, 1);
                }
            } else {
                self::$sync_result = true;
            }
        } else {
            $tracer_id = thread_ctrl::get_unique_trace_id();
            $code_id = $data[0];
            if (db::sql("INSERT INTO `servicing_level_3` (tracer_id, user_id, code_id, process_start, process_state)
            VALUES('$tracer_id', '$user_id', '$code_id', CURRENT_TIMESTAMP, 0)", "mtn_promo_datasync") === false){
                /* insertion failed due to unique tracer_id |
                attempt a recursion function call */
                self::$sync_result = null;
                self::start_sync($code_id, $user_id, 1);

            } else {
                self::$sync_result = true;
            }
        }

        return self::$sync_result;
    }

    /* retrieve tracer_id for broadcast operation */
    private static function fetch_tracer_id($user_id, $code_id)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `tracer_id` FROM `servicing_level_3`
        WHERE `user_id` = '$user_id' AND `code_id` = '$code_id' AND `process_start` LIKE '$date%'", "mtn_promo_datasync");
        $tracer_id = null;
        if (mysqli_num_rows($result)){
            while(list($tracer) = mysqli_fetch_array($result)){
                $tracer_id = $tracer;
            }
        }
        return $tracer_id;
    }

    /* Update current broadcast operation */
    public static function update_data_sync($tracer_id, $code_id=null, $state)
    {
        /* Check SDP msg sent state */
        $result = null;
        if (is_int($state)){
            if ($state == 1){
                /* content has been delivered */
                if (db::sql("UPDATE `servicing_level_3` SET process_end = CURRENT_TIMESTAMP , process_state = 1
                    WHERE tracer_id = '$tracer_id'", "mtn_promo_datasync")){
                    $result = true;
                }
            }  else {
                /* Network communication error */
                $result = db::sql("UPDATE `servicing_level_3` SET process_end = CURRENT_TIMESTAMP , process_state = 3
              WHERE tracer_id = '$tracer_id'", "mtn_promo_datasync");
            }
        }
        return $result;
    }

    /*check if current user has any pending service content */
    public static function has_pending_content($user_id)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `tracer_id`, `code_id` FROM `servicing_level_3` WHERE `process_end` LIKE '$date%'
                  AND `user_id` = '$user_id' AND process_state = 3", "mtn_promo_datasync");
        $pending_content = null;
        if (mysqli_num_rows($result)){
            while(list($tracer_id, $code_id) = mysqli_fetch_array($result)){
                $pending_content = array('tracer_id'=>$tracer_id, 'code_id'=>$code_id);
            }
        }
        return $pending_content;
    }

    /* servicing operation thread on user subscription */
    public static function start_broadcast_queued($user_id, $user_msisdn, $gwtype, $gwparams)
    {
        global $loggerObj;
        /* track if the user content has been processed  */
        $proccessed = null;

        /*  check if the service has started */
        if (thread_ctrl::has_queued_started(null, 'threading_level_3')){
            /* fetch content that fails and still being in a queue */
            $data_list = self::fetch_queued_data($user_id, $user_msisdn);
            if ($data_list){
                /* reporting purposed */
                $proccessed = array('result'=>true, 'tracer_id'=>$data_list[1], 'sent'=>'0');

                /* Add a prefix message to code */
                $data_list[3] = 'Please use this unique Code: '. $data_list[3].' to register to Glam Squad App';

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
                $response = ($gwtype == 'sms')? file_get_contents('http://localhost/mtnpromo/gateway/sms/?mode=level3', false, $context):
                    file_get_contents('http://localhost/mtnpromo/gateway/ussd/?mode=level3', false, $context);

                /* process | Update current broadcast operation result in datasync */
                $response = json_decode($response, true);

                /* result | success is automatic demo with no user interaction */
                if ($response['result'] == 'noAnswer'){
                    if (self::update_data_sync($data_list[1], null, 1)){
                        $loggerObj->LogInfo("Service level 3 - Unique Code sent successfully !");
                    }
                    $proccessed['sent'] = '1';
                } else {
                    /* SDP communication failure */
                    if (self::update_data_sync($data_list[1], null, 3)){
                        $loggerObj->LogInfo("!!!! WARINING - [code 3] - Threading Operation FAILED | MSISDN ". $user_msisdn. " | tracer_id : ". $data_list[1]. " | Reason : ". $response['message']."!!!!");
                    }
                }
            }

        }

        return $proccessed;
    }

    /* retrieve content in queued operation, content that failed to a network error or still being in a queue */
    private static function fetch_queued_data($user_id, $msisdn)
    {
        $date = date('Y-m-d');
        $result = db::sql("SELECT `tracer_id`, `code_id` FROM `servicing_level_3` WHERE `user_id` = '$user_id'
                  AND `process_start` LIKE '$date%' AND `process_state` IN (3,0) ORDER BY `process_state` DESC LIMIT 1", "mtn_promo_datasync");
        $queued_content = null;
        if (mysqli_num_rows($result)){
            while(list($tracer_id, $code_id) = mysqli_fetch_array($result)){
                $queued_content = array($msisdn, $tracer_id, $code_id);
            }
        }
        /* fetch the content for current content_id */
        if ($queued_content){
            $code = self::get_code($queued_content[2]);
            if ($code){
                array_push($queued_content, $code);
            }
        }
        return $queued_content;
    }

    /* return today user service activity */
    public static function user_current_activity($user_id)
    {
        $date = date('Y-m-d');
        $process_result = null;
        $result = db::sql("SELECT tracer_id, code_id, process_end FROM `servicing_level_3` WHERE user_id = '$user_id' AND process_start > '$date%' AND process_state = 1", "mtn_promo_datasync");
        if (mysqli_num_rows($result)){
            while(list($trace_id, $code_id, $process_date) = mysqli_fetch_array($result)){
                /* check if current activity been notified */
                if (!notify::has_record($user_id, $trace_id, $code_id)){
                    if (notify::set_record($user_id, $trace_id, $code_id) === 0){
                        /* check if the code was used */
                        $code_used_date = code::has_checked($code_id);
                        if ($code_used_date){
                            $process_result[] = array('title'=>'Slay or Nay', 'content'=>'You have used this unique Code: '. self::get_code($code_id).' to Glam Squad App', 'date'=>thread_ctrl::get_time_ago(strtotime($code_used_date)));
                        } else {
                            $process_result[] = array('title'=>'Slay or Nay', 'content'=>'You have received this unique Code: '. self::get_code($code_id).' to register to Glam Squad App. Please make use of the code to cast your vote', 'date'=>thread_ctrl::get_time_ago(strtotime($process_date)));
                        }
                    }
                }
            }
        }
        return $process_result;
    }

    /* return 2 recent user service activity */
    public static function user_recent_activity($user_id)
    {
        $process_result = null;
        $result = db::sql("SELECT code_id, process_end FROM `servicing_level_3` WHERE user_id = '$user_id' AND process_state = 1 ORDER BY process_end DESC LIMIT 2", "mtn_promo_datasync");
        if (!mysqli_num_rows($result)){
            $result = db::sql("SELECT code_id, process_end FROM `archive_servicing_level_3` WHERE user_id = '$user_id' AND process_state = 1 ORDER BY process_end DESC LIMIT 2", "mtn_promo_archives");
        }

        if (mysqli_num_rows($result)){
            while(list($code_id, $process_date) = mysqli_fetch_array($result)){
                /* check if the code was used */
                $code_used_date = code::has_checked($code_id);
                if ($code_used_date){
                    $process_result[] = array('title'=>'Slay or Nay', 'content'=>'You have used this unique Code: '. self::get_code($code_id).' to Glam Squad App', 'date'=>thread_ctrl::get_time_ago(strtotime($code_used_date)));
                } else {
                    $process_result[] = array('title'=>'Slay or Nay', 'content'=>'You have received this unique Code: '. self::get_code($code_id).' to register to Glam Squad App. Please make use of the code to cast your vote', 'date'=>thread_ctrl::get_time_ago(strtotime($process_date)));
                }
            }
        }
        return $process_result;
    }
}