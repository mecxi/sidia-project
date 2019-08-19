<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 11/4/2017
 * Time: 11:27 PM
 */

require_once('../config.php');

error_reporting(E_ALL); ini_set('display_errors', 1);

/** requestPaymentRequest SOAP -> SDP
 @Header params:
 * bundleID: conditional -
When SDP creates a capability bundle, SDP allocates a bundleID to capability bundle.
The bundleID must not be contained during invocation of a service interface developed by service partners and other partners,
 * and must be contained during invocation of a capability interface developed by API partners, other partners, and developers.
[Example] 256000039
 *
 * @Body params:
 * serviceId: Holds the identification number to identify the service request.This is set as 200.
 * parameters key-value pairs:
 * DueAmount: The amount that the subscriber is due to pay on the App system.
 * ProcessingNumber: An Id to uniquely identify the transaction on the App system.
 * serviceId: This relates to the Code configured in the Mobile Money system. Properties file is used to uniquely identify each service provided.
 * AcctRef: Subscriber's account number on the App system. If the subscriber does not have account on the App system, you need confirm it with the Mobile Money system.
 * AcctBalance: Subscriber's balance on the App system. If there is no subscriber's balance on the App system, you need confirm it with the Mobile Money system.
 * MinDueAmount: The minimum amount that the subscriber is due to pay on the App system. If there is no minimum due on App system, you need confirm it with the Mobile Money system.
 * Narration: optional - Any text that the vendor would like to display to the subscriber
 * PrefLang: optional - The language locale to be used.
 * OpCoID: Identifier of the country local to be used. Please refer to 3.3 OpCoID List.
 * CurrCode: - Optional - The currency, as an ISO 4217 formatted string.
 *
 *3.3 OpCoID List MTN OpCo Name OpCoID Value
Afghanistan 9301
Benin 22901
Bissau 24501
Botswana 26701
Cameroon 23701
Congo 24201
CoteD'Ivoire 22501
Cyprus 35701
Ghana 23301
Guinea Republic 22401
Liberia 23101
Nigeria 23401
Rwanda 25001
South Africa 2701
Sudan North 21101
Sudan South 24901
Swaziland 26801
Syria 96301
Uganda 25601
Yemen 96701
Zambia 26001
 */

$requestPayment_request = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:b2b="http://b2b.mobilemoney.mtn.zm_v1.0">
    <soapenv:Header>
        <RequestSOAPHeader xmlns="http://www.huawei.com.cn/schema/common/v2_1">
            <spId>35000001</spId>
            <spPassword>de96d901b3bad1db2aab76b7b0b202f2</spPassword>
            <bundleID>256000039</bundleID>
            <serviceId>35000001000035</serviceId>
            <timeStamp>20100727</timeStamp>
        </RequestSOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <b2b:processRequest>
            <serviceId>200</serviceId>
            <parameter>
                <name>DueAmount</name>
                <value>10</value>
            </parameter>
            <parameter>
                <name>MSISDNNum</name>
                <value>13132132000</value>
            </parameter>
            <parameter>
                <name>ProcessingNumber</name>
                <value>555</value>
            </parameter>
            <parameter>
                <name>serviceId</name>
                <value>200</value>
            </parameter>
            <parameter>
                <name>AcctRef</name>
                <value>100</value>
            </parameter>
            <parameter>
                <name>AcctBalance</name>
                <value>555</value>
            </parameter>
            <parameter>
                <name>MinDueAmount</name>
                <value>121212</value>
            </parameter>
            <parameter>
                <name>Narration</name>
                <value>121212</value>
            </parameter>
            <parameter>
                <name>PrefLang</name>
                <value>121212121</value>
            </parameter>
            <parameter>
                <name>OpCoID</name>
                <value>24201</value>
            </parameter>
        </b2b:processRequest>
    </soapenv:Body>
</soapenv:Envelope>
';

