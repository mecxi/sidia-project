<?php
/**
 * Created by PhpStorm.
 * User: Mecx: 11/4/2017
 * Time: 10:51 PM
 *
 * app payment gateway routing
 */

require_once('../config.php');

/* set logger for tracking traffic */
$loggerObj = new KLogger (LOG_FILE_PATH."PAY_Gateway".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Incoming request to the PAYMENT gateway!!!!!!!");
/* initialise incoming data */
$dataPOST = file_get_contents('php://input');
$loggerObj->LogInfo("================== URL CONTENTS  ================");
//$loggerObj->LogInfo("Data from incoming buffering :". $dataPOST);
//$loggerObj->LogInfo("Determine if encoding format :".  mb_detect_encoding($dataPOST, 'UTF-8', true) ? 'Valid UTF-8': 'Invalid UT8-encoding'); // false);
//$loggerObj->LogInfo("Posted data being decoded :".  urldecode($dataPOST)); // false);

//$loggerObj->LogInfo("Data from incoming POST :". print_r($_POST, true));
//$loggerObj->LogInfo("Data from incoming GET :". print_r($_GET, true));


/* enable simulation | no communication with SDP */
payment::$simulation = true;
payment::$production = false;

/* Determine incoming request */
if ($dataPOST == '') {
    /* bad request */
    echo restapi::custom_errors_xml(401);
    /* set header */
    restapi::setHttpHeaders($_SERVER['HTTP_ACCEPT'], 401);

} else {
    /* determine content_type based on SDP or authorised app */
    switch(payment::determine_content_type($dataPOST)){
        case 'JSON':
            /* App incoming request */
            echo payment::process_app_request($dataPOST);
            break;
        case 'XML':
            /* SDP request */
            $loggerObj->LogInfo(print_r($dataPOST, TRUE));
            echo payment::process_sdp_request($dataPOST);
            break;
        default:
            /* Unauthorised access */
            echo restapi::custom_errors_xml(401);
            /* set header */
            restapi::setHttpHeaders($_SERVER['HTTP_ACCEPT'], 401);
            break;
    }

}