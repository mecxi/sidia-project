<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * start allocating task for each service level operation
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Preparing All Services broadcast Operation phase ...");

/* process | start broadcast services */
$service_list = thread_ctrl::all_service_status();
$set_thread_services = 'echo start ';
$started_services = 0;
foreach($service_list as $service){
    $service_local_id = $service[0];
    $start_date = $service[1];
    $broadcast = $service[2];
    $set_thread = $service[3];
    $end_date = $service[4];
    $service_name = $service[5];
    $start_error_related = '';

    /* check if the service has launched and broadcast is enabled */
    if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){

        $set_thread_services .= "& php thread_manager.php $set_thread  $service_local_id";
        /* log service record */
        services::log_service_process($service_local_id, 1, 'service pool successful', 1);
        ++$started_services;
    } else {
        if (date('Y-m-d H:i:s') > $end_date){
            $loggerObj->LogInfo('Service '. $service_name. ' has closed | closing_date: '. $end_date);
            $start_error_related = 'Service '. $service_name. ' has closed | closing_date: '. $end_date;
        } else if ($broadcast == 0) {
            $loggerObj->LogInfo('Service '. $service_name. ' is not enabled to broadcast');
            $start_error_related = 'Service '. $service_name. ' is not enabled to broadcast';
        } else {
            $loggerObj->LogInfo('Service '. $service_name. ' has not started | starting_date: '. $start_date);
            $start_error_related = 'Service '. $service_name. ' has not started | starting_date: '. $start_date;
        }
    }

    /* log error */
    if (strlen($start_error_related) > 0){
        services::log_service_process($service_local_id, 1, $start_error_related, 0);
    }
}


/* start thread operation
* due some missing contents, the cleaning up operation will never run and prevent normal daily normal broadcast
 * Solution, force cleanup by the closing time
*/
$broadcast_settings = settings::get_broadcast_scheduled_set(0);

if ($set_thread_services != 'echo start '){
    /* check if scheduled thread are all completed */
    if ($started_services == thread_ctrl::completed_thread_all_services()) {
        $loggerObj->LogInfo("All Services broadcast Operation Completed Successfully!");
        thread_ctrl::run('unset_run');
    } else {
        thread_ctrl::run(null, null, $set_thread_services);
    }
} else {
    $loggerObj->LogInfo('No thread has been initialised');
}

db::close();