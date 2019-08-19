<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * Start retry operation for services that fails due to a network communication issues.
 * Primary gateway : SMS
 */

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Automated Content Services Retries has started");

$set_thread_services = 'echo start ';

/* check currently running thread */
$running_process = thread_ctrl::running_threads('threading_contents.php');

/* process | start broadcast services */
$service_list = thread_ctrl::all_service_status();
foreach($service_list as $service) {
    $service_local_id = $service[0];
    $start_date = $service[1];
    $broadcast = $service[2];
    $set_thread = $service[3];
    $end_date = $service[4];
    $service_name = $service[5];
    $start_error_related = '';

    if ($running_process < $set_thread) {
        $allocated_res = $set_thread - $running_process;

        /* check if the service has launched and broadcast is enabled */
        if (thread_ctrl::service_has_started($start_date, $end_date)){
            start_retry_process($set_thread_services, $service_local_id, $allocated_res);
        } else {
            if (date('Y-m-d H:i:s') > $end_date){
                $loggerObj->LogInfo('Service '. $service_name. ' has closed | closing_date: '. $end_date);
                $start_error_related = 'Service '. $service_name. ' has closed | closing_date: '. $end_date;
            } else {
                $loggerObj->LogInfo('Service '. $service_name. ' has not started | starting_date: '. $start_date);
                $start_error_related = 'Service '. $service_name. ' has not started | starting_date: '. $start_date;
            }
        }

        /* log error */
        if (strlen($start_error_related) > 0){
            services::log_service_process($service_local_id, 3, $start_error_related, 0);
        }
    }
}

/* initialise retries process */
function prepare_retries_process(&$set_thread_services, $user_id, $service_local_id, $message){
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

function start_retry_process(&$set_thread_services, $service_local_id, $set_thread){
    global $loggerObj;
    $current_service = new services($service_local_id);
    $message = 'retries_thread'; //prevent an automated broadcast

    if (in_array($current_service->service_type, array(1, 3))){

        $loggerObj->LogInfo("Retrying service ID ". $service_local_id. " | ". $current_service->name);
        /* check if a retry operation has been set */
        $service_logged_retries = thread_ctrl::has_service_retries($service_local_id);

        if ($service_logged_retries){

            /* fetch active subscribers */
            $users_list = services::retried_users($service_local_id);

            /* collect today charged subscribers for current services */
            $today_billed_users = services::subscribers_services_billed($service_local_id);

            $limit = 0;
            $tracker = 0;
            if ($users_list){
                foreach($users_list as $user_id){

                    $user = new user($user_id); ++$tracker;

                    if (!in_array($user->msisdn, $today_billed_users)){
                        /* check if current user is still in free trial period to applied service
                        a non delivery might be the cause of network error */
                        if ($current_service->free_period){
                            if (services::has_free_period_service_billed($user->services_stats, $service_local_id)){
                                services::update_retries_suspension($user_id, $service_local_id, 0);
                                /* load current user */
                                prepare_retries_process($set_thread_services, $user_id, $service_local_id, $message); ++$limit;
                            }
                        } else {
                            /* check if the current product billing is on a due date */
                            if (services::has_selected_billing_cycle_due($user->services_stats, $service_local_id)){
                                services::update_retries_suspension($user_id, $service_local_id, 1);
                            } else {
                                services::update_retries_suspension($user_id, $service_local_id, 0);
                            }
                        }
                    } else {
                        /* check if user hasn't already un-subscribed */
                        if ( user::has_current_subscription($user->services_stats, $service_local_id)){
                            services::update_retries_suspension($user_id, $service_local_id, 0);
                            /* load current user */
                            prepare_retries_process($set_thread_services, $user_id, $service_local_id, $message); ++$limit;
                        }
                    }

                    if ($limit > $set_thread) {
                        process_threads($set_thread_services); $limit = 0;
                    } else {
                        if (count($users_list) == $tracker){
                            process_threads($set_thread_services);
                        }
                    }
                }
            }
        }

        $broadcast_settings = settings::get_broadcast_scheduled_set(0);

        /* check if during retries, no fail retry is encountered or else set it as completed | retries will be closed 30 minutes before the service closing time */
        if (thread_ctrl::has_service_retries($service_local_id) == 0  && (date('H:i:s') > date('H:i:s', strtotime("".$broadcast_settings['close_time']." - 30 minutes")))){
            /* close schedule retries */
            thread_ctrl::set_retry_operation(2, $service_local_id);

            services::log_service_process($service_local_id, 4, 'Retry operation completed', 1);
        }
    }
}


$loggerObj->LogInfo("Automated Retries Operation has finished successfully");

/* free up database system memory */
db::close();

