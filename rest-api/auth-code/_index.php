<?php
/**
 * @author: Mecxi Musa
 * Web service API : Unique Code Verification
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
    case 'verify_code':
        /* login request required username, password */
        echo (is_null($params)) ?
            json_encode(array('error'=> restapi::custom_errors(405)), true)
            : json_encode((count($params) > 1 && ((isset($params['phone']) && isset($params['code'])) || isset($params['type'])))
                ? code::verify($params['phone'], $params['code'], (isset($params['type'])) ? $params['type'] : null) : restapi::custom_errors(406), true);
        break;
    case null :
    case 'fail':
        /* wrong method call or empty */
        echo json_encode(array('error'=> restapi::custom_errors(400)), true);
        break;
}

/* free up database system memory */
db::close();