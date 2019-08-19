<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 11/13/2017
 * Time: 9:31 PM
 * app object related operations
 */

class app
{
    public $service_local_id;
    public $id;
    public $name;
    public $desc;
    public $authcode;

    public function __construct($id)
    {
        $result = db::sql("SELECT `name`, `desc`, authcode, service_local_id FROM `tl_apps`
                  INNER JOIN tl_services_apps ON tl_services_apps.app_id = tl_apps.id
                  WHERE tl_apps.id = '$id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($name, $desc, $authcode, $service_id) = mysqli_fetch_array($result)){
                $this->name = $name;
                $this->desc = $desc;
                $this->authcode = $authcode;
                $this->service_local_id = $service_id;
            }
        }
    }

    /* verify the accesscode and return the service_local_id associated */
    public static function is_valid_code($accesscode)
    {
        $accesscode = strip_tags($accesscode);
        db::$prepare_param_values = array(&$accesscode);
        $result = db::sql("SELECT id FROM `tl_apps` WHERE authcode = ?;", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
                return $id;
            }
        } return null;
    }

    /* update accesscode */
    public static function update_accesscode($accesscode, $app_id)
    {
        return db::sql("UPDATE `tl_apps` SET authcode = '$accesscode' WHERE id = '$app_id';", DB_NAME);
    }

    /* return the authcode for the given service_local_id */
    public static function fetch_service_authcode($service_local_id, $app=null)
    {
        $result = db::sql("SELECT app_id, authcode FROM `tl_services_apps` INNER JOIN tl_apps ON tl_apps.id = tl_services_apps.app_id WHERE tl_services_apps.service_local_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($app_id, $accesscode) = mysqli_fetch_array($result)){
                return is_null($app) ? $accesscode : $app_id;
            }
        } return null;
    }


    /* process current app request */
    public static function process_current_request($app_id, $params)
    {
        global $loggerObj;
        /* instantiate current app */
        $current_app = new app($app_id);
        /* check request type */
        switch(strtolower($params['type'])){
            case 'paymentrequest':
            case 'depositrequest':
                /* validate the parameters values */
                $params_validate_result = self::validate_values_parameters($params);
                if (is_array($params_validate_result)){
                    return $params_validate_result;
                } else {
                    /* cache current request */
                    if (is_int(self::direct_request_logged($current_app, $params))){
                        /* process payment request */
                        $result = payment::forward_app_request($current_app, $params);
                        if (is_null($result)){
                            return array('error'=>array('message'=>'An internal error communicating with network provider. Please report to the merchant for a resolution'));
                        } else {
                            /* update current request outcome */
                            return self::update_request_logged($result);
                        }
                    } else {
                        /* failure to cache current request, there's no way to proceed */
                        $loggerObj->LogInfo('An internal error has occurred processing current request. Errors details : '. db::$errors);
                        return array('error'=>array('message'=>'An internal error has occurred processing your request. Please try again later. If the problem persist. Contact merchant'));
                    }
                }
                break;
            case 'paymentcheck':
                /* check a pending transaction for report */
                $params_validate_result = self::validate_values_parameters($params);
                if (is_array($params_validate_result)){
                    return $params_validate_result;
                } else {
                    return self::fetch_request_logged($params['parameters']['traceno'], $params['parameters']['transactionid']);
                }
                break;
            default:
                $loggerObj->LogInfo('Unknown request type received. Please review documentation for the correct request.');
                return array('error'=>restapi::custom_message_app(3));
                break;
        }
    }

    /* validate parameters values */
    private static function validate_values_parameters($params)
    {
        global $loggerObj;
        /* check app name character length and the presence of any latin character */
        if (strlen($params['appname']) > 50 || !mb_detect_encoding($params['appname'], 'UTF-8', true)){
            $loggerObj->LogInfo('The appname is invalid. Please make sure the character length is max 50 and does not contain any latin characters');
            return array('error'=>array('message'=>'The appname is invalid. Please make sure the character length is max 50 and does not contain any latin characters'));
        }

        /* check for the correct account number */
        if (isset($params['parameters']['accno']) && !is_numeric($params['parameters']['accno'])){
            $loggerObj->LogInfo('accno parameter is invalid. Please make sure the accno is numeric');
            return array('error'=>array('message'=>'accno parameter is invalid. Please make sure the accno is numeric'));
        }

        /* check the correct msisdn */
        if (isset($params['parameters']['msisdn']) && (!is_numeric($params['parameters']['msisdn']) || (substr($params['parameters']['msisdn'], 0, 1) != '0'))){
            $loggerObj->LogInfo('msisdn parameter is invalid. Please make sure the msisdn is numeric and start with 0');
            return array('error'=>array('message'=>'msisdn parameter is invalid. Please make sure the msisdn is numeric and start with 0'));
        }

        /* check for the correct product no */
        if (isset($params['parameters']['productno'])){
            /* check if is multiple product numbers */
            if (strpos($params['parameters']['productno'], ',') !== false){
                $productno = explode(',', $params['parameters']['productno']);
                foreach($productno as $key => $productid){
                    if (!is_numeric($productid)){
                        $loggerObj->LogInfo('productno parameter list '. ($key + 1).' is invalid. Please make sure the productno is numeric');
                        return array('error'=>array('message'=>'productno parameter list '. ($key + 1).' is invalid. Please make sure the productno is numeric'));
                    }
                }
            } else {
                if(!is_numeric($params['parameters']['productno'])){
                    $loggerObj->LogInfo('productno parameter is invalid. Please make sure the productno is numeric');
                    return array('error'=>array('message'=>'productno parameter is invalid. Please make sure the productno is numeric'));
                }
            }

        }

        /* check for the correct amount format */
        if (isset($params['parameters']['amount']) && !is_numeric($params['parameters']['amount'])){
            $loggerObj->LogInfo('amount parameter is invalid. Please make sure the amount is numeric');
            return array('error'=>array('message'=>'amount parameter is invalid. Please make sure the amount is numeric'));
        }

        /* check if an alert msg is included */
        if (isset($params['parameters']['alert']) && strlen($params['parameters']['alert']) > 100){
            $loggerObj->LogInfo('alert parameter is invalid. Please keep the text to 100 character long');
            return array('error'=>array('message'=>'alert parameter is invalid. Please keep the text to 100 character long'));
        }

        /* check for a valid traceid */
        if (isset($params['parameters']['traceno']) && !is_numeric($params['parameters']['traceno'])){
            $loggerObj->LogInfo('Invalid traceno format. Please make sure the traceno is numeric');
            return array('error'=>array('message'=>'Invalid traceno format. Please make sure the traceno is numeric'));
        }

        /* check for a valid transactionid */
        if (isset($params['parameters']['transactionid']) && !is_numeric($params['parameters']['transactionid'])){
            $loggerObj->LogInfo('Invalid transactionid format. Please make sure the transactionid is numeric');
            return array('error'=>array('message'=>'Invalid transactionid format. Please make sure the transactionid is numeric'));
        }

        return true;
    }

    /* cache direct incoming payment request */
    public static function direct_request_logged($current_app, &$params)
    {
        $trace_id = thread_ctrl::get_unique_trace_id();
        $msisdn = DIAL_CODE. $params['parameters']['msisdn'];
        /* determine request type */
        $req_type = strtolower($params['type']) == 'paymentrequest' ? 'payment': 'deposit';
        /* add current trace to app parameters */
        thread_ctrl::array_unshift_assoc($params, 'traceid', $trace_id);
        return db::sql("INSERT INTO `tl_sync_payment_requests` (msisdn, appname, trace_id, req_amount, req_type, req_service, req_product_no, req_state, by_date, req_sent, processing, date_created)
                VALUES('$msisdn', '". $params['appname']."', '$trace_id', '". $params['parameters']['amount']."', '$req_type', '$current_app->service_local_id', '". $params['parameters']['productno']. "',
                '1', CURRENT_TIMESTAMP, 1, 1, CURRENT_TIMESTAMP)", "tl_gateway");
    }

    /* update direct payment request outcome */
    public static function update_request_logged($sdpResult, $response=null)
    {
        /* parse sdp response parameters */
        $traceid = is_null($response) ?
                    payment::parse_sdp_parameters($sdpResult['soapenvBody']['ns1processRequestResponse']['return'], 'ProcessingNumber'):
                    $sdpResult['soapenvBody']['ns3requestPaymentCompleted']['ns3ProcessingNumber'];

        $statusCode = is_null($response) ?
                        payment::parse_sdp_parameters($sdpResult['soapenvBody']['ns1processRequestResponse']['return'], 'StatusCode'):
                        $sdpResult['soapenvBody']['ns3requestPaymentCompleted']['ns3StatusCode'];

        $statusDesc = is_null($response) ?
                        payment::parse_sdp_parameters($sdpResult['soapenvBody']['ns1processRequestResponse']['return'], 'StatusDesc'):
                        $sdpResult['soapenvBody']['ns3requestPaymentCompleted']['ns3StatusDesc'];

        $sdp_transID_resp = payment::parse_sdp_parameters($sdpResult['soapenvBody']['ns1processRequestResponse']['return'], 'MOMTransactionID');
        $transID = is_null($response) ?
            (is_array($sdp_transID_resp) ? '0' : $sdp_transID_resp):
            (is_array($sdpResult['soapenvBody']['ns3requestPaymentCompleted']['ns3MOMTransactionID']) ? '0': $sdpResult['soapenvBody']['ns3requestPaymentCompleted']['ns3MOMTransactionID']);

        /* determine response state */
        $req_state = (in_array($statusCode, array('01', '1000'))) ? 1:0;
        $processing = $statusCode == '1000' ? 0 : 2;
        $resp_type = $statusCode == '01' ? 'success' : ($statusCode == '1000' ? 'pending' : 'failed');

        /* cleanup status desc */
        $statusDesc_cl = (strpos($statusDesc, "'") !== false) ? str_replace("'","\'", $statusDesc) : $statusDesc;

        $update_result = db::sql("UPDATE `tl_sync_payment_requests` SET req_state = '$req_state', processing = '$processing', resp_time = CURRENT_TIMESTAMP,
        resp_type = '$resp_type', resp_code = '$statusCode', resp_desc = '$statusDesc_cl', transID = '$transID' WHERE trace_id = '$traceid';", "tl_gateway");


        return is_null($response) ? (
                $req_state ? array('success'=>array('code'=>$statusCode, 'message'=>$statusDesc, 'traceno'=>$traceid, 'transactionid'=>$transID)) : array('error'=>array('code'=>$statusCode,  'message'=>$statusDesc))
        ) : ($update_result ? true : (is_int($update_result) ? false : null));

    }

    /* return the requested transaction report */
    public static function fetch_request_logged($traceid, $transactionid)
    {
        global $loggerObj;
        $result = db::sql("SELECT resp_code, resp_desc FROM `tl_sync_payment_requests` WHERE trace_id = '$traceid' AND transID = '$transactionid'", "tl_gateway");
        if (mysqli_num_rows($result)){
            while(list($code, $desc) = mysqli_fetch_array($result)){
                $loggerObj->LogInfo('Report : '. $desc);
               return array('result'=>array('code'=>$code, 'message'=>$desc));
            }
        }

        /* check if the current transaction is awaiting sdp report update */
        $result = mysqli_num_rows(db::sql("SELECT * FROM `tl_sync_payment_requests` WHERE trace_id = '$traceid' AND transID IS NULL", "tl_gateway"));
        if ($result > 0){
            $loggerObj->LogInfo('Report : transaction with traceno '. $traceid. ' still awaiting transaction report');
            return array('result'=>array('code'=>'00', 'message'=> 'transaction with traceno '. $traceid. ' still awaiting transaction report'));
        } else {
            $loggerObj->LogInfo('Report: Transaction not found. Please make sure the corresponding traceno is for the given transactionid');
            return array('result'=>array('code'=>'000', 'message'=> 'Transaction not found. Please make sure the corresponding traceno is for the given transactionid'));
        }
    }


}