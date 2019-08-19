<?php
/* set display errors */
error_reporting(E_ALL); ini_set('display_errors', 1);
/**
 * Demo - SDP simulate syncOrderRelationshipResponse
 *
 */

/* Case | Incoming SMS request - parse required params */
/* initialise incoming data */
$dataPOST = file_get_contents('php://input');

$sdpXMLRequest = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $dataPOST));
$xml = new SimpleXMLElement($sdpXMLRequest);
$sdpConvertedRequest = json_decode(json_encode((array)$xml), TRUE);

/* Retrieve required parameters */
$receiptRequest = (isset($sdpConvertedRequest['soapenvBody']['locsyncOrderRelationResponse']['locresult']))?
    $sdpConvertedRequest['soapenvBody']['locsyncOrderRelationResponse']['locresultDescription'] :
    null;

/* notify receipt Request */
if ($receiptRequest){
    echo sendSMSResponse();
} else {
    echo 'BAD REQUEST';
}



//echo '<pre>'. print_r($sdpConvertedRequest, true).'</pre>';




/* obtain status reports of the request
error that might be return in result:
SVC0001
Service error. The error code is %1.
SVC0002
Invalid input value.
SVC0280
The length exceeds the threshold.
SVC0901
Access authentication fails.
SVC0905
Parameter error.
POL0003
There are too many addresses.
POL0006
The function of sending a message to a group is not supported.
POL0900
The bulk messaging function is not supported.
POL0904
The message sending rate exceeds the threshold.*/

function sendSMSResponse(){
    /* simulate a case whereby the sms is not forwarded */
    $result = rand(0,3);
    $errorList = return_random_error();
    if ($result){
        return '<?xml version="1.0" encoding="utf-8" ?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <soapenv:Body>
                <ns1:sendSmsResponse xmlns:ns1="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local">
                    <ns1:result>100211200201161126212853510091</ns1:result>
                </ns1:sendSmsResponse>
            </soapenv:Body>
        </soapenv:Envelope>';
    } else {
        return '<?xml version="1.0" encoding="utf-8" ?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <soapenv:Body>
                <ns1:sendSmsResponse xmlns:ns1="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local">
                    <ns1:result>
                        <errorCode>'.$errorList['code'].'</errorCode>
                        <Description>'.$errorList['msg'].'</Description>
                    </ns1:result>
                </ns1:sendSmsResponse>
            </soapenv:Body>
        </soapenv:Envelope>';
    }
}

function return_random_error(){
    $i = rand(0, 8);
    $errorList = array(
        array('code'=>'SVC0001', 'msg'=>'Service error. The error code is %1'),
        array('code'=>'SVC0002', 'msg'=>'Invalid input value.'),
        array('code'=>'SVC0280', 'msg'=>'The length exceeds the threshold.'),
        array('code'=>'SVC0901', 'msg'=>'Access authentication fails.'),
        array('code'=>'SVC0905', 'msg'=>'Parameter error.'),
        array('code'=>'POL0003', 'msg'=>'There are too many addresses.'),
        array('code'=>'POL0006', 'msg'=>'The function of sending a message to a group is not supported.'),
        array('code'=>'POL0900', 'msg'=>'The bulk messaging function is not supported.'),
        array('code'=>'POL0904', 'msg'=>'The message sending rate exceeds the threshold.')
    );
    return $errorList[$i];
}
