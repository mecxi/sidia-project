<?php
require_once('../config.php');

error_reporting(E_ALL); ini_set('display_errors', 1);
/* SDP simulation :
features parameters:
     <ns2:msgType>0</ns2:msgType>
| Message type.
? 0: Begin
? 1: Continue
? 2: End
The first request sent by users in a USSD session is of the Begin type. Other requests are of the Continue type.

    <ns2:ussdOpType>1</ns2:ussdOpType>
| USSD operation type.
? 1: Request
? 2: Notify
? 3: Response
? 4: Release
The mapping between the ussdOpType and msgType values in notifyUssdReceptionRequest is as follows:
? msgType=0: ussdOpType=1
? msgType=1: ussdOpType=3
? msgType=2:
? ussdOpType=3 (A user initiates the USSD session.)
? ussdOpType=4 (An partner initiates the USSD session.)
*/


/* Case | Incoming subscribeProduct request - parse required params */
/* initialise incoming data */
$dataPOST = file_get_contents('php://input');
$params = json_decode($dataPOST, true);

$msisdn = (isset($params['msisdn'])) ? $params['msisdn']:null;
$service_id = (isset($params['service_id'])) ? $params['service_id']:null;
$request_type = (isset($params['request'])) ? $params['request']:null;
$request_billing_cycle = (isset($params['billing_cycle'])) ? $params['billing_cycle']:null;
$free_period = isset($params['free_period']) ? $params['free_period'] : null;
$force_result = (isset($params['force_result'])) ? $params['force_result']:null;

//echo 'MSISDN : '. $msisdn. ' - Service_ID '.$service_id.' - Request:'. $request_type. ' - billing_cyle:'. $request_billing_cycle;

/* determine simulation outcomes depending on the request */
$service_syn_id = services::get_sdp_service_id($service_id);
$product_syn_id = services::get_sdp_product_id_by_billing_cycle($service_id, $request_billing_cycle);
$updateType = '';
$updateDesc = '';
$update_value = '';
$update_desc = '';
$free_value = 'false';
if (!is_null($request_type)){
    switch($request_type){
        case 'subscribe':
        case 're-subscribe':
            /* simulate: success 1 or fail 2 */
            $resp = (!is_null($force_result)) ? 1 : rand(1,3);
            switch($resp){  /* SDP subscribe successfully */
                case 1:
                    $updateType = '1';
                    $updateDesc = 'Addition';
                    $updateReason = '';
                    $free_value = $free_period > 0 ? 'true' : 'false';
                    break;
                case 2:
                case 3:
                $updateType = '0';
                $updateDesc = 'Fails-subscribe';
                $update_value = '1';
                $update_desc = '7999: Other errors occur';
                $free_value = 'false';
                break;
            }

            break;
        case 'unsubscribe':
            /* simulate : success | fails */
            $resp = (!is_null($force_result)) ? 2 : rand(2,3);
            switch($resp){
                case 2: /* SDP un-subscribe successfully */
                    $updateType = '2';
                    $updateDesc = 'Deletion';
                    $free_value = 'false';
                    $update_value = rand(1,6);
                    switch($update_value){
                        case 1:
                            $update_desc = 'A user unsubscribes from a product.';
                            break;
                        case 2:
                            $update_desc = 'Rental fails to be collected upon subscription renewal.';
                            break;
                        case 3:
                            $update_desc = 'Unsubscription is triggered when the charging fails upon the first subscription.';
                            break;
                        case 4:
                            $update_desc = 'Unsubscription is triggered when the service status is abnormal.';
                            break;
                        case 5:
                            $update_desc = 'Unsubscription is triggered when the user status is abnormal.';
                            break;
                        case 6:
                            $update_desc = 'Automatical Unsubscription due to User being put in blacklist.';
                            break;
                    }
                    break;
                case 3: /* SDP fails to unsubscribe */
                    $updateType = '0';
                    $updateDesc = 'Fails-unsubscribe';
                    $update_value = '1';
                    $update_desc = '7999: Other errors occur';
                    break;
            }

            break;
        case 'renewal':
            /* simulate : success | fails */
            $resp = (!is_null($force_result)) ? 3 : rand(3,4);
            switch($resp){
                case 3: /* SDP charged successfully */
                    $updateType = '3';
                    $updateDesc = 'Modification';
                    $updateReason = 'Subscription charged renewal';
                    $free_value = 'false';
                    break;
                case 4:/* SDP fails to re-new */
                $updateType = '0';
                $updateDesc = 'Fails-renewal';
                $update_value = rand(1,6);
                switch($update_value){
                    case 1:
                        $update_desc = 'A user unsubscribes from a product.';
                        break;
                    case 2:
                        $update_desc = 'Rental fails to be collected upon subscription renewal.';
                        break;
                    case 3:
                        $update_desc = 'Unsubscription is triggered when the charging fails upon the first subscription.';
                        break;
                    case 4:
                        $update_desc = 'Unsubscription is triggered when the service status is abnormal.';
                        break;
                    case 5:
                        $update_desc = 'Unsubscription is triggered when the user status is abnormal.';
                        break;
                    case 6:
                        $update_desc = 'Automatical Unsubscription due to User being put in blacklist.';
                        break;
                }
                    break;

            }
            break;
    }
}

