<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 9/19/2017
 * Time: 11:20 AM
 * simulate notitySMSnotification
 */

require_once('../config.php');

$request = '
<?xml version="1.0" encoding="utf-8" ?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>2420110003920</ns1:spId>
            <ns1:timeStamp>20170919071310</ns1:timeStamp>
            <ns1:traceUniqueID>504021504521709190813107902003</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifySmsReception xmlns:ns2="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
            <ns2:correlator>220201975</ns2:correlator>
            <ns2:message>
                <message></message>
                <senderAddress>tel:242066858947</senderAddress>
                <smsServiceActivationNumber>tel:2021</smsServiceActivationNumber>
                <dateTime>2017-09-19T07:13:10Z</dateTime>
            </ns2:message>
        </ns2:notifySmsReception>
    </soapenv:Body></soapenv:Envelope>';

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


echo curlHTTPRequest('http://localhost'.DEFAULT_PORT.'/gateway/sms/', $request);