/* requestPaymentResponse SOAP -> APP
 @parameters:
 * ThirdPartyAcctRef : Subscriber's account number on the App system. The SDP will fill this parameter as the same value which was received from AcctRef in the request.
 * SenderID : An Id to identify sender.This is set as MOM.
 * StatusCode : A predetermined code to determine the status of the transaction.
 * StatusDesc: The Mobile Money system supplied description of the response code.
 * MOMTransactionID : An Id to uniquely identify the transaction on the Mobile Money system. The SDP will forward this parameter to the App system.
*/
$requestPayment_response = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body>
        <ns1:processRequestResponse xmlns:ns1="http://b2b.mobilemoney.mtn.zm_v1.0">
            <return>
                <name>ProcessingNumber</name>
                <value>555</value>
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
                <value>222</value>
            </return>
            <return>
                <name>StatusDesc</name>
                <value>333</value>
            </return>
            <return>
                <name>MOMTransactionID</name>
                <value>111</value>
            </return>
        </ns1:processRequestResponse>
    </soapenv:Body>
</soapenv:Envelope>
';


/* requestPaymentCompleted_request SOAP -> APP
   @parameters:
. traceUniqueID : Transaction ID. The ID is automatically generated by the SDP and is used only to trace messages during the SDP commissioning. The App ignores this parameter.
[Example] 100001200101110623021721000011
. ProcessingNumber : “ProcessingNumber” which came in requestPayment request.
. MOMTransactionID : An Id to uniquely identify the transaction on the Mobile Money system.
. StatusCode : A predetermined code to determine the status of the transaction.
. StatusDesc : The Mobile Money system supplied description of the response code.
. ThirdPartyAcctRef : “AcctRef” which came in requestPayment request.
*/

$requestPaymentCompleted_request = '
<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns2:traceUniqueID xmlns:ns2="http://www.csapi.org/schema/momopayment/local/v1_0">504021503411410281818220013006</ns2:traceUniqueID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns3:requestPaymentCompleted xmlns:ns3="http://www.csapi.org/schema/momopayment/local/v1_0">
            <ns3:ProcessingNumber>2713500010003</ns3:ProcessingNumber>
            <ns3:MOMTransactionID>2713500010002</ns3:MOMTransactionID>
            <ns3:StatusCode>01</ns3:StatusCode>
            <ns3:StatusDesc>This is a respone Message!</ns3:StatusDesc>
            <ns3:ThirdPartyAcctRef>http://www.qwe.com</ns3:ThirdPartyAcctRef>
        </ns3:requestPaymentCompleted>
    </soapenv:Body>
</soapenv:Envelope>
';

/* requestPaymentCompleted_response SOAP -> SDP
. resultCode: Return code : 00000000 means success.
. resultDescription : the description of the resultCode 1.6.5
The App returns error codes to the SDP when an exception occurs in response to requestPaymentCompleted messages. The error codes are defined by partners.
Error Code      Description
00000000        Success
00000001        No data found
10000001        Authentication failed
10000002        Invalid Parameter:%1. %1 indicates parameter name
10000003        Request Rate Limited
10000099        Service Error
*/

$requestPaymentCompleted_response = '
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <requestPaymentCompletedResponse xmlns="http://www.csapi.org/schema/momopayment/local/v1_0">
            <result>
                <resultCode xmlns="">00000000</resultCode>
                <resultDescription xmlns="">success</resultDescription>
            </result>
            <extensionInfo>
                <item xmlns="">
                    <key>result</key>
                    <value>success</value>
                </item>
            </extensionInfo>
        </requestPaymentCompletedResponse>
    </soapenv:Body>
</soapenv:Envelope>
';

