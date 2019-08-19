<?php
/**
 * Demo - SDP simulate SMS sendSmsResponse and notifySmsDeliveryReceipt
 *
 */

require_once('../config.php');

/* Case | Incoming SMS request - parse required params */
/* initialise incoming data */
$dataPOST = file_get_contents('php://input');

$sdpXMLRequest = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $dataPOST));
$xml = new SimpleXMLElement($sdpXMLRequest);
$sdpConvertedRequest = json_decode(json_encode((array)$xml), TRUE);

/* Retrieve required parameters */
$receiptSMSRequest = isset($sdpConvertedRequest['soapenvBody']['locsendSms']['locreceiptRequest']['endpoint'])?
    $sdpConvertedRequest['soapenvBody']['locsendSms']['locreceiptRequest']['endpoint'] : null;

$receiptPaymentRequest = isset($sdpConvertedRequest['soapBody']['ns3processRequest']) ?
    payment::parse_sdp_parameters($sdpConvertedRequest['soapBody']['ns3processRequest']['parameter'], 'ProcessingNumber') : null;


/* notify receipt Request */
if ($receiptSMSRequest){
    echo sendSMSResponse();
}

/* notify receipt payment Request */
if ($receiptPaymentRequest){
    echo sendPaymentRequestResponse($receiptPaymentRequest);
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
                        <errorCode>SVC0001</errorCode>
                        <Description>Service error. The error code is %1.</Description>
                    </ns1:result>
                </ns1:sendSmsResponse>
            </soapenv:Body>
        </soapenv:Envelope>';
    }
}

function sendPaymentRequestResponse($traceid){
    /* simulate a case whereby the payment request response is not forwarded */
    $result = rand(0, 3);
    if ($result == 0){
        /* fails return */
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <soapenv:Body>
                        <ns1:processRequestResponse xmlns:ns1="http://b2b.mobilemoney.mtn.zm_v1.0">
                            <return>
                                <name>ProcessingNumber</name>
                                <value>'. $traceid.'</value>
                            </return>
                            <return>
                                <name>ThirdPartyAcctRef</name>
                                <value>444</value>
                            </return>
                            <return>
                                <name>senderID</name>
                                <value>MOM</value>
                            </return>
                            <return>
                                <name>StatusCode</name>
                                <value>104</value>
                            </return>
                            <return>
                                <name>StatusDesc</name>
                                <value>Authorization declined</value>
                            </return>
                            <return>
                                <name>MOMTransactionID</name>
                                <value>111</value>
                            </return>
                        </ns1:processRequestResponse>
                    </soapenv:Body>
                </soapenv:Envelope>';
    } else if ($result == 1){
        /* pending return */
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <soapenv:Body>
                        <ns1:processRequestResponse xmlns:ns1="http://b2b.mobilemoney.mtn.zm_v1.0">
                            <return>
                                <name>ProcessingNumber</name>
                                <value>'. $traceid.'</value>
                            </return>
                            <return>
                                <name>ThirdPartyAcctRef</name>
                                <value>444</value>
                            </return>
                            <return>
                                <name>senderID</name>
                                <value>MOM</value>
                            </return>
                            <return>
                                <name>StatusCode</name>
                                <value>1000</value>
                            </return>
                            <return>
                                <name>StatusDesc</name>
                                <value>Pending</value>
                            </return>
                            <return>
                                <name>MOMTransactionID</name>
                                <value>111</value>
                            </return>
                        </ns1:processRequestResponse>
                    </soapenv:Body>
                </soapenv:Envelope>';
    } else {
        /* return success */
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <soapenv:Body>
                        <ns1:processRequestResponse xmlns:ns1="http://b2b.mobilemoney.mtn.zm_v1.0">
                            <return>
                                <name>ProcessingNumber</name>
                                <value>'. $traceid.'</value>
                            </return>
                            <return>
                                <name>ThirdPartyAcctRef</name>
                                <value>444</value>
                            </return>
                            <return>
                                <name>senderID</name>
                                <value>MOM</value>
                            </return>
                            <return>
                                <name>StatusCode</name>
                                <value>01</value>
                            </return>
                            <return>
                                <name>StatusDesc</name>
                                <value>Successfully processed transaction</value>
                            </return>
                            <return>
                                <name>MOMTransactionID</name>
                                <value>111</value>
                            </return>
                        </ns1:processRequestResponse>
                    </soapenv:Body>
                </soapenv:Envelope>';
    }
}
