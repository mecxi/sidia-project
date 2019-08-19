<?php
/**
 * web recruit class handler
 * User: Mecxi
 * Date: 4/2/2017
 * Time: 1:09 AM
 */

class web_recruit
{
    /* web request */
    private static $host = array('192.168.8.250', 'trivia.sidia.co.za');
    private static $msisdn;
    private  static $serviceID;
    private static $re_try = 0;

    /* web response */
    private static $authToken;
    private static $tokenExpiryTime;
    private static $result_resp;


    /*
    request SP web authentication to process current request
    log current user for service subscription request to SDP
    */
    public static function request_web_auth($host, $msisdn, $serviceID)
    {
        /* check if current host is allowed */
        if (in_array($host, self::$host)){
            /* initialise object fields */
            self::$msisdn = '27'. substr($msisdn, 1) ;
            self::$serviceID = $serviceID;
            /* fetch SP credentials and trace_unique_id logged */
            if (self::start_web_request_log() === true){
                $transactionId = self::fetch_trace_id();
                if (!is_null($transactionId)){
                    /* generate authentication for web recruitment process */
                    date_default_timezone_set("UTC");
                    $spID = '270110001205';
                    $nonce = mt_rand();
                    $timestamp = date("Y-m-d\TH:i:s\Z");
                    $has_res = hash('sha256', $nonce. $timestamp. 'bmeB500');
                    $password = thread_ctrl::run(null, null, 'echo "'. $has_res.'" | xxd -r -p | base64');
                    //$password = base64_encode(hash('sha256', $nonce . $timestamp . 'bmeB500'));
                    return array('spID'=>$spID, 'nonce'=>$nonce, 'timestamp'=>$timestamp, 'password'=>$password[0], 'transactionId'=>$transactionId, 'msisdn'=>self::$msisdn, 'serviceID'=>self::$serviceID);
                } else {
                    return array('result'=> false, 'message'=>'An internal error occurred. Enable to fetch TransactionId to proceed the subscription! Please report to system admim');
                }
            } else {
                return array('result'=> false, 'message'=>'An internal error occurred. Enable to log current request to proceed subscription request! Please try again later');
            }
        } else {
            return array('result'=> false, 'message'=>'Error processing your request. HOST Refereed: '. $host.' is not allowed to perform this request');
        }
    }

    /* log incoming request, 5 attempts allowed to minimize database memory to exhaust */
    private static function start_web_request_log()
    {
        self::$re_try = self::$re_try + 1;
        $result = null;
        if (db::sql("INSERT INTO `web_recruit_request` (trace_id, service_id, msisdn, date_created)
          VALUES('". thread_ctrl::get_unique_trace_id() ."', '".self::$serviceID ."', '". self::$msisdn."', CURRENT_TIMESTAMP);", "mtn_promo_gateway") === false){
            if (self::$re_try < 6) {
                self::start_web_request_log();
            }
       } else {
            $result = true;
       }
        return $result;
    }

    /* log the update request */
    private static function update_web_request_log($trace_id, $success=null, $update=null)
    {
        return (is_null($success)) ?
            db::sql("UPDATE `web_recruit_request` SET req_result = '". self::$result_resp."' WHERE trace_id = '$trace_id';", "mtn_promo_gateway") :
            (
                (is_null($update)) ?
                db::sql("UPDATE `web_recruit_request` SET req_result = '". self::$result_resp."', auth_token = '".self::$authToken."', token_expiry = '".self::$tokenExpiryTime."' WHERE trace_id = '$trace_id';", "mtn_promo_gateway") :
                    db::sql("UPDATE `web_recruit_request` SET req_result = '$update' WHERE trace_id = '$trace_id';", "mtn_promo_gateway")
            );
    }

    /* fetch trace_id of current request */
    private static function fetch_trace_id()
    {
        $result = db::sql("SELECT trace_id FROM `web_recruit_request` WHERE service_id = '".self::$serviceID."' AND msisdn = '".self::$msisdn."' ORDER BY date_created DESC LIMIT 1;", "mtn_promo_gateway");
        if (mysqli_num_rows($result)){
            while(list($trace_id) = mysqli_fetch_array($result)){
                return $trace_id;
            }
        }
        return null;
    }

    /*
     * Incoming SDP online subscription response
    */

    public static function response_web_auth($dataGET)
    {
        global $loggerObj;
        /* check incoming transactionID */
        $fetch_resp_trace = (isset($_GET['transactionId'])) ?  self::get_msisdn_trace_id($dataGET['transactionId']) : null;
        if (!is_null($fetch_resp_trace)){

            switch($dataGET['result']){
                case '0':
                    /* Accepting response */
                    self::$authToken = $dataGET['authToken'];
                    self::$tokenExpiryTime = $dataGET['tokenExpiryTime'];
                    self::$result_resp = 0;
                    if (self::update_web_request_log($dataGET['transactionId'], true)){
                        /* initialise the gateway needed fields */
                        gateway::$msisdn = $fetch_resp_trace[0];
                        gateway::$serviceID = $fetch_resp_trace[1];
                        $report = gateway::subscribeProductRequest(self::$authToken);
                        if (isset($report['code'])){
                            if ($report['code'] == '0'){
                                /* user has been subscribed */
                                self::$result_resp = 1;
                                self::update_web_request_log($dataGET['transactionId'], true, self::$result_resp);
                            } else {
                                self::$result_resp = 0;
                                self::update_web_request_log($dataGET['transactionId'], true, self::$result_resp);
                            }

                            $loggerObj->LogInfo("Web SDP Response : ". $report['code'].  " | ". $report['desc']);
                        } else {
                            $loggerObj->LogInfo("SDP Internal Server Error ");
                        }
                    } else {
                        $loggerObj->LogInfo("An internal error occurred - Enable to process SDP web response transactionId : ". $dataGET['transactionId']);
                    }
                    break;
                default:
                    self::$result_resp = 0;
                    self::update_web_request_log($dataGET['transactionId']);
                    $loggerObj->LogInfo("Enable to process SDP Web Response - transactionId : ". $dataGET['transactionId']. ' - ERROR CODE : '. $dataGET['result']. ' - ERROR DESC : '. $dataGET['description']);
                    break;
            }
        } else {
            $loggerObj->LogInfo("An internal error occurred - Enable to locate MSISDN for web response transactionId : ". $dataGET['transactionId']);
        }
        return self::$result_resp;
    }

    private static function get_msisdn_trace_id($trace_id)
    {
        $result = db::sql("SELECT msisdn, service_id FROM `web_recruit_request` WHERE trace_id = '$trace_id';", "mtn_promo_gateway");
        if (mysqli_num_rows($result)){
            while(list($msisdn, $serviceID) = mysqli_fetch_array($result)){
                return array($msisdn, $serviceID);
            }
        }
        return null;
    }
}