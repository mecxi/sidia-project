<?php
require_once('../config.php');
$loggerObj = new KLogger (LOG_FILE_PATH."USSD_REQUEST".LOG_DATE.".log", KLogger::DEBUG );

/* SP authentication credential */
$timestamp = date('YmdHis');
$password = '2420110005540'.'Huawei123'.$timestamp;
$hashedPassword = md5($password);

$loggerObj->LogInfo("MD5 hashed password; $hashedPassword");

/* determine request type */
$request_type = $argv[1];
$xmlPostData = null;
$correlator_id = '56717';

/* Correlator ID that associates a startUSSDNotificationRequest message with an stopUSSDNotificationRequest message.
When the App sends a startUSSDNotificationRequest message to the SDP, the SDP records the correlator ID. When the App
sends an stopUSSDNotificationRequest message to the SDP, the SDP disables the MO USSD messages notification based on
the correlator ID. The value is a random number defined by a third party and must be unique.
[Example] 12345 */

/* Essential parameters:
    # endpoint : app url to receive incoming request | Service address to which an SMS message is sent.
    # ussdServiceActivationNumber : The value is planned and allocated by carriers.
                                    A Developer or an API Partner must contact the carrier.
                                    [Example]
                                    *1234*356#
*/

if ($request_type == 'start'){
    $loggerObj->LogInfo("Starting USSD Service with SDP");
    $xmlPostData = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/osg/ussd/notification_manager/v1_0/local">
          <soapenv:Header>
            <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
                <tns:spId>2420110005540</tns:spId>
                <tns:spPassword>'.$hashedPassword.'</tns:spPassword>
                <tns:serviceId>242012000013589</tns:serviceId>
                <tns:timeStamp>'.$timestamp.'</tns:timeStamp>
            </tns:RequestSOAPHeader>
          </soapenv:Header>
          <soapenv:Body>
            <loc:startUSSDNotification>
                <loc:reference>
                    <endpoint>http://localhost/mtnpromo/gateway/ussd</endpoint>
                    <interfaceName>notifyUssdReception</interfaceName>
                    <correlator>'.$correlator_id.'</correlator>
                </loc:reference>
                <loc:ussdServiceActivationNumber>*166#</loc:ussdServiceActivationNumber>
            </loc:startUSSDNotification>
          </soapenv:Body>
        </soapenv:Envelope>';
} else if ($request_type == 'stop'){
    $loggerObj->LogInfo("Stopping USSD Service with SDP Correlator");
    $xmlPostData =
        '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/osg/ussd/notification_manager/v1_0/local">
            <soapenv:Header>
                <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
                <tns:spId>242012000013589</tns:spId>
                <tns:spPassword>'.$hashedPassword.'</tns:spPassword>
                <tns:timeStamp>'.$timestamp.'</tns:timeStamp>
                </tns:RequestSOAPHeader>
            </soapenv:Header>
            <soapenv:Body>
                <loc:stopUSSDNotification>
                    <loc:correlator>'.$correlator_id.'</loc:correlator>
                </loc:stopUSSDNotification>
            </soapenv:Body>
        </soapenv:Envelope>';
} else {
    $loggerObj->LogInfo('USSD Service with SDP FAILED - Pass correct argument: start|stop in exec command');
}

if ($xmlPostData){
    $loggerObj->LogInfo("======================== MOBIAPPS START/STOP MTN PROMO - USSD SERVICE =======================");
    $loggerObj->LogInfo($xmlPostData);
    $result = gateway::curlHTTPRequestXML('https://41.206.4.162:8443/USSDNotificationManagerService/services/USSDNotificationManager', $xmlPostData);
    $loggerObj->LogInfo("======================== MTN SOUTH AFRICA USSD API RESPONSE =======================");
    $loggerObj->LogInfo(print_r($result, true));
}





