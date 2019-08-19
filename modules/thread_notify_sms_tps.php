<?php
/**
 * User: Mecxi
 * Date: 4/25/2017
 * Time: 1:04 AM
 * @module_overview: Create sequential threading for the given operation
 * 19 threads per min that serves 950 TPS
 * 3 sec threading intervals
 */

require_once('../config.php');

/* get thread limit set for this service */
$service_list = thread_ctrl::all_service_status();
$set_thread_services = 'echo start ';

foreach($service_list as $service){
    $service_local_id = $service[0];
    $start_date = $service[1];
    $broadcast = $service[2];
    $set_thread = 25;
    $end_date = $service[4];

    if ((new services($service_local_id))->service_type == 1){
        /* check if the service has launched and broadcast is enabled */
        if (thread_ctrl::service_has_started($start_date, $end_date)){
            prepare_services_thread($set_thread_services, $service_local_id, $set_thread);
        }
    }
}


/* initialise retries process */
function prepare_content_process(&$set_thread_services, $user_id, $service_local_id, $message){
    $wait_period = rand(0, 1);
    $set_thread_services .= "& php threading_contents.php $user_id $service_local_id $wait_period sms $message";
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
function prepare_services_thread(&$set_thread_services, $service_local_id, $set_thread){

    /* collect queuing users for notify  */
    $users = services::contents_users($service_local_id);

    if ($users){
        $limit = 0;
        $tracker = 0;
        /* process notify */
        foreach($users as $user_id){
            ++$tracker;
                /* prepare current thread */
                prepare_content_process($set_thread_services, $user_id, $service_local_id, 'start_sms'); ++$limit;

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
