<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * The script will be servicing level 1 on user demand like new subscription/re-subscription/User answer,
 * service level 2 daily content and service level 3 generate a unique for the Glam Squad App to authenticate the user
 * please note that this request is initiated via httpd/apache sent through SMS or USSD gateway after a successful
 * subscription/re-subscription
 * The USSD/SMS gateway will void servicing for user who un-subscribe and re-subscribe on the same day based on return
 * result in servicing_subscription module
 *
 */
/* prevent calling this page from the browser without appropriate params */
if (!isset($_GET['service_local_id'])){
    echo "Bad request | Required parameters are missing";
    exit();
}

/* initialise posted data */
$dataPOST = file_get_contents('php://input');
$params = json_decode($dataPOST, true);
$service_local_id = $_GET['service_local_id'];
$user_id = $params['user_id'];
$msisdn = $params['msisdn'];
$message = $params['message'];
$gwtype = $params['gwtype'];

$loggerObj = ($gwtype == 'sms') ?
                new KLogger (LOG_FILE_PATH."SMS_Gateway".LOG_DATE.".log", KLogger::DEBUG ) :
                new KLogger (LOG_FILE_PATH."USSD_Gateway".LOG_DATE.".log", KLogger::DEBUG );

/* determine the demand type */
$service_demand_type = ($message == 'subscribe') ? 'Subscription': 'MO Response-Request';

$current_service = new services($service_local_id);

$loggerObj->LogInfo('Starting service on demand | '. $service_demand_type.' | '.$current_service->name.' | '. $msisdn);

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
} else if ($current_service->broadcast_type == 1) {
    /* check if there's any content uploaded */
    if (services::has_content_related($current_service)){
        $is_service_available = true;
    } else {
        /* proceed if cross_sell is enabled */
        if ($current_service->cross_sell_services){
            $is_service_available = true;
        }
    }
} else {
    /* service on demand has exclusive type
    the content availability is defined by the service rule based on the user request */
    $is_service_available = true;
}

if ($is_service_available === true){
    /* check service type */
    if ($current_service->service_type == 1){
        if (start_content_services($current_service, $user_id, $message, $gwtype, $service_demand_type)){
            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo start_trivia_services($user_id, $service_local_id, $message, null, rand(0, 1), $service_demand_type);
    }
} else {
    /* check if the current trivia service has cross sell enable to proceed */
    if ($current_service->service_type == 2 && $current_service->cross_sell_on_sub == 1){
        echo start_trivia_services($user_id, $service_local_id, $message, null, rand(0, 1), $service_demand_type);
    } else {
        echo 'error';
    }
}


function start_content_services($current_service, $user_id, $message, $gwtype, $service_demand_type){
    global $loggerObj;
    /* check for content broadcast type 1, if there's a content available for this user */
    if ($current_service->broadcast_type == 1){
        if (is_null(services::is_content_service_available($current_service->service_local_id, $user_id))){
            services::add_skipped_users($current_service->service_local_id, $user_id);
            $loggerObj->LogInfo('Service '. $current_service->name.' has been skipped | MSISDN: '. user::get_user_msisdn($user_id));
            return false;
        }
    }
    /* log current thread for tracking operation time */
    if ($service_demand_type == 'Subscription'){
        thread_ctrl::log($user_id, 0, $current_service->service_local_id);
    }

    /* wait a random number of sec before starting current thread */
    $wait_period = rand(0, 5);
    thread_ctrl::run(null, null, "php threading_contents.php $user_id $current_service->service_local_id $wait_period $gwtype $message");
    return true;
}

function start_trivia_services($user_id, $service_local_id, $message, $trace_id, $sleep, $service_demand_type){
    /* log current thread for tracking operation time */
    if ($service_demand_type == 'Subscription'){
        thread_ctrl::log($user_id, 0, $service_local_id);
    }

    $result = thread_ctrl::run(null, null, "php process_datasync.php $user_id $service_local_id $message $trace_id $sleep");
    return $result[0];
}

/* close db */
db::close();