$updateReason = "<item>
                    <key>updateReason</key>
                    <value>$update_value</value>
                    <desc>$update_desc</desc>
                </item>";
$is_free_period = "<item>
                    <key>isFreePeriod</key>
                    <value>$free_value</value>
                </item>";


/* SDP (functioning as the client) send MO USSD message to the App */

$template = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>2420110005540</ns1:spId>
            <ns1:serviceId>242012000013589</ns1:serviceId>
            <ns1:timeStamp>20161110123710</ns1:timeStamp>
            <ns1:linkid>10133710077204189825</ns1:linkid>
            <ns1:traceUniqueID>404090105241611101237094336003</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifySmsReception xmlns:ns2="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
            <ns2:correlator>6647</ns2:correlator>
            <ns2:message><message>B100</message>
                <senderAddress>tel:242066894790</senderAddress>
                <smsServiceActivationNumber>tel:166</smsServiceActivationNumber>
                <dateTime>2016-11-10T12:37:10Z</dateTime>
            </ns2:message>
        </ns2:notifySmsReception>
    </soapenv:Body></soapenv:Envelope>';

/* sent a notifySmsReception Request
When receiving an MO SMS message from a user, the SDP (functioning as the client) invokes the notifySmsReception API to send
the MO SMS message to the App (functioning as the server). */
$notifySmsReceptionRequest = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>2420110005540</ns1:spId>
            <ns1:serviceId>242012000013589</ns1:serviceId>
            <ns1:timeStamp>20161110123710</ns1:timeStamp>
            <ns1:linkid>10133710077204189825</ns1:linkid>
            <ns1:traceUniqueID>404090105241611101237094336003</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifySmsReception xmlns:ns2="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
            <ns2:correlator>6647</ns2:correlator>
            <ns2:message><message>A</message>
                <senderAddress>tel:27796701111</senderAddress>
                <smsServiceActivationNumber>tel:166</smsServiceActivationNumber>
                <dateTime>2016-11-10T12:37:10Z</dateTime>
            </ns2:message>
        </ns2:notifySmsReception>
    </soapenv:Body></soapenv:Envelope>';

$sendSmsResponse= '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body>
        <ns1:sendSmsResponse xmlns:ns1="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local">
            <ns1:result>100211200201161126212853510091</ns1:result>
        </ns1:sendSmsResponse>
    </soapenv:Body>
</soapenv:Envelope>';

/* SDP send a notify SMS delivery Receipt to the APP to confirm whether the msg was delivered to the user */

$notifySmsDeliveryReceipt = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>2420110005540</ns1:spId>
            <ns1:timeStamp>20161110123710</ns1:timeStamp>
            <ns1:traceUniqueID>404090105241611101237094336003</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifySmsDeliveryReceipt xmlns:ns2="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
            <ns2:correlator>6647</ns2:correlator>
            <ns2:deliveryStatus>
                <address>242064661515</address>
                <deliveryStatus>DeliveredToTerminal</deliveryStatus>
            </ns2:deliveryStatus>
        </ns2:notifySmsDeliveryReceipt>
    </soapenv:Body>
