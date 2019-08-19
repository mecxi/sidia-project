<?php
/**
 * @author: Mecxi Musa
 * Web service API : tools
 */

require_once('../../config.php');

/* set response header */
restapi::setHttpHeaders($_SERVER['HTTP_ACCEPT'], 200);

/*
controls the RESTful services URL mapping
*/
$request = isset($_GET['request']) ? $_GET['request'] : null;

/* retrieve required parameters */
$params = json_decode(file_get_contents('php://input'), true);

/* required params for services upload */
$upload_params = (isset($_POST)) ? $_POST : null;
$service_file = (isset($_FILES['file'])) ? $_FILES['file']: null;

switch($request){
    case 'preview':
        /* draw players preview */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 2 && ( isset($params['service_draw_id']) && isset($params['date_range']) ))
                ? service_draw::get_players_preview($params['service_draw_id'], $params['date_range'], null, true) : restapi::custom_errors(406), true);
        break;
    case 'winners':
        /* weekly or monthly winner report */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 2 && ( isset($params['service_draw_id']) && isset($params['date_range']) ))
                ? service_draw::fetch_draw_winners($params['service_draw_id'], $params['date_range']) : restapi::custom_errors(406), true);
        break;
    case 'raffle':
        /* select weekly or monthly winner */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 5 && (isset($params['service_draw_id']) && isset($params['date_range']) && isset($params['type']) && isset($params['loginID']) && isset($params['remote_address'])))
                ? service_draw::process_draw_winner($params['service_draw_id'], $params['date_range'], $params['type'], $params['loginID'], $params['remote_address']) : restapi::custom_errors(406), true);
        break;
    case 'range':
        /* return weekly or monthly range */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode( (count($params) == 2 && ( isset($params['date_range']) && isset($params['draw_type_id']) ))
                ? service_draw::get_selected_date_range($params['date_range'], $params['draw_type_id'], true): restapi::custom_errors(406), true);
        break;
    case 'reset':
        /* reset weekly or monthly winner */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode( (count($params) == 4 && (isset($params['service_draw_id']) && isset($params['date_range']) && isset($params['loginID']) && isset($params['remote_address'])) )
                ? service_draw::reset_draw_winners($params['service_draw_id'], $params['date_range'], $params['loginID'], $params['remote_address']): restapi::custom_errors(406), true);
        break;
    case 'notify':
        /* notify current winners */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode( (count($params) == 2 && (isset($params['service_draw_id']) && isset($params['date_range'])) )
                ? service_draw::notify_draw_winners($params['service_draw_id'], $params['date_range']): restapi::custom_errors(406), true);
        break;
    case 'web-auth':
        /* request sp credentials for web-recruitment process */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode(( count($params) == 3 && (isset($params['host']) && isset($params['msisdn']) && isset($params['serviceID'])) ) ?
                web_recruit::request_web_auth($params['host'], $params['msisdn'], $params['serviceID']) : restapi::custom_errors(406), true);
        break;
    case 'upload':
        /* services upload */
        echo (is_null($upload_params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode(( count($upload_params) == 2 && (isset($upload_params['service_id']) && isset($upload_params['load_perday'])) ) ?
                services::process_upload_services($upload_params['service_id'], $upload_params['load_perday'], $service_file) : restapi::custom_errors(406), true);

        break;
    case 'list':
        /* return service list */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode(( count($params) == 3 && (isset($params['category']) && isset($params['type']) && isset($params['service_id'])) )
                ? services::process_list_request($params['category'], $params['type'], $params['service_id']) : restapi::custom_errors(406), true);
        break;
    case 'update':
        /* update target service */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode(( count($params) == 5 && (isset($params['category']) && isset($params['data']) && isset($params['service_id']) && isset($params['process']) && isset($params['type'])) )
                ? services::process_service_update($params['category'], $params['data'], $params['service_id'], $params['process'], $params['type']) : restapi::custom_errors(406), true);
        break;
    case 'add':
        /* add a new service */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode(( count($params) == 2 && (isset($params['data']) && isset($params['type'])) )
                ? services::process_service_creation($params['data'], $params['type']) : restapi::custom_errors(406), true);
        break;
    case null :
    case 'fail':
        /* wrong method call or empty */
        echo json_encode(array('error'=> restapi::custom_errors(400)), true);
        break;
}

/* free up database system memory */
db::close();