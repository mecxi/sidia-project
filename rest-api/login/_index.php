<?php
/**
 * @author: Mecxi Musa
 * Web service API : Login
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
    case 'login':
        /* login request required username, password */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) == 3 && (isset($params['username']) && isset($params['password']) && isset($params['remote_address'])))
                ? login::verify_request($params['username'], $params['password'], $params['remote_address']) : restapi::custom_errors(406), true);
        break;
    case 'logout':
        /* logout request required login_id */
        echo (is_null($params)) ?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode((count($params) < 4 && (isset($params['login_id']) && isset($params['remote_address']))) ?
                ((isset($params['priority'])) ? login::record_session_end($params['login_id'], $params['remote_address'], $params['priority']):
                    login::record_session_end($params['login_id'], $params['remote_address'])) : restapi::custom_errors(406), true);
        break;
    case 'register':
        /* incoming registration request */
        echo (is_null($params))?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode((count($params) == 4 && (isset($params['fullname']) && isset($params['email']) && isset($params['phone']) &&
                isset($params['password']))) ? login::register($params['fullname'], $params['email'], $params['phone'], $params['password']) :
                restapi::custom_errors(406), true);
        break;
    case 'auth':
        /* check token to confirm registration */
        echo (is_null($params))?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode((count($params) == 2 && (isset($params['phone']) && isset($params['token']))) ?
                login::verify_token($params['phone'], $params['token']) : restapi::custom_errors(406), true);
        break;
    case 'reset':
        /* resetting your password */
        echo (is_null($params))?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode((count($params) == 2 && (isset($params['phone']) && isset($params['password']))) ?
                login::reset($params['phone'], $params['password']) : restapi::custom_errors(406), true);
        break;
    case 'alive':
        /* keep-alive current session */
        echo (is_null($params))?
            json_encode(array('error'=>restapi::custom_errors(405)), true)
            : json_encode((count($params) == 2 && (isset($params['loginID']) && isset($params['remote_address']))) ?
                login::keep_alive_session($params['loginID'], $params['remote_address']) : restapi::custom_errors(406), true);
        break;
    case null :
    case 'fail':
        /* wrong method call or empty */
        echo json_encode(array('error'=> restapi::custom_errors(400)), true);
        break;
}

/* free up database system memory */
db::close();
