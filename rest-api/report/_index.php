<?php
/**
 * @author: Mecxi Musa
 * Web service API : Reports
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

/* set an exception for billings */
if ($request == 'billings'){
    $request = 'billing';
    $params = array('type'=>'dashboard', 'service'=>'all');
}

switch($request){
    case 'entries':
        /* services entries reports */
        echo (!is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(407)), true)
            : json_encode(array('data'=> services::services_requests_log()), true);
        break;
    case 'payment':
        /* payment request reports */
        echo (!is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(407)), true)
            : json_encode(array('data'=> services::payment_requests_log()), true);
        break;
    case 'broadcast':
        /* daily push reports */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 1 && (isset($params['service_id'])))
                ? services::broadcast_stats($params['service_id']) : restapi::custom_errors(406), true);
        break;
    case 'billing':
        /* glam-Squad billing report */
        echo (is_null($params))?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
             : json_encode((count($params) == 2 && (isset($params['type']) && isset($params['service'])))
                ? services::billing_report($params['type'], $params['service']) : restapi::custom_errors(406), true);
        break;
    case 'traffic':
        /* get mo_traffic */
        echo (!is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(407)), true)
            : json_encode(array('data'=> thread_ctrl::mo_mt_live_traffic()), true);
        break;
    case null :
    case 'fail':
        /* wrong method call or empty */
        echo json_encode(array('error'=> restapi::custom_errors(400)), true);
        break;
}

/* free up database system memory */
db::close();