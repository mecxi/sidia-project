<?php
/**
 * User: Mecxi
 * Date: 4/25/2017
 * Time: 1:04 AM
data_sync unlock process:
Check the status of current suspended questions of related service in the queue and retry if necessary
 */

require_once('../config.php');

if ( thread_ctrl::running_threads("thread_datasync_ck.php") > 2){
    exit();
}

/* initialise required variables */
$message = 'pass@';

/* get thread limit set for this service */
$service_list = thread_ctrl::all_service_status();
$set_thread_services = 'echo start ';
/* check current resources running */
$running_process = thread_ctrl::running_threads('process_datasync.php');

foreach($service_list as $service){
    $service_local_id = $service[0];
    $start_date = $service[1];
    $broadcast = $service[2];
    $set_thread = 30;
    $end_date = $service[4];
    $current_service = new services($service_local_id);

    if ($current_service->service_type == 2){
        if ($running_process < $set_thread) {
            $allocated_res = $set_thread - $running_process;
            /* check if the service has launched and broadcast is enabled */
            if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){
                prepare_services_thread($set_thread_services, $service_local_id, $allocated_res, $message);
            }
        }
    }
}


/* initialise retries process */
function prepare_datasync_process(&$set_thread_services, $user_id, $message, $trace_id, $sleep, $service_local_id){
    $set_thread_services .= "& php process_datasync.php $user_id $service_local_id $message $trace_id $sleep";
}

/* execute loaded threads */
function process_threads(&$set_thread_services){
    if ($set_thread_services != 'echo start '){
        thread_ctrl::run(null, null, $set_thread_services);
    }
    /* reset service threads */
    $set_thread_services = 'echo start ';
}

/* process current service thread */
function prepare_services_thread(&$set_thread_services, $service_local_id, $set_thread, $message){
    /* check current data_sync suspended to update their status if their notify has changed */
    $users = services::get_data_contents_users($service_local_id, true);
    if ($users){
        foreach($users as $user){
            /* initialise the gateway needed fields */
            $user_id = $user['user_id'];
            $trivia_id = $user['trivia_id'];
            /* prevent adding users who haven't been notified yet */
            if (services::has_notify_sent($service_local_id, $user_id, $trivia_id)){
                /* update current suspension status */
                services::update_datasync_suspended($service_local_id, $user_id, $trivia_id, true);
            }
        }
    }

    $users = services::get_data_contents_users($service_local_id, true, true);
    if ($users){
        $limit = 0;
        $tracker = 0;
        foreach($users as $user){
            ++$tracker;
            $user_id = $user['user_id'];
            $trace_id = $user['trace_id'];
            $sleep = $user['sleep'];
            $trivia_id = $user['trivia_id'];
            /* prevent adding data_sync where notify hasn't been processed */
            if (services::has_notify_sent($service_local_id, $user_id, $trivia_id)){
                prepare_datasync_process($set_thread_services, $user_id, $message, $trace_id, $sleep, $service_local_id); ++$limit;
            } else {
                /* check if current user has been suspended to avoid locking up resources
                Suspended the question only if the user notify is also suspended */
                if (services::has_notify_suspended($service_local_id, $user_id, $trivia_id)){
                    services::update_datasync_suspended($service_local_id, $user_id, $trivia_id);
                }
            }

            if ($limit > $set_thread) {
                process_threads($set_thread_services); $limit = 0;
            } else {
                if (count($users) == $tracker){
                    process_threads($set_thread_services);
                }
            }
        }
    }
}


