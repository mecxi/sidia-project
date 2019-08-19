<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 11/4/2017
 * Time: 10:53 PM
 *
 * This class handles incoming | outgoing app payment requests operations
 */


class payment
{
    public static $simulation = true;
    public static $interface = null; //this is for demo testing interface | Web Portal Direct Request for live Output
    public static $production = false;

    /* SDP incoming request fields */
    public static $accountno;
    public static $statusCode;
    public static $statusDesc;
    public static $transactionID;
    public static $processingNumber;

    /* check incoming format to the gateway */
    public static function determine_content_type($data)
    {
        $request_type = substr($data, 0, 1);
        /* check for a JSON format */
        if ($request_type == '{'){
            return 'JSON';
        } else if ($request_type == '<'){
          return 'XML';
        } else {
            return false;
        }
    }

    /* parse incoming request; determine what type of the request */
    public static function process_sdp_request($data)
    {
        global $loggerObj;

        $process_portal = null;

        /* parse sdp xml data posted */
        $sdpXMLRequest = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $data));
        $xml = new SimpleXMLElement($sdpXMLRequest);
        $sdpConvertedRequest = json_decode(json_encode((array)$xml), TRUE);


        /* SDP Incoming request type */
        $processRequestResponse = null;
        $requestPaymentCompleted = null;

        if (isset($sdpConvertedRequest['soapenvBody'])) {
            foreach ($sdpConvertedRequest['soapenvBody'] as $key => $value) {
                if ($key == 'ns3requestPaymentCompleted'){
                    /* incoming requestPaymentCompleted | paymentRequest from aSynchronous request */
                    $loggerObj->LogInfo('SDP - requestPaymentCompleted Received');
                    $requestPaymentCompleted = true;
                } else {
                    $loggerObj->LogInfo('Invalid or Unknown request type received!');
                }
            }
        }

        /* requestPaymentCompleted_request received */
        if ($requestPaymentCompleted){
            /* Retrieve required parameters */
            $update_result = app::update_request_logged($sdpConvertedRequest, true);
            if ($update_result === true){
                /* success */
                $loggerObj->LogInfo('UPDATING TRANSACTION STATUS: Success | traceID: '. $sdpConvertedRequest['soapenvBody']['ns3requestPaymentCompleted']['ns3ProcessingNumber']);
                return self::paymentRequestCompletedResponse(0);
            } else if ($update_result === false){
                /* traceid no match or data not found */
                $loggerObj->LogInfo('UPDATING TRANSACTION STATUS: failed - no traceID found');
                return self::paymentRequestCompletedResponse(1);
            } else {
                /* An internal server error | database failure */
                $loggerObj->LogInfo('UPDATING TRANSACTION STATUS: failed -  An internal server error or database failure');
                return self::paymentRequestCompletedResponse(2);
            }
        } else {
            /* Unknown request type */
            return self::paymentRequestCompletedResponse(3);
        }
    }

    /* parse and return the required sdp parameters */
    public static function parse_sdp_parameters($sdp_params, $type)
    {
        foreach($sdp_params as $item){
            if ($item['name'] == $type){
                return $item['value'];
            }
        } return null;
    }

    /* process app requests */
    public static function process_app_request($data)
    {
        global $loggerObj;
        /* retrieve required parameters */
        $params = json_decode($data, true);
        /* log current request */
        $loggerObj->LogInfo(print_r($params, true));

        /* verify incoming request */
        if (!isset($params['type'])){
            $loggerObj->LogInfo('You are not authorised to access this resource');
            return json_encode(array('error'=>restapi::custom_message_app(0)), true);
        } else {
            if (!isset($params['accesscode']) || !isset($params['parameters'])){
                $loggerObj->LogInfo('Invalid parameters submitted. Please review documentation for the correct parameters');
                return json_encode(array('error'=>restapi::custom_message_app(1)), true);
            } else {
                /* validate the current request */
                $app_id = app::is_valid_code($params['accesscode']);
                if ($app_id){
                    return json_encode(app::process_current_request($app_id, $params), true);
                } else {
                    $loggerObj->LogInfo('Invalid access-code. Your request could not be authenticated.');
                    return json_encode(array('error'=>restapi::custom_message_app(2)), true);
                }
            }
        }
    }


    /* forward the request */
    public static function forward_app_request($current_app, $params)
    {
        global $loggerObj;
        /* initialise the service parameters */
        $current_service = new services($current_app->service_local_id);
        $serviceID = $current_service->service_sdp_id;
        $spID = $current_service->sp_id;
        $credentials = services::service_credentials($current_app->service_local_id);
        /* check request type for related parameters */
        $sdp_service_code = '';
        $amount_param = '';
        $acc_reference_param = '';
        $min_due_param = '';
        $request_type = 1; // paymentRequest by default
        if (strtolower($params['type']) == 'paymentrequest'){
//            $sdp_service_code = '200';
            $amount_param = '<parameter><name>DueAmount</name><value>'.$params['parameters']['amount'] .'</value></parameter>';
//            $acc_reference_param = '<parameter><name>AcctRef</name><value>'. $params['parameters']['accno'].'</value></parameter>';
//            $min_due_param = '<parameter><name>MinDueAmount</name><value>'.$params['parameters']['amount'].'</value></parameter>';
        } else {
//            $sdp_service_code = '201';
            $amount_param = '<parameter><name>Amount</name><value>'.$params['parameters']['amount'] .'</value></parameter>';
            $request_type = 0; // deposit request
        }

        /* check if alert text is required */
        $alert_user = isset($params['parameters']['alert']) ? '<parameter><name>Narration</name><value>'. $params['parameters']['alert'].'</value></parameter>' : '';


        $xmlPostData ='
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
   <soap:Header>
      <ns2:RequestSOAPHeader xmlns:ns3="http://b2b.mobilemoney.mtn.zm_v1.0" xmlns:ns2="http://www.huawei.com.cn/schema/common/v2_1">
         <spId>'. $spID.'</spId>
         <spPassword>B60FB7DE59BC9CDFCC41FA2F2D5BF550</spPassword>
         <serviceId/>
         <timeStamp>20170801160327</timeStamp>
      </ns2:RequestSOAPHeader>
   </soap:Header>
   <soap:Body>
      <ns3:processRequest xmlns:ns2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:ns3="http://b2b.mobilemoney.mtn.zm_v1.0">
         <serviceId>pharma.sp</serviceId>'.$amount_param.'
         <parameter>
            <name>MSISDNNum</name>
            <value>'. DIAL_CODE.$params['parameters']['msisdn'].'</value>
         </parameter>
         <parameter>
            <name>ProcessingNumber</name>
            <value>'.$params['traceid'].'</value>
         </parameter>
         <parameter>
            <name>CurrCode</name>
            <value>XAF</value>
         </parameter>
         <parameter>
            <name>SenderID</name>
            <value>MOM</value>
         </parameter>
      </ns3:processRequest>
   </soap:Body>
</soap:Envelope>
        ';


        $loggerObj->LogInfo(strtolower($params['type']) == 'paymentrequest' ?
            "======================== PROCESSING PAYMENT REQUEST =======================":"======================== PROCESSING DEPOSIT REQUEST =======================" );
        if (self::$simulation) {
            $loggerObj->LogInfo($xmlPostData);
            $result = gateway::curlHTTPRequestXML('http://localhost'.DEFAULT_PORT.'/demo/simulate_smsResponse', $xmlPostData);
            $loggerObj->LogInfo("======================== MOBILE MONEY API RESPONSE SIMULATION =======================");
            /* check the api response status to update the status of current request if needed  */
            if (is_string($result) && strpos($result, 'Error') !== false){
                $loggerObj->LogInfo('An internal error communicating with network provider | details :'. print_r($result, true));
                return null;
            } else {
                $loggerObj->LogInfo('SmsResponse XML: ' .print_r($result, true));
                $sdpXMLResult = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result));
                $xml = new SimpleXMLElement($sdpXMLResult);
                $sdpResult = json_decode(json_encode((array)$xml), TRUE);
                //$loggerObj->LogInfo(print_r($sdpResult, true));
                $loggerObj->LogInfo('SmsResponse Result:'.print_r($sdpResult, true));
                /* return the result report for gateway updates */
                return $sdpResult;

            }
        } else {
            $loggerObj->LogInfo($xmlPostData);
            $result = gateway::curlHTTPRequestXML((self::$production == true) ?
                ($request_type ? 'http://'. SDP_PROD_IP.'/ThirdPartyServiceUMMImpl/UMMServiceService/RequestPayment/v17' : 'http://'. SDP_PROD_IP.'/ThirdPartyServiceUMMImpl/UMMServiceService/DepositMobileMoney/v17'):
                ($request_type ? 'http://'. SDP_TEST_IP.'/ThirdPartyServiceUMMImpl/UMMServiceService/RequestPayment/v17' : 'http://'. SDP_PROD_IP.'/ThirdPartyServiceUMMImpl/UMMServiceService/DepositMobileMoney/v17' ), $xmlPostData);
            $loggerObj->LogInfo(strtolower($params['type']) == 'paymentrequest' ?
                "======================== PAYMENT REQUEST RESULT =======================":"======================== DEPOSIT REQUEST RESULT =======================");
            /* check the api response status to update the status of the request if needed */
            if (is_string($result) && strpos($result, 'Error') !== false){
                $loggerObj->LogInfo('An internal error communicating with network provider | details :'. print_r($result, true));
                return null;
            } else {
                $loggerObj->LogInfo('SmsResponse XML: '. print_r($result, true));
                $sdpXMLResult = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result));
                $xml = new SimpleXMLElement($sdpXMLResult);
                $sdpResult = json_decode(json_encode((array)$xml), TRUE);
                //$loggerObj->LogInfo(print_r($sdpResult, true));
                $loggerObj->LogInfo('SmsResponse Result: Code: '. self::parse_sdp_parameters($sdpResult['soapenvBody']['ns1processRequestResponse']['return'], 'StatusCode'). ' | Desc: '.payment::parse_sdp_parameters($sdpResult['soapenvBody']['ns1processRequestResponse']['return'], 'StatusDesc'));
                /* return the result report for gateway updates */
                return $sdpResult;
            }
        }
    }

    /* notify paymentRequestCompleted Response */
    public static function paymentRequestCompletedResponse($code)
    {
        $resultCode = '';
        $resultDesc = '';
        switch($code){
            case 0:
                    /* Success */
                $resultCode = '00000000';
                $resultDesc = 'Success';
                break;
            case 1:
                /* No data found */
                $resultCode = '00000001';
                $resultDesc = 'No data found';
                break;
            case 2:
                /* Service Error */
                $resultCode = '10000099';
                $resultDesc = 'An internal server error. Please try again';
                break;
            case 3:
                /* Invalid Parameter */
                $resultCode = '10000002';
                $resultDesc = 'Invalid Parameter or Unknown request';
                break;
        }

        return '<?xml version="1.0" encoding="utf-8"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
                    <soapenv:Body>
                        <requestPaymentCompletedResponse xmlns="http://www.csapi.org/schema/momopayment/local/v1_0">
                            <result>
                                <resultCode xmlns="">'. $resultCode.'</resultCode>
                                <resultDescription xmlns="">'.$resultDesc.'</resultDescription>
                            </result>
                        </requestPaymentCompletedResponse>
                    </soapenv:Body>
                </soapenv:Envelope>';
    }

}
