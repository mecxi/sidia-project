<?php
require_once('../config.php');
error_reporting(E_ALL); ini_set('display_errors', 0);

/* start the session */
session_start();


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

/* SDP (functioning as the client) send MO USSD message to the App */

/* prepare required parameters */
$timeStamp = time();
$traceUniqueId = substr(md5(microtime()),rand(0,5),12);
$service_id = (isset($_POST['service_id'])) ? $_POST['service_id'] : null;
$current_service = new services($service_id);

$notifyUssdReceptionRequest = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>'.$current_service->sp_id.'</ns1:spId>
            <ns1:serviceId>'.$current_service->service_sdp_id.'</ns1:serviceId>
            <ns1:timeStamp>'. $timeStamp. '</ns1:timeStamp>
            <ns1:linkid>09090943076202503225</ns1:linkid>
            <ns1:traceUniqueID>'. $traceUniqueId.'</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifyUssdReception xmlns:ns2="http://www.csapi.org/schema/parlayx/ussd/notification/v1_0/local">
            <ns2:msgType>0</ns2:msgType>
            <ns2:senderCB>1904855568</ns2:senderCB>
            <ns2:receiveCB>FFFFFFFF</ns2:receiveCB>
            <ns2:ussdOpType>1</ns2:ussdOpType>
            <ns2:msIsdn>242066894790</ns2:msIsdn>
            <ns2:serviceCode>166</ns2:serviceCode>
            <ns2:codeScheme>15</ns2:codeScheme>
            <ns2:ussdString>ussdstring</ns2:ussdString>
        </ns2:notifyUssdReception>
    </soapenv:Body>
</soapenv:Envelope>';


$template = '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/ussd/send/v1_0/local">
    <soapenv:Header> <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
        <tns:spId>000201</tns:spId>
        <tns:spPassword>e6434ef249df55c7a21a0b45758a39bb</tns:spPassword>
        <tns:bundleID>256000039</tns:bundleID>
        <tns:timeStamp>20100731064245</tns:timeStamp>
        <tns:OA>8613300000010</tns:OA>
        <tns:FA>8613300000010</tns:FA>
        </tns:RequestSOAPHeader> </soapenv:Header> <soapenv:Body> <loc:sendUssd> <loc:msgType>0</loc:msgType> <loc:senderCB>306909975</loc:senderCB> <loc:receiveCB/> <loc:ussdOpType>1</loc:ussdOpType> <loc:msIsdn>8633699991234</loc:msIsdn> <loc:serviceCode>2929</loc:serviceCode> <loc:codeScheme>68</loc:codeScheme> <loc:ussdString>please select: Menuplease</loc:ussdString> </loc:sendUssd> </soapenv:Body> </soapenv:Envelope>
';


/* SDP (functioning as the client) invokes an API to send abnormal USSD session */
$notifyUssdAbort = '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>'.$current_service->sp_id.'</ns1:spId>
            <ns1:serviceId>'.$current_service->service_sdp_id.'</ns1:serviceId>
            <ns1:timeStamp>20161109080943</ns1:timeStamp>
            <ns1:linkid>09090943076202503225</ns1:linkid>
            <ns1:traceUniqueID>'. $traceUniqueId.'</ns1:traceUniqueID>
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




/********* USSD model demo ***********/

$message = (isset($_POST['answer']))? $_POST['answer']: null;
$session = (isset($_POST['session']))? $_POST['session']: null;
$msisdn = (isset($_POST['msisdn']))? $_POST['msisdn']: null;
$request_type = (isset($_POST['request_type'])) ? $_POST['request_type']:null;
if ($request_type == 'subscription'){
    /* USSD DEMO - Subscription Interaction */
    if (!is_null($message)){
        /* check a USSD string of *136# exist to start the session Menu */
        if (strpos($message, '*136#') !== false){
            $_SESSION['start_ussd'] = true;
            $_SESSION['start_count'] = 0;
        }

        if (isset($_SESSION['start_ussd']) && $_SESSION['start_ussd'] == true && $message !=''){
            /* get the xml cache data */
            $session = ($message == '0') ? 'end': (($_SESSION['start_count'] == 0) ? 'start':'continue');
           // echo $session . ' - message = '. $message. '- var_dump: '. var_dump($message);
            $dataXML = get_XML($session, $notifyUssdReceptionRequest);
            /* set value from forms elts */
            $dataXML['ussdString'] = $message;
            $dataXML['msisdn'] = $msisdn;

            $result = explode('|', do_curl(set_XML($dataXML['timeStamp'], $dataXML['traceUniqueID'], $dataXML['ussdString'], $dataXML['msgType'],
                $dataXML['ussdOpType'], $dataXML['msisdn'], $request_type, $current_service)));

            /* continue session */
            if (isset($_SESSION['start_count'])){
                $_SESSION['start_count'] = $_SESSION['start_count'] + 1;
            }

            /* check the session*/
            if ($result[1] == '2,3'){
                /* end the session */
                session_destroy();
            } else if ($result[1] == '0,1'){
                /* restart session */
                $_SESSION['start_count'] = 0;
            }

            echo $result[0];

            //Unset forms post
            unset($_POST);

            /* in order to exist the session, we use '0' in our demo */
            if ($message == '0'){
                session_destroy();
            }

        } else {
            echo 'Please dial a ussd code *136# to start the session MENU';
        }
    } else {
        log_XML($notifyUssdReceptionRequest);
    }
} else {
    /* USSD DEMO - Trivia Interaction */
    if (!is_null($message)){
        //echo '<pre>'. print_r(get_XML($session, $notifyUssdReceptionRequest), true).'</pre>';
        $dataXML = get_XML($session, $notifyUssdReceptionRequest);

        /* set form value submitted */
        $dataXML['ussdString'] = $message;
        $dataXML['msisdn'] = $msisdn;

        echo do_curl(set_XML($dataXML['timeStamp'], $dataXML['traceUniqueID'], $dataXML['ussdString'], $dataXML['msgType'],
            $dataXML['ussdOpType'], $dataXML['msisdn'], $request_type, $current_service));
        //Unset forms post
        unset($_POST);

    } else {
        log_XML($notifyUssdReceptionRequest);
        echo 'Please submit your an answer';
    }
}




