<?php
/**
 * app USSD gateway routing
 */
require_once('../config.php');


/* set logger for tracking traffic */
$loggerObj = new KLogger (LOG_FILE_PATH."USSD_Gateway".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Incoming request to the USSD gateway!!!!!!!");
/* initialise incoming data */
$dataPOST = file_get_contents('php://input');
//$loggerObj->LogInfo("================== SDP URL CONTENTS  ================");
//$loggerObj->LogInfo(print_r($dataPOST, TRUE));

/* enable simulation | no communication with SDP */
gateway::$simulation = true;
gateway::$gwTypeUSSD = true;

/* Determine incoming request
For request sent locally a variable mode is enabled */
if (isset($_GET['service_type'])){
    /* log local incoming request */
    //$loggerObj->LogInfo(print_r(json_decode($dataPOST, true), TRUE));
    //$loggerObj->LogInfo(print_r($_GET, true));

    /* To disable simulation, set the field simulation of the gateway object to false above */
    switch($_GET['service_type']){
        case 'trivia':
            /* servicing trivia related services */
            echo gateway::mode_servicing_type($dataPOST, 'trivia');
            break;
        case 'content':
            /* servicing contents related services */
            echo gateway::mode_servicing_type($dataPOST, 'content');
            break;
        case 'others':
            /* servicing others */
            echo gateway::mode_servicing_type($dataPOST, 'others');
            break;
        case 'token':
            /* servicing token authentication */
            echo gateway::servicing_token_auth($dataPOST);
            break;
    }
} else if (is_string($dataPOST) && $dataPOST == '') {
    echo '<p style="color:red; font-size: large">BAD REQUEST</p>';
} else {
    /* SDP incoming request */
    if (gateway::$simulation){
        echo gateway::process_sdp_request($dataPOST);
    } else {
        gateway::process_sdp_request($dataPOST);
    }
}