</soapenv:Envelope>';


/******** SERVICE SUBSCRIPTION MANAGEMENT **********/

/* fields::
    updateType: 1: Add 2: Delete 3: Update 5: block 6: unblock (If block/unblock,SP should change the subscription status,
                but can’t change the period of validity of subscription and other information.)
    updateDesc: Addition, Deletion | Update  (When the extensionInfo field contains the UpdateReason field, the UpdateReason
                field specifies the subscription relationship update reason.)
                | UpdateReason = 2
                    ? 7698: The product is automatically unsubscribed from at the end of the grace period.
                    ? 7699: The product is automatically unsubscribed from at the end of the suspension period.
                    ? 7702?Unsubscription thriggered because user chose ‘not renew’ or didn’t reply for renewal subscription.


                Update reason.
                When updateType is set to 2 - Delete, the possible reasons are as follows:
                ? 1: A user unsubscribes from a product.
                ? 2: Rental fails to be collected upon subscription renewal.
                ? 3: Unsubscription is triggered when the charging fails upon the first subscription.
                ? 4: Unsubscription is triggered when the service status is abnormal.
                ? 5: Unsubscription is triggered when the user status is abnormal.
                ? 6: Automatical Unsubscription due to User being put in blacklist.

*/

$syncOrderRelationshipRequest = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body>
        <ns2:syncOrderRelation xmlns:ns1="http://www.csapi.org/schema/parlayx/data/sync/v1_0/local">
            <ns2:userID>
                <ID>'.$msisdn.'</ID>
                <type>0</type>
            </ns2:userID>
            <ns2:spID>270110000556</ns2:spID>
            <ns2:productID>'. $product_syn_id. '</ns2:productID>
            <ns2:serviceID>'. $service_syn_id.'</ns2:serviceID>
            <ns2:serviceList>'.$service_syn_id.'</ns2:serviceList>
            <ns2:updateType>'.$updateType.'</ns2:updateType>
            <ns2:updateTime>20161223125344</ns2:updateTime>
            <ns2:updateDesc>'.$updateDesc.'</ns2:updateDesc>
            <ns2:effectiveTime>20161223125321</ns2:effectiveTime>
            <ns2:expiryTime>20161225220000</ns2:expiryTime>
            <ns2:extensionInfo>
                <item>
                    <key>chargeMode</key>
                    <value>18</value>
                </item>
                <item>
                    <key>MDSPSUBEXPMODE</key>
                    <value>1</value>
                </item>
                <item>
                    <key>objectType</key>
                    <value>1</value>
                </item>
                <item>
                    <key>shortMessage</key>
                    <value>*136*8378*214##</value>
                </item>
                <item>
                    <key>operatorID</key>
                    <value>2701</value>
                </item>
                <item>
                    <key>payType</key>
                    <value>1</value>
                </item>
                <item>
                    <key>transactionID</key>
                    <value>504021505151612231539060875005</value>
                </item>
                <item>
                    <key>orderKey</key>
                    <value>999000000000005551</value>
                </item>
                <item>
                    <key>accessCode</key>
                    <value>*136*8378*214##</value>
                </item>
                <item>
                    <key>durationOfGracePeriod</key>
                    <value>0</value>
                </item>
                <item>
                    <key>serviceAvailability</key>
                    <value>0</value>
                </item>
                <item>
                    <key>durationOfSuspendPeriod</key>
                    <value>3</value>
                </item>
                <item>
                    <key>servicePayType</key>
                    <value>0</value>
                </item>
                <item>
                    <key>keyword</key>
                    <value>subbts</value>
                </item>
                '. $is_free_period.'
                <item>
                    <key>cycleEndTime</key>
                    <value>20161225220000</value>
                </item>
                <item>
                    <key>Starttime</key>
                    <value>20161222220000</value>
                </item>
                <item>
                    <key>channelID</key>
                    <value>3</value>
                </item>
                <item>
                    <key>TraceUniqueID</key>
                    <value>504021505151612231539060875008</value>
                </item>
                <item>
                    <key>messageID</key>
                    <value>99bc9aa72931457b857b6ca073f2d62f</value>
                </item>
                <item>
                    <key>rentSuccess</key>
                    <value>true</value>
                </item>
                <item>
                    <key>try</key>
                    <value>false</value>
                </item>
            </ns2:extensionInfo>
        </ns2:syncOrderRelation>
    </soapenv:Body>
