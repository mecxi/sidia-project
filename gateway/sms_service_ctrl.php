<?php
require_once('../config.php');
$loggerObj = new KLogger (LOG_FILE_PATH."SMS_REQUEST".LOG_DATE.".log", KLogger::DEBUG );


$production = false;

/* determine request type */
$request_type = (isset($argv[1])) ? $argv[1] : null;
$request_service_id = (isset($argv[2])) ? (int)$argv[2] : null;

$xmlPostData = null;
/* set a correlator_id for the requested service */
$collerator_id = thread_ctrl::get_unique_trace_id();
$current_service = new services($request_service_id);
$serviceID = $current_service->service_sdp_id;
$accesscode = $current_service->accesscode;
$spID = $current_service->sp_id;
$credentials = services::service_credentials($request_service_id);

$enabled_serviceID = $current_service->service_type == 3 ? '' : '<serviceId>'. $serviceID.'</serviceId>';

/* Correlator ID that associates a startUSSDNotificationRequest message with an stopUSSDNotificationRequest message.
When the App sends a startUSSDNotificationRequest message to the SDP, the SDP records the correlator ID. When the App
sends an stopUSSDNotificationRequest message to the SDP, the SDP disables the MO USSD messages notification based on
the correlator ID. The value is a random number defined by a third party and must be unique.
[Example] 12345 */

/* Essential parameters:
    # endpoint : app url to receive incoming request | Service address to which an SMS message is sent.
    # smsServiceActivationNumber : The value is planned and allocated by carriers.
                                    A Developer or an API Partner must contact the carrier.
                                    [Format]
                                    tel:Access code
                                    [Example]
                                    tel:1234501

*/

if ($request_type == 'start' && !is_null($request_service_id)){
    $loggerObj->LogInfo("Starting SMS Service with SDP - Service : ". $current_service->name);
    $xmlPostData = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification_manager/v2_3/local">
        <soapenv:Header>
            <RequestSOAPHeader xmlns="http://www.huawei.com.cn/schema/common/v2_1">
                <spId>'. $spID . '</spId>
                <spPassword>' . $credentials['hashedpassword'] . '</spPassword>'.$enabled_serviceID.'
                <timeStamp>' . $credentials['timestamp'] . '</timeStamp>
            </RequestSOAPHeader>
        </soapenv:Header>
        <soapenv:Body>
            <loc:startSmsNotification>
                <loc:reference>
                    <endpoint>'.ENDPOINTS_SMS.'</endpoint>
                    <interfaceName>notifySmsReception</interfaceName>
                    <correlator>'.$collerator_id.'</correlator>
                </loc:reference>
                <loc:smsServiceActivationNumber>'.$accesscode.'</loc:smsServiceActivationNumber>
            </loc:startSmsNotification>
        </soapenv:Body>
    </soapenv:Envelope>';

} else if ($request_type == 'stop' && !is_null($request_service_id)){
    $loggerObj->LogInfo("Stopping SMS Service with SDP - Service : ". $current_service->name);
    $xmlPostData =
        '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification_manager/v2_3/local">
        <soapenv:Header>
            <RequestSOAPHeader xmlns="http://www.huawei.com.cn/schema/common/v2_1">
                <spId>'. $spID.'</spId>
                <spPassword>' . $credentials['hashedpassword'] . '</spPassword>
                '.$enabled_serviceID.'
                <timeStamp>' . $credentials['timestamp'] . '</timeStamp>
            </RequestSOAPHeader>
        </soapenv:Header>
        <soapenv:Body>
            <loc:stopSmsNotification>
                <loc:correlator>'.$collerator_id.'</loc:correlator>
            </loc:stopSmsNotification>
        </soapenv:Body>
    </soapenv:Envelope>';
} else {
    $loggerObj->LogInfo('SMS Service with SDP FAILED - Pass correct argument: arg1 - start|stop arg2 - serviceID in exec command');
}

if ($xmlPostData){
    $loggerObj->LogInfo($xmlPostData);
    $result = gateway::curlHTTPRequestXML(($production == true) ? 'http://'. SDP_PROD_IP.'/SmsNotificationManagerService/services/SmsNotificationManager' :
        'http://'. SDP_TEST_IP.'/SmsNotificationManagerService/services/SmsNotificationManager', $xmlPostData);
    $loggerObj->LogInfo("======================== MTN SMS API RESPONSE =======================");
    $loggerObj->LogInfo(print_r($result, true));
} else {
    $loggerObj->LogInfo("======================== NO PARAMETERS SUBMITED ON YOUR REQUEST param=start || param=stop  =======================");
}





