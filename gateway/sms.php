<?php
/**
 * app SMS gateway routing
 */
require_once('../config.php');

/* set logger for tracking traffic */
$loggerObj = new KLogger (LOG_FILE_PATH."SMS_Gateway".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Incoming request to the SMS gateway!!!!!!!");
/* initialise incoming data */
$dataPOST = file_get_contents('php://input');
$loggerObj->LogInfo("================== SDP URL CONTENTS  ================");
//$loggerObj->LogInfo(print_r($dataPOST, TRUE));

/* enable simulation | no communication with SDP */
gateway::$simulation = true;
gateway::$gwTypeSMS = true;
gateway::$production = false;

/* Determine incoming request
For request sent locally a variable mode is set */
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
} else if (isset($_GET['transactionId']) || isset($_GET['result'])){
    /* Incoming SDP online subscription response */
    $loggerObj->LogInfo("SDP Web Recruit Response -  TransactionID : ". $_GET['transactionId']);
    /* check the return result to notify user */
   $result = web_recruit::response_web_auth($_GET);
    /* redirect user to glam promo page */
    if (is_null($result) || $result == 0){
        header('location: http://trivia.sidia.co.za/promo/glam?result=0');
    } else {
        header('location: http://trivia.sidia.co.za/promo/glam?result='.$result);
    }
//    echo '<pre>'. print_r($_GET, true).'</pre>';
//    echo '<pre>'. print_r(getallheaders(), true).'</pre>';
    //header('location: http://trivia.sidia.co.za/promo/glam?result='.$result);
    
} else if (is_string($dataPOST) && $dataPOST == '') {
    /* bad request */
    echo '<p style="color:red; font-size: large">BAD REQUEST</p>';
} else{
    /* SDP incoming request */
    if (gateway::$simulation){
        $process_result = gateway::process_sdp_request($dataPOST);
        /* syncOrderRelationship process result will be an array */
        if (is_array($process_result)){
            echo (isset($process_result[0])) ? $process_result[0] : $process_result[1];
        } else {
            echo $process_result;
        }
    } else {
        $process_result = gateway::process_sdp_request($dataPOST);
        if (gateway::$interface){
            echo $process_result;
            $loggerObj->LogInfo($process_result);
        } else {
            if (is_array($process_result)){
                /* for syncOrderRelationship process print the result on LIVE */
                echo $process_result[1];
            }
        }
    }
}

