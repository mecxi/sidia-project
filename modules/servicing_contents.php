<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * servicing contents in automated fashion. Tasks are allocated in individual thread per user running in a parallel to control
 * memory allocation and resources.
 * This script is triggered by the thread manager with a given number of thread to run
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* parse required params */
$allocated_res = (isset($argv[1])) ? $argv[1]:null;
$service_local_id = (isset($argv[2])) ? $argv[2]:null;

/* prevent further execution process if required param not present */
if (is_null($service_local_id)){
    $loggerObj->LogInfo("Servicing contents ERROR - no service_local_id present - thread has been terminated! ");
    exit();
}

/* instantiate a service object */
$current_service = new services($service_local_id);

$current_phase =  round(thread_ctrl::completed_thread($service_local_id) / (int) $allocated_res, 0, PHP_ROUND_HALF_UP);
$loggerObj->LogInfo("Phase $current_phase - Automated Service Contents Broadcast Operation has started | Service : ". $current_service->name);

$is_service_available = null;
/* check service availability for broadcast type 2, if content is available today */
if ($current_service->broadcast_type == 2){
    if (services::service_availability($current_service)){
        $is_service_available = true;
    } else {
        /* proceed if cross_sell is enabled */
        if ($current_service->cross_sell_services){
            $is_service_available = true;
        }
    }
} else {
    /* check if there's any content uploaded */
    if (services::has_content_related($current_service)){
        $is_service_available = true;
    } else {
        /* proceed if cross_sell is enabled */
        if ($current_service->cross_sell_services){
            $is_service_available = true;
        }
    }
}


if ($is_service_available === true){
    /* fetch active subscribers for the target service */
    if (!is_null($current_service->last_process_id)){

        $users_list = user::fetch_active_subs($service_local_id, $current_service->last_process_id, $allocated_res);
        /* set servicing users operations */
        $set_thread_users_operations = 'echo 0 ';

        /* start rolling out thread for the given subscribers */
        if ($users_list){
            /* fetch the last id from the list and log*/
            $last_current_user = end($users_list);
            services::log_last_processID($last_current_user['id'], $service_local_id);

            /* check if subscribers haven't already been queued for today service or  check if the user is first_content_broadcast_queue */
            foreach($users_list as $key => $user){
                if (thread_ctrl::has_queued($user['id'], $service_local_id) ||
                    thread_ctrl::has_first_content_queued($user['id'], $service_local_id)) continue;

                /******* START THREADING *********/
                /* check for content broadcast type 1, if there's a content available for this user */
                if ($current_service->broadcast_type == 1){
                    if (is_null(services::is_content_service_available($current_service, $user['id']))){
                        services::add_skipped_users($service_local_id, $user['id']);
                        $loggerObj->LogInfo('Service '. $current_service->name.' has been skipped | MSISDN: '. $user['msisdn']);
                        continue;
                    }
                }
                /* log current thread for tracking operation time */
                thread_ctrl::log($user['id'], 0, $service_local_id);
                /* wait a random number of sec before starting current thread */
                $wait_period = rand(0, 2);
                $set_thread_users_operations .= ($current_service->service_type == 1) ?
                    "& php threading_contents.php ".$user['id']." $service_local_id $wait_period":
                    "& php threading_trivia.php ".$user['id']." $service_local_id $wait_period";
            }

        } else {
            /* no more active subscribers to continue threading for the day | log daily task has completed */
            $loggerObj->LogInfo("No Active subscribers Service: ". $current_service->name." or remaining for broadcast operation on ". date('Y-m-d'));
            $loggerObj->LogInfo("Terminating scheduled broadcast");
            //echo "Terminating scheduled broadcast";
        }

        /* start thread */
        thread_ctrl::run(null, null, $set_thread_users_operations);

    }

} else {
    $error_related = ($current_service->broadcast_type == 2) ?
        "Automated Service $current_service->name Broadcast FAILED - No service contents found on ". date('Y-m-d'):
        "Automated Service $current_service->name Broadcast FAILED - No content exits in the database";
    $loggerObj->LogInfo($error_related);
    /* log current error */
    services::log_service_process($service_local_id, 1, $error_related, 0);
}

/* log operation state */

if (thread_ctrl::has_service_retries($service_local_id)){
    $loggerObj->LogInfo("Current phase automated Service $current_service->name Broadcast completed");
    $loggerObj->LogInfo("Retry operation has been initiated");
    services::log_service_process($service_local_id, 3, 'Retry operation has been initiated', 1);
} else {
    $loggerObj->LogInfo("Current phase automated Service $current_service->name Broadcast completed!");
}

/* free up database system memory */
db::close();


