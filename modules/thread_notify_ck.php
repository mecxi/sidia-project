<?php
/**
 * User: Mecxi
 * Date: 4/25/2017
 * Time: 1:04 AM
datasync unlock process:
Check the status of current suspended questions in the queue
 */

require_once('../config.php');

/* prevent running retries if one is active */
//if (thread_ctrl::running_threads("thread_run.php") > 0){
//    exit();
//}

if (thread_ctrl::running_threads("thread_notify_ck.php") > 2){
    exit();
}


/* get thread limit set for this service */
$service_list = thread_ctrl::all_service_status();
$set_thread_services = 'echo start ';

/* check currently running thread */
$running_process = thread_ctrl::running_threads('process_notify.php');


foreach($service_list as $service){
    $service_local_id = $service[0];
    $start_date = $service[1];
    $broadcast = $service[2];
    $set_thread = 50; //limit thread to 100 per session
    $end_date = $service[4];

    if ($running_process < $set_thread){
        $allocated_res = $set_thread - $running_process;
        /* check if the service has launched and broadcast is enabled */
        if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){
            prepare_services_thread($set_thread_services, $service_local_id, $allocated_res);
        }
    }
}

/* process threads */

function process_threads(&$set_thread_services){
    if ($set_thread_services != 'echo start '){
        thread_ctrl::run(null, null, $set_thread_services);
    }
    /* reset service threads */
    $set_thread_services = 'echo start ';
}




/* process current service thread */
function prepare_services_thread(&$set_thread_services, $service_local_id, $set_thread){
    $current_service = new services($service_local_id);
    /* process retries */
    $users = services::get_data_notifies($service_local_id, true);
    if ($users){
        /* collect today charged subscribers for current services */
        $today_billed_users = services::subscribers_services_billed($service_local_id);

        /* remove users who are currently pending suspension */
        $limit = 0;
        $tracker = 0;
        foreach($users as $user_id){
            $user = new user($user_id); ++$tracker;
            if (!in_array($user->msisdn, $today_billed_users)){
                /* check if current user is still in free trial period
                a non delivery might be the cause of network error */
                if ($current_service->free_period){
                    if (services::has_free_period_service_billed($user->services_stats, $service_local_id)){
                        /* update current user suspension */
                        services::update_suspended_notify($user->msisdn, 0, $service_local_id);
                        prepare_notify_retries($set_thread_services, $service_local_id, $user_id, $service_local_id); ++$limit;
                    } else {
                        services::update_suspended_notify($user_id, 1, $service_local_id);
                    }
                } else {
                    /* check if the current product billing is on a due date */
                    if (services::has_selected_billing_cycle_due($user->services_stats, $service_local_id)){
                        services::update_suspended_notify($user_id, 1, $service_local_id);
                    } else {
                        services::update_suspended_notify($user_id, 0, $service_local_id);
                    }
                }
            } else {
                /* check if user hasn't already unsubscribed */
                if ( user::has_current_subscription($user->services_stats, $service_local_id)){
                    services::update_suspended_notify($user_id, 0, $service_local_id);
                    prepare_notify_retries($set_thread_services, $service_local_id, $user_id, $service_local_id); ++$limit;
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


function prepare_notify_retries(&$set_thread_services, $service_id, $user_id, $service_local_id){
    /* initialise the gateway needed fields */
    $data = services::get_data_notify($service_id, $user_id);
    if ($data){
        $trace_id = $data[0]['trace_id'];
        $trivia_id = $data[0]['trivia_id'];
        $score = $data[0]['score'];
        $sleep = $data[0]['sleep'];
        $msisdn = $data[0]['msisdn'];
        /* prepare current thread */
        $set_thread_services .= "& php process_notify.php $user_id $score $trace_id $trivia_id $sleep $service_local_id $msisdn";
    }
}