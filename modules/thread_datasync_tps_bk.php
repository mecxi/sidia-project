<?php
/**
 * User: Mecxi
 * Date: 4/25/2017
 * Time: 1:04 AM
 * @module_overview: Create sequential threading for the given operation
 */

require_once('../config.php');

/* initialise required variables */
$message = 'pass@';

/* get thread limit set for this service */
/* get thread limit set for this service */
$service_list = thread_ctrl::all_service_status();
$set_thread_services = 'echo start ';

foreach($service_list as $service){
    $service_local_id = $service[0];
    $start_date = $service[1];
    $broadcast = $service[2];
    $set_thread = 10;
    $end_date = $service[4];

    if ((new services($service_local_id))->service_type == 2){
        /* check if the service has launched and broadcast is enabled */
        if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){
            prepare_services_thread($set_thread_services, $service_local_id, $set_thread, $message);
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
    /* collect queuing users queued datasync content  */
    $users = services::get_data_contents_users($service_local_id);
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
                Suspended the question only if the user is also suspended */
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



