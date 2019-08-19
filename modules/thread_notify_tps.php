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

    /* check if the service has launched and broadcast is enabled */
    if (thread_ctrl::service_has_started($start_date, $end_date)){
        prepare_services_thread($set_thread_services, $service_local_id, $set_thread);
    }
}


/* initialise retries process */
function prepare_notify_process(&$set_thread_services, $user_id, $score, $trace_id, $trivia_id, $sleep, $service_local_id, $msisdn){
    $set_thread_services .= "& php process_notify.php $user_id $score $trace_id $trivia_id $sleep $service_local_id $msisdn";
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
    $data = services::get_data_notify($service_local_id);
    if ($data){
        $limit = 0;
        $tracker = 0;
        /* process notify */
        foreach($data as $user){
            ++$tracker;
            /* initialise the gateway needed fields */
            $user_id = $user['user_id'];
            $score = $user['score'];
            $trace_id = $user['trace_id'];
            $trivia_id = $user['trivia_id'];
            $sleep = $user['sleep'];
            $msisdn = $user['msisdn'];
            /* prepare current thread */
            prepare_notify_process($set_thread_services, $user_id, $score, $trace_id, $trivia_id, $sleep, $service_local_id, $msisdn); ++$limit;

            if ($limit > $set_thread) {
                process_threads($set_thread_services); $limit = 0;
            } else {
                if (count($data) == $tracker){
                    process_threads($set_thread_services);
                }
            }
        }
    }
}
