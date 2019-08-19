<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * Servicing users subscription/un-subscription/re-subscription requests operation on user demand
 */

$loggerObj = new KLogger (LOG_FILE_PATH."SUBSCRIPTION".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Incoming subscription / unsubscribed / re-subscribed request !!!!!!!");

if (isset($_GET['mode'])){
    /* process | subscription : un-subscribe / re-subscribe request */
    $request = $_GET['mode'];
    $msisdn = $_GET['msisdn'];
    $service_id = $_GET['serviceID'];
    $product_id = $_GET['productID'];
    /* subscribe request */
    if ($request == 'subscribe'){
        /* start rolling new subscription */
        $report = user::start_unroll_process($msisdn, $service_id, $product_id);
        //$loggerObj->LogInfo("report = ". print_r($report, true));
        if ($report['result'] == true){
            $loggerObj->LogInfo($report['message']);
            /* start a broadcast for today trivia for current user_id */
            echo "success,1,".$report['message'];
        } else {
            /* check state report to determine next process */
            switch($report[0]){
                case 2:
                    /* user has been created without stats | very unlikely to happen | report success and notify dev tea
                $message = explode(',', $report['message']);
                $loggerObj->LogInfo($message[1]);
                $user_id = $message[0];
                /* start a broadcast for today trivia for current user_id */
                    echo "success,2,".$report['message'];
                    break;
                case 3:
                    /* a database system failed | notify dev  tea */
                    $loggerObj->LogInfo($report['message']);
                    echo 'error,3,'.$report['message'];
                    break;
                case 8:
                    /* user has been successful re-subscribed */
                    $loggerObj->LogInfo($report['message']);
                    echo 'success,8,'.$report['message'];
                    break;
                case 9:
                    /* fails to re-subscribe the user | database system is down */
                    $loggerObj->LogInfo($report['message']);
                    echo 'error,9,'.$report['message'];
                    break;
                default:
                    /* a user has an existing subscription */
                    $loggerObj->LogInfo($report['message']);
                    echo 'success,4,'.$report['message'];
                    break;
            }

        }

        /* un-subscribe request */
    } else if ($request == 'unsubscribe'){
        $report = user::start_derolling_process($msisdn, $service_id, $product_id);
        switch($report['result']){
            case 5:
                /* user not found in the system */
                $loggerObj->LogInfo($report['message']);
                echo 'deactivated,5,'.$report['message'];
                break;
            case 6:
                /* user un-subscribed successfully */
                $loggerObj->LogInfo($report['message']);
                echo 'deactivated,6,'.$report['message'];
                break;
            case 7:
                /* database system is down */
                $loggerObj->LogInfo($report['message']);
                echo 'deactivated,7,'.$report['message'];
                break;
            default:
                /* user has no running services */
                $loggerObj->LogInfo($report['message']);
                echo 'deactivated,10,'.$report['message'];
                break;
        }
    }
} else {
    /* No parameters | report error */
    $loggerObj->LogInfo('Error - No parameters supplied');
    echo 'error';
}

