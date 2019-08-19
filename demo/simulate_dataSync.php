<?php
require_once('../config.php');


//$msisdn = '27830010550';
$service_id = '3';
$request_type = 'unsubscribe';
$request_billing_cyle = '1'; //1- Day 2-Week 3-Month
$free_period = (new services($service_id))->free_period;

/* bullk request */
/* get all active subs */
$active_subs = user::fetch_active_subs($service_id);
foreach($active_subs as $user){
    $msisdn = $user['msisdn'];
    echo 'Request: '. $request_type. ' | '. $msisdn.'| result: '.  simultate_SDP_syncOrderRelation($msisdn, $service_id, $request_type, $request_billing_cyle, $free_period, true) . '<br>';
}

//$result = db::sql("SELECT msisdn FROM `temp_users` LIMIT 1741;", DB_NAME);
//if (mysqli_num_rows($result)){
//    while(list($msisdn) = mysqli_fetch_array($result)){
//        echo simultate_SDP_syncOrderRelation($msisdn, $service_id, $request_type, $request_billing_cyle, $free_period, true) . '<br>';
//    }
//}

//echo simultate_SDP_syncOrderRelation($msisdn, $service_id, $request_type, $request_billing_cyle, $free_period, true);

function simultate_SDP_syncOrderRelation($msisdn, $service_id, $request_type, $request_billing_cyle, $free_period, $force_result){
    /* required parameters */
    $params = array(
        'msisdn' => $msisdn,
        'service_id' => $service_id,
        'request' => $request_type,
        'billing_cycle'=>$request_billing_cyle,
        'free_period' => $free_period,
        'force_result' => $force_result

    );

    $jsonData = json_encode($params, true);

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $jsonData
        )
    );

    $context  = stream_context_create($opts);

    return file_get_contents('http://localhost'.DEFAULT_PORT.'/demo/SMS_demo', false, $context);
}

$subscribeProductRequest = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/subscribe/manage/v1_0/local">
    <soapenv:Header>
        <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
            <tns:spId>270110000483</tns:spId>
            <tns:spPassword>******</tns:spPassword>
            <tns:timeStamp>20170221165543</tns:timeStamp>
        </tns:RequestSOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <loc:subscribeProductRequest>
            <loc:subscribeProductReq>
                <userID>
                    <ID>27733120320</ID>
                    <type>0</type>
                </userID>
                <subInfo>
                    <productID>2701220000001359</productID>
                    <operCode>zh</operCode>
                    <isAutoExtend>0</isAutoExtend>
                    <channelID>2</channelID>
                </subInfo>
            </loc:subscribeProductReq>
        </loc:subscribeProductRequest>
    </soapenv:Body>
</soapenv:Envelope>';

$subscribeProductResponse = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body>
        <ns1:subscribeProductResponse xmlns:ns1="http://www.csapi.org/schema/parlayx/subscribe/manage/v1_0/local">
            <ns1:subscribeProductRsp>
                <result>0</result>
                <resultDescription>successful</resultDescription>
            </ns1:subscribeProductRsp>
        </ns1:subscribeProductResponse>
    </soapenv:Body>
</soapenv:Envelope>
';

$UnSubscribeProductRequest = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/subscribe/manage/v1_0/local">
    <soapenv:Header>
        <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
            <tns:spId>270110000483</tns:spId>
            <tns:spPassword>******</tns:spPassword>
            <tns:timeStamp>20170221165543</tns:timeStamp>
        </tns:RequestSOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <loc:unSubscribeProductRequest>
            <loc:unSubscribeProductReq>
                <userID>
                    <ID>27733120320</ID>
                    <type>0</type>
                </userID>
                <subInfo>
                    <productID>2701220000001359</productID>
                    <operCode>zh</operCode>
                    <isAutoExtend>0</isAutoExtend>
                    <channelID>2</channelID>
                </subInfo>
            </loc:unSubscribeProductReq>
        </loc:unSubscribeProductRequest>
    </soapenv:Body>
</soapenv:Envelope>
';


$UnSubscribeProductResponse = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Body> <ns1:unSubscribeProductResponse xmlns:ns1="http://www.csapi.org/schema/parlayx/subscribe/manage/v1_0/local">
        <ns1:unSubscribeProductRsp>
            <result>0</result>
            <resultDescription>successful</resultDescription>
        </ns1:unSubscribeProductRsp>
        </ns1:unSubscribeProductResponse>
    </soapenv:Body>
</soapenv:Envelope>
';

$unSubscribeDataSync = '
<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>270110000590</ns1:spId>
            <ns1:serviceId>27012000002393</ns1:serviceId>
            <ns1:timeStamp>20170822084828</ns1:timeStamp>
            <ns1:traceUniqueID>504021505151708221048240301008</ns1:traceUniqueID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:syncOrderRelation xmlns:ns2="http://www.csapi.org/schema/parlayx/data/sync/v1_0/local">
            <ns2:userID>
                <ID>27731014561</ID>
                <type>0</type>
            </ns2:userID>
            <ns2:spID>270110000590</ns2:spID>
            <ns2:productID>2701220000001894</ns2:productID>
            <ns2:serviceID>27012000002393</ns2:serviceID>
            <ns2:serviceList>27012000002393</ns2:serviceList>
            <ns2:updateType>2</ns2:updateType>
            <ns2:updateTime>20170822083934</ns2:updateTime>
            <ns2:updateDesc>Deletion</ns2:updateDesc>
            <ns2:effectiveTime>20170822083050</ns2:effectiveTime>
            <ns2:expiryTime>20170822083933</ns2:expiryTime>
            <ns2:extensionInfo>
                <item>
                    <key>Starttime</key>
                    <value>20170822083050</value>
                </item>
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
                    <key>keyword</key>
                    <value>STOP</value>
                </item>
                <item>
                    <key>cycleEndTime</key>
                    <value>20170822083933</value>
                </item>
                <item>
                    <key>updateReason</key>
                    <value>1</value>
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
                    <value>504021505151708221048240301004</value>
                </item>
                <item>
                    <key>orderKey</key>
                    <value>999000000000007131</value>
                </item>
                <item>
                    <key>channelID</key>
                    <value>3</value>
                </item>
                <item>
                    <key>syncFlag</key>
                    <value>0</value>
                </item>
                <item>
                    <key>TraceUniqueID</key>
                    <value>504021505151708221048240301006</value>
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
</soapenv:Envelope>
';