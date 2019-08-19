<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * Start daily contents broadcast for the given subscriber or on request
 */

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* parse params */
$user_id = (isset($argv[1])) ? $argv[1] : null;
$service_local_id = (isset($argv[2]))? $argv[2]: null;
$wait_period = (isset($argv[3]))? (int)$argv[3]: null;
/* gwparams */
$gwtype = (isset($argv[4]))? $argv[4]: null;
$message = (isset($argv[5]))? $argv[5]: null;
$linkid = (isset($argv[6]))? $argv[6]: null;;
$senderCB = (isset($argv[7]))? $argv[7]: null;

/* empty message is equal to none after data has been cleaned up to prevent to pass senderCB param position
we set it back to 'NULL' = User send an empty answer. */

/* prevent further execution process if required param not present */
if (is_null($service_local_id)){
    $loggerObj->LogInfo("Threading contents ERROR - no service_local_id present - thread has been terminated! ");
    exit();
}

/* prepare gateway params for SMS or USSD sending method*/
$gwparams = array(
    'linkid'=>$linkid,
    'senderCB'=>$senderCB,
    'service_local_id'=>$service_local_id
);


/* initialise current service and user */
$current_service = new services($service_local_id);
$current_user = new user($user_id);

$loggerObj->LogInfo("Broadcasting to ID : $user_id | MSISDN : ". $current_user->msisdn." | Service : ". $current_service->name);

/* wait for a given number of sec */
if (!is_null($message)) sleep($wait_period);

/* check if a retry should be initiated in a case of failure */
$schedule_retries = null;

if (in_array($message, array(null, 'subscribe', 'unsubscribe'))){
    $loggerObj->LogInfo('automated content request');

    /* allocate entry for newly subscriber or users receiving contain as per system rules specification */
    service_draw::process_draw_allocation($current_service->service_local_id, $user_id, $message);

    /************** Automated Broadcast on schedule **************/
    /* process | sync data to be queued for broadcast for current user
   data_list contains an array of: [0] => msisdn, [1] => tracer_id, [2] => content_id, [3] => message */
    $data_list = services::sync_data($current_service, $user_id, $current_user->msisdn);
    /* process | start trivia broadcasting */

    if ($data_list){
        /* push gwparams to the data */
        array_push($data_list[0], $gwparams);

        /* process | Sending an HTTP POST request to SMS gateway. Data will be forwarded as a JSON syntax format */
        /* create a json object */
        $jsonData = json_encode($data_list[0], true);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $jsonData
            )
        );
        $context  = stream_context_create($opts);
        $response = file_get_contents('http://localhost'.DEFAULT_PORT.'/gateway/sms/?service_type=content', false, $context);
        /* process | Update current broadcast operation result in datasync */
        $response = json_decode($response, true);
        /* check the response  */
        if ($response['result'] == 'success'){
            if (services::update_data_sync($data_list[0][1], $user_id, $current_service, 1)){
                /* update user stat for the current broadcast */
                services::log_user_service_broadcast($user_id, $service_local_id, $data_list[0][2]);

                $loggerObj->LogInfo('tracer_id:'.$data_list[0][1].' | '. $response['message']);
            }
        } else {
            /* network error */
            $schedule_retries = true;
            if (services::update_data_sync($data_list[0][1], $user_id, $current_service, 3)){
                $loggerObj->LogInfo('tracer_id :'. $data_list[0][1]. ' | '. $response['message']);
            }
        }
    }

    /* log current thread completed */
    thread_ctrl::log($user_id, 1, $service_local_id);
} else {

    $loggerObj->LogInfo('pending content request');

    if ($current_service->service_type == 1){
        /* check if there are any queued contents for current user upon a re-subscription*/
        if (services::has_pending_content($user_id, $current_service)){
            /* send content awaiting in the queue */
            $report = services::start_broadcast_queued($current_service, $user_id, $current_user->msisdn, $gwtype, $gwparams);
            if ($report['result'] == true && $report['sent'] == 1){
                echo "success, Service $current_service->name sent to MSISDN $current_user->msisdn | tracer_id:". $report['tracer_id'];
            } else {
                $schedule_retries = true;
                echo "success, Failed sending Service $current_service->name to $current_user->msisdn | tracer_id:".$report['tracer_id'];
            }
        }
    } else {
        /* retries an exclusive service process */
        $report = service_base_code::start_exclusive_process($current_service, $user_id, $message, $gwparams, $gwtype, true);
        if (is_array($report)){
            if ($report['result'] == true && $report['sent'] == 1){
                echo "success, Service  $current_service->name sent to MSISDN $current_user->msisdn | tracer_id:". $report['tracer_id'];
            } else {
                $schedule_retries = true;
                echo "success, Failed sending Service $current_service->name to $current_user->msisdn | tracer_id:".$report['tracer_id'];
            }
        }
    }
}

/* Schedule retry for failed broadcast */
if ($schedule_retries === true && $message != 'retries_thread'){
    thread_ctrl::set_retry_operation(1, $service_local_id);
} else {
    /* de-count set value on success if it's current thread was initiated by retries operation */
    if (is_null($schedule_retries) && $message == 'retries_thread'){
        thread_ctrl::set_retry_operation(3, $service_local_id);
    }
}

/* free up database system memory */
db::close();