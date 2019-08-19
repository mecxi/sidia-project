<?php
/**
 * @author: Mecxi Musa
 * Web service API : Users
 */

require_once('../../config.php');

/* set response header */
restapi::setHttpHeaders($_SERVER['HTTP_ACCEPT'], 200);

/*
controls the RESTful services URL mapping
*/
$request = null;
if (isset($_GET['request'])){
    $request = $_GET['request'];
}
/* retrieve required parameters */
$params = json_decode(file_get_contents('php://input'), true);

switch($request){
    case 'total_subs':
        /* get totals subs */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['type'])))
                ? user::fetch_sum_subscribers($params['type']) : restapi::custom_errors(406), true);
        break;
    case 'display_subs':
        /* display related subs */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['type'])))
                ? user::fetch_last_subscribers($params['type']) : restapi::custom_errors(406), true);
        break;
    case 'stats':
        /* get user current services profile */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['msisdn'])))
                ? user::user_profile_stats($params['msisdn']) : restapi::custom_errors(406), true);
        break;
    case 'post':
        /* get user post activity */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['msisdn'])))
                ? user::post_profile_activities($params['msisdn']) : restapi::custom_errors(406), true);
        break;
    case 'activities':
        /* get user post activity */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['msisdn'])))
                ? user::profile_activities($params['msisdn']) : restapi::custom_errors(406), true);
        break;
    case 'q_services_history':
        /* query current user services history and stats */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['msisdn'])))
                ? user::user_services_subscription_historic($params['msisdn']) : restapi::custom_errors(406), true);
        break;
    case 'r_services_related':
        /* request current user service related */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 3 && (isset($params['msisdn']) && isset($params['request']) && isset($params['serviceID'])))
                ? user::request_service_related($params['msisdn'], $params['request'], $params['serviceID']) : restapi::custom_errors(406), true);
        break;
    case null :
    case 'fail':
        /* wrong method call or empty */
        echo json_encode(array('error'=> restapi::custom_errors(400)), true);
        break;
}

/* free up database system memory */
db::close();