</soapenv:Envelope>';

$syncOrderRelationshipRequest_OLD = '
<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body>
    <ns1:syncOrderRelation xmlns:ns1="http://www.csapi.org/schema/parlayx/data/sync/v1_0/local">
        <ns1:userID>
            <ID>'. $msisdn.'</ID>
            <type>0</type>
        </ns1:userID>
        <ns1:spID>001100</ns1:spID>
        <ns1:productID>1000000423</ns1:productID>
        <ns1:serviceID>'.$service_syn_id.'</ns1:serviceID>
        <ns1:updateType>'.$updateType.'</ns1:updateType>
        <ns1:updateTime>20130723082551</ns1:updateTime>
        <ns1:updateDesc>'.$updateDesc.'</ns1:updateDesc>
        <ns1:effectiveTime>20130723082551</ns1:effectiveTime>
        <ns1:expiryTime>20361231160000</ns1:expiryTime>
        <ns1:extensionInfo>
            <item>
                <key>accessCode</key>
                <value>20086</value>
            </item>
            <item>
                <key>operCode</key>
                <value> zh_CN </value>
            </item>
            <item>
                <key>chargeMode</key>
                <value>0</value>
            </item>
            <item>
                <key>MDSPSUBEXPMODE</key>
                <value>1</value>
            </item>
            <item>
                <key>objectType</key>
                <value>1</value>
            </item>
            <item>
                <key>Starttime</key>
                <value>20130723082551</value>
            </item>
            <item>
                <key>isFreePeriod</key>
                <value>false</value>
            </item>
            <item>
                <key>operatorID</key>
                <value>26001</value>
            </item>
            <item>
                <key>payType</key>
                <value>0</value>
            </item>
            <item>
                <key>transactionID</key>
                <value>504016000001307231624304170004</value>
            </item>
            <item>
                <key>orderKey</key>
                <value>999000000000000194</value>
            </item>
            <item>
                <key>keyword</key>
                <value>sub</value>
            </item>
            <item>
                <key>cycleEndTime</key>
                <value>20130822160000</value>
            </item>
            <item>
                <key>durationOfGracePeriod</key>
                <value>1</value>
            </item>
            <item>
                <key>serviceAvailability</key>
                <value>0</value>
            </item>
            <item>
                <key>durationOfSuspendPeriod</key>
                <value>0</value>
            </item>
            <item>
                <key>fee</key>
                <value>0</value>
            </item>
            <item>
                <key>servicePayType</key>
                <value>0</value>
            </item>
            <item>
                <key>cycleEndTime</key>
                <value>20130813021650</value>
            </item>
            <item>
                <key>channelID</key>
                <value>1</value>
            </item>
            <item>
                <key>TraceUniqueID</key>
                <value>504016000001307231624304170005</value>
            </item>
            <item>
                <key>rentSuccess</key>
                <value>true</value>
            </item>
            <item>
                <key>try</key>
                <value>false</value>
            </item>
            '.$updateReason.'
        </ns1:extensionInfo>
    </ns1:syncOrderRelation>
    </soapenv:Body>
</soapenv:Envelope>';