/* log xml data into a file */
function log_XML($xml_data) {

    $dir_path= PROJECT_DIR.'/demo/';
    $filename = $dir_path. 'xml_cache.log';
    $sdpConvertedRequest = $xml_data;
    if (!is_array($xml_data)){
        $sdpXMLRequest = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xml_data);
        $xml = new SimpleXMLElement($sdpXMLRequest);
        $sdpConvertedRequest = json_encode((array)$xml, true);
    } else {
        $sdpConvertedRequest = json_encode($xml_data, true);
    }


    if (!file_exists($dir_path)){
        mkdir($dir_path, 0744);
    }
    /* insert xml data */
    return file_put_contents($filename,  $sdpConvertedRequest);
}



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

/* set xml data to forward */
function set_XML($timeStamp, $traceUniqueId, $ussdString, $msgType, $ussdOpType, $msisdn, $interface, $current_service)
{
    return '<?xml version="1.0" encoding="utf-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <soapenv:Header>
        <ns1:NotifySOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
            <ns1:spId>'.$current_service->sp_id.'</ns1:spId>
            <ns1:serviceId>'.$current_service->service_sdp_id.'</ns1:serviceId>
            <ns1:timeStamp>'. $timeStamp. '</ns1:timeStamp>
            <ns1:linkid>09090943076202503225</ns1:linkid>
            <ns1:traceUniqueID>'. $traceUniqueId.'</ns1:traceUniqueID>
            <ns1:OperatorID>24201</ns1:OperatorID>
        </ns1:NotifySOAPHeader>
    </soapenv:Header>
    <soapenv:Body>
        <ns2:notifyUssdReception xmlns:ns2="http://www.csapi.org/schema/parlayx/ussd/notification/v1_0/local">
            <ns2:msgType>'.$msgType.'</ns2:msgType>
            <ns2:senderCB>1904855568</ns2:senderCB>
            <ns2:receiveCB>FFFFFFFF</ns2:receiveCB>
            <ns2:ussdOpType>'.$ussdOpType.'</ns2:ussdOpType>
            <ns2:msIsdn>'. $msisdn. '</ns2:msIsdn>
            <ns2:serviceCode>166</ns2:serviceCode>
            <ns2:codeScheme>15</ns2:codeScheme>
            <ns2:ussdString>'.$ussdString.'</ns2:ussdString>
            <ns2:ussdInterface>'.$interface.'</ns2:ussdInterface>
        </ns2:notifyUssdReception>
    </soapenv:Body>
</soapenv:Envelope>';
}



/* fetch saved xml data */
function fetch_XML($notifyUssdReceptionRequest=null) {
    $dir_path= PROJECT_DIR.'/demo/';
    $filename = $dir_path. 'xml_cache.log';
    $buffer = array();

    if (file_exists($filename)){
        /* read current file */
        $tempfile = fopen($filename, 'r');

        while (!feof($tempfile)) {
            /* read data per line */
            $buffer[] = fgets($tempfile, 4096);
        }
        fclose($tempfile);
    } else {
        /* create the file */
        if (log_XML($notifyUssdReceptionRequest) !== false){
            $buffer = fetch_XML();
        }
    }

    if ($buffer){
        return (is_string($buffer)) ? $buffer: $buffer[0];
    } else {
        return null;
    }
}

function do_curl($xml_params) {
    return curlHTTPRequest('http://localhost'.DEFAULT_PORT.'/gateway/ussd/', $xml_params);
}

function get_XML($session, $notifyUssdReceptionRequest){
    /* get current cache data */
    $result = fetch_XML($notifyUssdReceptionRequest);
    /* convert into an array */
    $array_xml = json_decode($result, true);
    if (is_array($array_xml)){
        if ($session == 'start'){
            /* set new session */
            $array_xml['soapenvHeader']['ns1NotifySOAPHeader']['ns1timeStamp'] = time();
            $array_xml['soapenvHeader']['ns1NotifySOAPHeader']['ns1traceUniqueID'] = substr(md5(microtime()),rand(0,5),12);
            $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2msgType'] = '0'; // Begin
            $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2ussdOpType'] = '1'; // request
            /* cache current data */
            log_XML($array_xml);
        } else if ($session == 'continue'){
            $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2msgType'] = '1'; // Continue
            $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2ussdOpType'] = '3'; // Response
            /* cache current data */
            log_XML($array_xml);
        } else {
            $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2msgType'] = '2'; // End
            $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2ussdOpType'] = '3'; // User initiated the session
            /* cache current data */
            log_XML($array_xml);
        }
    }
    /* return only required parameters */
    return array(
        'timeStamp'=>$array_xml['soapenvHeader']['ns1NotifySOAPHeader']['ns1timeStamp'],
        'traceUniqueID'=> $array_xml['soapenvHeader']['ns1NotifySOAPHeader']['ns1traceUniqueID'],
        'msgType'=>$array_xml['soapenvBody']['ns2notifyUssdReception']['ns2msgType'],
        'ussdOpType'=> $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2ussdOpType'],
        'ussdString'=> $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2ussdString'],
        'msisdn'=> $array_xml['soapenvBody']['ns2notifyUssdReception']['ns2msIsdn']

    );
}

