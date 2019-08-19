<?php
/**
 * User: Mecxi
 * Date: 5/16/2017
 * Time: 4:50 PM
 * @overview: control service  notification and schedulers loaders
 */
require_once('../config.php');

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

if (thread_ctrl::running_threads("thread_bulk_sync.php") < 3) {

    /* initialise exclusive service bulk sync
        during the cleanup process wait 5 min before loading notification.
        during that time the system is busy cleaning and archiving data
    */

    $current_time = date('H:i');
    if ($current_time > '23:30' && $current_time < '23:40'){
        // Wait during system cleanup
    } else {
        pharma_service_bulk_sync(2);
    }


    $current_weekday = date('D');

    switch ($current_weekday) {
        case 'Mon':
            /* process: cross_sell, sponsors */
//        start_cross_notification('campaign_cross_sell', 'advert', $current_weekday, 2);
//        start_cross_notification('sponsors_cross_sell', 'sponsors', $current_weekday);
            break;
        case 'Tue':
            /* process: cross_sell, loyalty */
//        start_cross_notification('campaign_cross_sell', 'advert', $current_weekday, 2);
//        start_cross_notification('loyal_entry_allocation', 'closing_2', $current_weekday, 1);
            break;
        case 'Wed':
            /* process: cross_sell */
//        start_cross_notification('campaign_cross_sell', 'advert', $current_weekday, 2);
//        start_cross_notification('sponsors_cross_sell', 'sponsors', $current_weekday);
            break;
        case 'Thu':
            /* process: cross_sell */
//        start_cross_notification('campaign_cross_sell', 'advert', $current_weekday, 2);
            break;
        case 'Fri':
            /* process: cross_sell, sponsors */
//        start_cross_notification('campaign_cross_sell', 'advert', $current_weekday, 2);
//        start_cross_notification('sponsors_cross_sell', 'sponsors', $current_weekday);
//        start_cross_notification('loyal_entry_allocation', 'closing_2', $current_weekday, 1);
            break;
    }

}

/* collect users to notify */
function collect_users($service_id){
    if ($service_id == 1){
        return user::fetch_active_subs();
    } else {
        return user::fetch_active_add_subs($service_id);
    }
}

/* prepare notify  */
function start_cross_notifies($msisdn, $notify_type, $service_id){
    services::prepare_notify(array($msisdn, thread_ctrl::get_unique_trace_id(), 1), $notify_type, null, null, 0, $service_id);
}


/* prepare cross notification */
function start_cross_notification($cross_sell_name, $sell_type, $current_weekday, $service_id_arg=null){
    if (is_null($service_id_arg)){
        $services = array(1,2);
        foreach($services as $service_id){
            /* check the schedule time */
            $broadcast_settings = settings::get_system_services_scheduled($cross_sell_name);
            if (date('H:i:s') > $broadcast_settings['start_time']){
                /* check if current cross_sell has been completed */
                if (!services::has_completed_cross_sell($cross_sell_name, $current_weekday, $service_id)){
                    /* log current thread_cross_sell */
                    if (services::log_cross_sell($cross_sell_name, $current_weekday, $service_id)){
                        /* collect users to notify */
                        $users_list = collect_users($service_id);
                        if ($users_list){
                            foreach($users_list as $user){
                                /* cross sell non related service */
                                if ($cross_sell_name == 'campaign_cross_sell'){
                                    if (user::has_current_subscription($user[1], ($service_id == 1) ? 2 : 1) == 0){
                                        start_cross_notifies($user[1], $sell_type, $service_id);
                                    }
                                } else {
                                    start_cross_notifies($user[1], $sell_type, $service_id);
                                }
                            }
                        }
                        /* close current thread as completed */
                        services::log_cross_sell($cross_sell_name, $current_weekday, $service_id, true);
                    }
                }
            }
        }
    } else {
        /* check the schedule time */
        $broadcast_settings = settings::get_system_services_scheduled($cross_sell_name);
        if (date('H:i:s') > $broadcast_settings['start_time']){
            /* check if current cross_sell has been completed */
            if (!services::has_completed_cross_sell($cross_sell_name, $current_weekday, $service_id_arg)){
                /* log current thread_cross_sell */
                if (services::log_cross_sell($cross_sell_name, $current_weekday, $service_id_arg)){
                    /* collect users to notify */
                    $users_list = collect_users($service_id_arg);
                    if ($users_list){
                        foreach($users_list as $user){
                            /* cross sell non related service */
                            if ($cross_sell_name == 'campaign_cross_sell'){
                                if (user::has_current_subscription($user[1], ($service_id_arg == 1) ? 2 : 1) == 0){
                                    start_cross_notifies($user[1], $sell_type, $service_id_arg);
                                }
                            } else {
                                start_cross_notifies($user[1], $sell_type, $service_id_arg);
                            }
                        }
                    }
                    /* close current thread as completed */
                    services::log_cross_sell($cross_sell_name, $current_weekday, $service_id_arg, true);
                }
            }
        }
    }
}

/* collect available service users notify data as the per their flag,
if the next notify is on time, load it on the notify queue */
function pharma_service_bulk_sync($service_base_code_id){
    global $loggerObj;
    $service_local_id = service_base_code::get_service_local_id_base_code($service_base_code_id);
    $service_data = bc_pharma::service_notify_available_data();
    if ($service_data){
        foreach($service_data as $data){
            $next_notify_slot = bc_pharma::get_service_next_notify_slot($data['freq_h'], $data['last_notify']);
            if (date('Y-m-d H:i') > $next_notify_slot){
                $loggerObj->LogInfo("Starting Pharma notify service - MSISDN : ". user::get_user_msisdn($data['user_id']));
                /* log current notify to trace report */
                if (is_numeric(services::prepare_datasync_notify(array(user::get_user_msisdn($data['user_id']), thread_ctrl::get_unique_trace_id(), $data['service_request_id']), 'pharma', null, null, rand(0, 1), $service_local_id))){
                    /* update current service data */
                    bc_pharma::update_service_notify($data['service_request_id'], $data['user_id'], $data['flag']);
                }
            }
        }
    }
}



/* free up resources */
db::close();