$syncOrderRelationshipRequest_XML = '<?xml version="1.0" encoding="utf-8" ?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><soapenv:Body><ns1:syncOrderRelation xmlns:ns1="http://www.csapi.org/schema/parlayx/data/sync/v1_0/local"><ns1:userID><ID>27788273607</ID><type>0</type></ns1:userID><ns1:spID>270110000556</ns1:spID><ns1:productID>2701220000001669</ns1:productID><ns1:serviceID>27012000002197</ns1:serviceID><ns1:serviceList>27012000002197</ns1:serviceList><ns1:updateType>1</ns1:updateType><ns1:updateTime>20161225123022</ns1:updateTime><ns1:updateDesc>Addition</ns1:updateDesc><ns1:effectiveTime>20161223125321</ns1:effectiveTime><ns1:expiryTime>20161225220000</ns1:expiryTime><ns1:extensionInfo><item><key>chargeMode</key><value>18</value></item><item><key>MDSPSUBEXPMODE</key><value>1</value></item><item><key>objectType</key><value>1</value></item><item><key>shortMessage</key><value>*136*8378*214##</value></item><item><key>operatorID</key><value>2701</value></item><item><key>payType</key><value>1</value></item><item><key>transactionID</key><value>504021505151612231539060875005</value></item><item><key>orderKey</key><value>999000000000005551</value></item><item><key>accessCode</key><value>*136*8378*214##</value></item><item><key>durationOfGracePeriod</key><value>0</value></item><item><key>serviceAvailability</key><value>0</value></item><item><key>durationOfSuspendPeriod</key><value>3</value></item><item><key>servicePayType</key><value>0</value></item><item><key>keyword</key><value>subbts</value></item><item><key>isFreePeriod</key><value>true</value></item><item><key>cycleEndTime</key><value>20161225220000</value></item><item><key>Starttime</key><value>20161222220000</value></item><item><key>channelID</key><value>3</value></item><item><key>TraceUniqueID</key><value>504021505151612231539060875008</value></item><item><key>messageID</key><value>99bc9aa72931457b857b6ca073f2d62f</value></item><item><key>rentSuccess</key><value>true</value></item><item><key>try</key><value>false</value></item></ns1:extensionInfo></ns1:syncOrderRelation></soapenv:Body></soapenv:Envelope>';


/* SDP (functioning as the client) invokes an API to send abnormal USSD session */
$notifyUssdAbort = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>2420110005540</ns1:spId>
            <ns1:serviceId>242012000013589</ns1:serviceId>
            <ns1:timeStamp>20161109080943</ns1:timeStamp>
            <ns1:linkid>09090943076202503225</ns1:linkid>
            <ns1:traceUniqueID>404092405241611090809432932004</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifyUssdAbort xmlns:ns2="http://www.csapi.org/schema/parlayx/ussd/notification/v1_0/local">
            <ns2:abortReason>The end user cancels.</ns2:abortReason>
            <ns2:senderCB>1904855568</ns2:senderCB>
            <ns2:receiveCB>FFFFFFFF</ns2:receiveCB>
        </ns2:notifyUssdAbort>
    </soapenv:Body>
</soapenv:Envelope>';

/* custom HTTP POST */
function curlHTTPRequest($url, $params){
    /* initialise curl resource */
    $curl = curl_init();

    /* result container, whether we are getting a feedback form url or an error */
    $result = null;

    /* encode to json format */
    //$data_string = json_encode($params, JSON_FORCE_OBJECT);

    /* set resources options for GET REQUEST */
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_CONNECTTIMEOUT => 10000, //attempt a connection within 10sec
        CURLOPT_FAILONERROR => 1,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => array('Content-Type: text/xml; charset=utf-8'),
        CURLOPT_POSTFIELDS => $params
    ));

    /* execute curl */
    $result = curl_exec($curl);

    if(curl_error($curl)){
        $result = 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
    }
    /* close request to clear up some memory */
    curl_close($curl);

    /* return the result */
    return $result;
}

/* determine incoming request */
$result =  null;
if (!is_null($msisdn) && !is_null($service_id) && !is_null($request_type)){
    $result = curlHTTPRequest('http://localhost'.DEFAULT_PORT.'/gateway/sms/', $syncOrderRelationshipRequest);
} else {
    $result = curlHTTPRequest('http://localhost'.DEFAULT_PORT.'/gateway/sms/', $notifySmsReceptionRequest);
}

echo $result;

//echo '<pre>'. print_r($result, true).'</pre>';