/* Request for DepositMobileMoney APP -> SDP
    Some parameters same as paymentRequest
    For this request the processRequest parameter:
    serviceId : 201
*/
$depositMobileMoneyRequest = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:b2b="http://b2b.mobilemoney.mtn.zm_v1.0/">
    <soapenv:Header>
        <RequestSOAPHeader xmlns="http://www.huawei.com.cn/schema/common/v2_1">
            <spId>35000001</spId>
            <spPassword>de96d901b3bad1db2aab76b7b0b202f2</spPassword>
            <bundleID>256000039</bundleID>
            <serviceId>35000001000035</serviceId>
            <timeStamp>20100727</timeStamp>
        </RequestSOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <b2b:processRequest>
            <serviceId>201</serviceId>
            <parameter>
                <name>ProcessingNumber</name>
                <value>555</value>
            </parameter>
            <parameter>
                <name>serviceId</name>
                <value>102</value>
            </parameter>
            <parameter>
                <name>SenderID</name>
                <value>MOM</value>
            </parameter>
            <parameter>
                <name>OpCoID</name>
                <value>24201</value>
            </parameter>
            <parameter>
                <name>MSISDNNum</name>
                <value>13132132000</value>
            </parameter>
            <parameter>
                <name>Amount</name>
                <value>10</value>
            </parameter>
            <parameter>
                <name>Narration</name>
                <value>121212</value>
            </parameter>
            <parameter>
                <name>CurrCode</name>
                <value>USD</value>
            </parameter>
        </b2b:processRequest>
    </soapenv:Body>
</soapenv:Envelope>
';

/* Response for DepositMobileMoney SDP -> APP
    Some parameters same as paymentRequestResponse
*/
$depositMobileMoneyResponse = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body>
        <ns1:processRequestResponse xmlns:ns1="http://b2b.mobilemoney.mtn.zm_v1.0/">
            <return>
                <name>ProcessingNumber</name>
                <value>123</value>
            </return>
            <return>
                <name>SenderID</name>
                <value>MOM</value>
            </return>
            <return>
                <name>StatusCode</name>
                <value>234</value>
            </return>
            <return>
                <name>StatusDesc</name>
                <value>345</value>
            </return>
            <return>
                <name>OpCoID</name>
                <value>0</value>
            </return>
            <return>
                <name>IMSINum</name>
                <value>86</value>
            </return>
            <return>
                <name>MSISDNNum</name>
                <value>13132132000</value>
            </return>
            <return>
                <name>OrderDateTime</name>
                <value>20100727</value>
            </return>
            <return>
                <name>ThirdPartyAcctRef</name>
                <value>121212</value>
            </return>
            <return>
                <name>MOMTransactionID</name>
                <value>456</value>
            </return>
        </ns1:processRequestResponse>
    </soapenv:Body>
</soapenv:Envelope>
';



?>
<html>
<body>
    <script src="lib/jquery/jquery-1.11.2.min.js"></script>
    <script>
        /* deposit */
        $.ajax({
            url: 'http://192.168.8.150:2400/gateway/payment/',
            type: 'POST',
            data: JSON.stringify({
                type: 'depositRequest',
                accesscode: 'RPwxaSnBdQ0oGGbKAZn8',
                appname: 'textoAPP',
                parameters: {
                    msisdn: '066269100',
                    productno: '45,34,67',
                    amount:'100',
                    alert: 'Your refund has been paid.'
                }
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                // do something with the result
                alert(JSON.stringify(result));
            },
            error: function(){
                // Can't reach the resource
            }
        });

        /* payment */
        $.ajax({
            url: 'http://192.168.8.150:2400/gateway/payment/',
            type: 'POST',
            data: JSON.stringify({
                type: 'paymentRequest',
                accesscode: 'RPwxaSnBdQ0oGGbKAZn8',
                appname: 'textoAPP',
                parameters: {
                    accno:'12345',
                    msisdn: '0793122930'
                    productno: '1234',
                    amount:'200',
                    alert: 'Thanks for your order'
                }
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                // do something with the result
                alert(JSON.stringify(result));
            },
            error: function(){
                // Can't reach the resource
            }
        });
    </script>
</body>
</html>
