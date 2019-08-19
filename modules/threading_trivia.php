<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 *  Start daily contents broadcast for the given subscriber or on request
 */

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* parse params */
$user_id = (isset($argv[1])) ? $argv[1] : null;
$service_local_id = (isset($argv[2]))? $argv[2]: null;
$wait_period = (isset($argv[3]))? (int)$argv[3]: null;
/* gwparams */
$gwtype = (isset($argv[4]))? $argv[4]: null;
$message = (isset($argv[5]))? $argv[5]: null;
$tracer_id = (isset($argv[6]))? $argv[6]: null;
$linkid = (isset($argv[7]))? $argv[7]: null;;
$senderCB = (isset($argv[8]))? $argv[8]: null;

/* empty message is equal to none after data has been cleaned up to prevent to pass senderCB param position
we set it back to 'NULL' = User send an empty answer. */

/* prevent further execution process if required param not present */
if (is_null($service_local_id)){
    $loggerObj->LogInfo("Threading trivia ERROR - no service_local_id present - thread has been terminated! ");
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
$current_score = null;

if (in_array($message, array(null, 'subscribe', 'unsubscribe'))){
    if ($current_service->service_type == 2){
        $loggerObj->LogInfo('automated trivia request');

        /* allocate entry for newly subscriber or users receiving contain as per system rules specification */
        service_draw::process_draw_allocation($current_service->service_local_id, $user_id, $message);

        /************** Automated Broadcast on schedule **************/
        /* process | sync data to be queued for broadcast for current user
       data_list contains an array of: [0] => msisdn, [1] => tracer_id, [2] => content_id, [3] => message */
        $data_list = services::sync_data($current_service, $user_id, $current_user->msisdn, $message);
        /* process | start trivia broadcasting */

        if ($data_list){
            /* check if an opening message is required for the current service */
            $service_message_opener = $current_service->opening_message ?
                services::get_service_opening_message($current_service, $user_id, $current_user->msisdn) : null;

            if (services::prepare_datasync_trivia($data_list[0], $service_local_id, rand(0, 1))) {

                /* push gwparams to the data */
                array_push($data_list[0], $gwparams);

                /* update current trace for notify */
                $data_list[0][1] = thread_ctrl::get_unique_trace_id();
                /* attach notify message */
                $data_list[0][3] = (is_null($service_message_opener)) ? $data_list[0][3] : $service_message_opener;

                /* log current notify to trace report */
                services::prepare_datasync_notify($data_list[0], 'opening', null, null, rand(0, 1), $service_local_id, true);

                /* log current process has started */
                //services::log_notify_thread_started($data_list[0][1]);

                /* if our data_list has only one content, this is a cross_sell as no message for the current service
                was found, do not notify just send the cross_sell */
                if (count($data_list) > 1){
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
                    $response = file_get_contents('http://localhost'.DEFAULT_PORT.'/gateway/sms/?service_type=trivia', false, $context);
                    /* process | Update current broadcast operation result in datasync */
                    $response = json_decode($response, true);
                    /* check the response  */
                    if ($response['result'] == 'success'){
                        if (services::prepare_datasync_notify($data_list[0], null, true, null, null, $service_local_id)){
                            $loggerObj->LogInfo("Opening Message Sent successfully !");
                            echo 'success, Opening Message Sent successfully !';
                        }
                    } else {
                        $loggerObj->LogInfo('tracer_id :'. $data_list[0][1]. ' | '. $response['message']);
                        echo 'success, tracer_id :'. $data_list[0][1]. ' | '. $response['message'];
                        /* SDP communication failure :
                           failure upon subscription is not going to give users entry, force a reminder
                       */
                        services::log_notify_thread_started($data_list[0][1], true, ($message == 'subscribe') ? 1 : 0);
                    }
                } else {
                    $loggerObj->LogInfo("Opening Message is not required, cross sell will be triggered !");
                    /* log notify sent to trigger a cross_sell */
                    services::prepare_datasync_notify($data_list[0][1], null, true, 1, null, $service_local_id);
                    echo 'success, Opening Message is not required, cross sell will be triggered !';
                }
            }
        }

    } else {
        /* start an exclusive service process upon subscription if needed */
        $report = service_base_code::start_exclusive_process($current_service, $user_id, $message, $gwparams, $gwtype);
        if (is_array($report)){
            if ($report['result'] == true && $report['sent'] == 1){
                echo "success, Service  $current_service->name sent to MSISDN $current_user->msisdn | tracer_id:". $report['tracer_id'];
            } else {
                $schedule_retries = true;
                echo "success, Failed sending Service $current_service->name to $current_user->msisdn | tracer_id:".$report['tracer_id'];
            }
        }
    }
    /* log current thread completed */
    thread_ctrl::log($user_id, 1, $service_local_id);
} else {

    /* determine a user request type service */
    if ($current_service->service_type == 2){
        /* check if there are any pending contents for current user */
        $related_content = services::has_pending_content($user_id, $current_service);
        if ($related_content){
            $loggerObj->LogInfo('pending trivia request');
            /* check any pending question in the queue has been delivered */
            if (services::has_progress_datasync($user_id, $service_local_id)){
                $loggerObj->LogInfo("Enable to evaluated $current_user->msisdn | Sent Answer = $message | pending datasync trivia");
                echo "success, Sent Answer = $message | Enable to evaluated | $current_user->msisdn still has pending datasync trivia";
            } else {
                /* process scoring user answer */
                $current_score = services::update_data_sync($related_content['tracer_id'], $user_id, $current_service, 1,  $related_content['trivia_id'], $related_content['cross_sell_id'], $message);
                /* send the next question if there are still remaining ones in today queue */
                $report = services::start_broadcast_queued($current_service, $user_id, $current_user->msisdn, $gwtype, $gwparams, $current_score, $related_content);
                if ($report['result']){
                    if ($report['sent']){
                        echo "success, MSISDN $current_user->msisdn | Sent Answer = $message | evaluated successfully to: $current_score | notify message sent :". $report['notify'];
                    } else {
                        echo "success, MSISDN $current_user->msisdn | Sent Answer = $message | evaluated successfully to: $current_score | Network error : ".$report['error'];
                    }
                } else {
                    echo "success, Sent Answer = $message | evaluated successfully to: $current_score | $current_user->msisdn has no questions available in today broadcast queue";
                }
            }
        } else {
            $loggerObj->LogInfo("no_previous trivia pending");
            if (services::has_progress_datasync($user_id, $service_local_id) && $message !== 'pass@'){
                $loggerObj->LogInfo("Enable to evaluated $current_user->msisdn | Sent Answer = $message | pending datasync in the queue");
                echo "Enable to evaluated $current_user->msisdn | Sent Answer = $message | pending datasync in the queue";
            } else {
                $report = services::start_broadcast_queued($current_service, $user_id, $current_user->msisdn, $gwtype, $gwparams, $current_score, $tracer_id);
                if ($report['result']){
                    if ($report['data']){
                        echo "success, MSISDN $current_user->msisdn | Sent Answer = $message | no received previous trivia | trivia sent:". $report['data'];
                    } else if ($report['notify']){
                        echo "success, MSISDN $current_user->msisdn | Sent Answer = $message | evaluated successfully to: $current_score | notify message sent :". $report['notify'];
                    }
                } else {
                    if (is_null($report)){
                        echo "success, Sent Answer = $message | $current_user->msisdn has no trivia available in today broadcast queue";
                    } else {
                        echo "success, MSISDN $current_user->msisdn | Sent Answer = $message | evaluated successfully to: $current_score | Network error : ".$report['error'];
                    }
                }
            }
        }
    } else {
        /* start an exclusive service process upon user request */
        $report = service_base_code::start_exclusive_process($current_service, $user_id, $message, $gwparams, $gwtype);
        if (is_array($report)){
            if ($report['result'] == true && $report['sent'] == 1){
                echo "success, Service  $current_service->name sent to MSISDN $current_user->msisdn | tracer_id:". $report['tracer_id'];
            } else {
                $schedule_retries = true;
                echo "success, Failed sending Service $current_service->name to $current_user->msisdn | tracer_id:".$report['tracer_id']. ' | Error Detail: '. $report['error'];
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