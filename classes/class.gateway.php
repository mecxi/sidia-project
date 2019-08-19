<?php
/**
 * @author : Mecxi Musa
 * This class handles SMS/USSD incoming | outgoing services requests operations
 */

class gateway
{
    public static $simulation = true;
    public static $interface = null; //this is for demo testing interface | Web Portal Direct Request for live Output
    public static $production = false;

    /* SDP incoming request fields */
    private static $linkid;
    public static $traceUniqueID;
    public static $message;
    public static $msisdn;
    private static $receivecb;
    public static $sendercb;
    private static $msgType;
    private static $ussdOpType;

    /* SyncOrderRelation */
    public static $serviceID;
    public static $productID;
    private static $updateType;
    private static $ns1updateDesc;
    private static $transactionID;
    private static $freePeriod;
    private static $accessCode;

    /* SP credentials */
    public static $hashedPassword;
    public static $timestamp;

    /* gateway type */
    public static $gwTypeSMS;
    public static $gwTypeUSSD;

    /* notifySmsDeliveryReceipt */
    private static $deliveryStatus;
    private static $correlatorID;


    /* custom HTTP POST XML */
    public static function curlHTTPRequestXML($url, $params){
        /* initialise curl resource */
        $curl = curl_init();

        /* result container, whether we are getting a feedback form url or an error */
        $result = null;

        /* set resources options for GET REQUEST */
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => 10000, //attempt a connection within 10sec
            CURLOPT_FAILONERROR => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array('Content-Type: text/xml; charset=utf-8'),
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_POSTFIELDS => $params
        ));


        /* execute curl */
        $result = curl_exec($curl);

        if(curl_error($curl)){
            $result = 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
                //. ' - HTTP HEADER INFO - '. print_r(curl_getinfo ( $curl), true);
        }
        /* close request to clear up some memory */
        curl_close($curl);

        /* return the result */
        return $result;
    }

    /* mode - servicing related type */
    public static function mode_servicing_type($postData, $service_type)
    {
        global $loggerObj;

        /* parse params here */
        $params = json_decode($postData, true);
        $msisdn = $params[0];
        $tracer_id = $params[1];
        $message = $params[3];
        $gwparams = $params[4];

        $loggerObj->LogInfo('mode-servicing-type: '.$service_type . print_r($params, true));
        $loggerObj->LogInfo('mode-servicing-type: '.$service_type);

        $result = null;

        /* live traffic | send SMS */
        if (self::$gwTypeSMS){
            $sent_report = self::sendSMS($msisdn, $message, $tracer_id, $gwparams['service_local_id']);
            if (strpos($sent_report, 'successfully') !== false){
                $responseData = array(
                    'result' => 'success',
                    'message'=> $sent_report
                );
                $jsonResponseData = json_encode($responseData, true);
                header('Content-Type: application/json');
                if (ob_get_contents()) ob_end_clean();

            } else {
                $responseData = array(
                    'result' => 'error',
                    'message'=> $sent_report
                );

                $jsonResponseData = json_encode($responseData, true);
                header('Content-Type: application/json');
                if (ob_get_contents()) ob_end_clean();
            }

            $result = $jsonResponseData;

        } else { /* USSD :: DISABLED FOR LIVE TRAFFIC */

            if (self::$simulation){
                /* live Traffic | send USSD */
                /* Process will wait for the response of the user */
                $responseData = array(
                    'result' => 'success',
                    'message'=> "sent successfully!"
                );
                $jsonResponseData = json_encode($responseData, true);
                header('Content-Type: application/json');
                if (ob_get_contents()) ob_end_clean();
                $result = $jsonResponseData;

                self::sendUSSD($gwparams['senderCB'], $msisdn, $message, '1,3', $gwparams['service_local_id'], $gwparams['linkid']);
            }
        }

        return $result;
    }

    /* mode - send token authentication */
    public static function servicing_token_auth($postData)
    {
        global $loggerObj;
        /* initialise service credentials */
        self::$timestamp = date('YmdHis');
        $password = (self::$production == true) ? '270110001205'.'bmeB500'.self::$timestamp : '270110000556'.'bmeB500'.self::$timestamp;
        self::$hashedPassword = md5($password);

        /* parse parameters */
        $params = json_decode($postData, true);
        $msisdn = $params[0];
        $trace_id = $params[1];
        $message = $params[2];
        $service_id = 3; // send token through service ID 3 by default

        $loggerObj->LogInfo("mode-servicing_level-token : ". print_r($params, true));

        /* live traffic | send SMS */
        if (self::$gwTypeSMS){
            /* Servicing Unique Portal-code authenticate, check which service user is currently active */
            if (!user::has_current_subscription($msisdn, $service_id)){
                /* check an existing subscription on others services */
                $services_list = array(2, 1);
                foreach($services_list as $serviceID){
                    if (user::has_current_subscription($msisdn, $serviceID)){
                        $service_id = $serviceID;
                        break;
                    }
                }
            }

            return self::sendSMS('12345', self::$timestamp, self::$hashedPassword, $msisdn, $message, $trace_id, $service_id);
        } else {
            return false;
        }
    }


    /* parse incoming request; determine what type of the request */
    public static function process_sdp_request($dataPOST)
    {
        global $loggerObj;

        $process_portal = null;

       /* parse sdp xml data posted */
        $sdpXMLRequest = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $dataPOST));
        $xml = new SimpleXMLElement($sdpXMLRequest);
        $sdpConvertedRequest = json_decode(json_encode((array)$xml), TRUE);

        /****************** determine INCOMING SDP REQUEST TYPE *************/
        $loggerObj->LogInfo("============= Determining SDP REQUEST TYPE ===============");

        if (self::$gwTypeSMS){
            /* SDP Incoming request type */
            $notifySmsReception = null;
            $syncOrderRelationship = null;
            $notifySmsDeliveryReceipt = null;

            $nothingProvided = null;

            if (isset($sdpConvertedRequest['soapenvBody'])){
                foreach($sdpConvertedRequest['soapenvBody'] as $key => $value){

                    if ($key == 'ns2notifySmsReception'){
                        /* A incoming ns2notifySmsReception request | MO SMS */
                        $loggerObj->LogInfo('SDP - notifySmsReception Request Received');
                        $notifySmsReception = true;
                    } else if ($key == 'ns2syncOrderRelation'){
                        /* A incoming syncOrderRelationship request | subscription /un-subscribe request */
                        $loggerObj->LogInfo('SDP - syncOrderRelationship Request Received');
                        $syncOrderRelationship = true;
                    } else if ($key == 'ns2notifySmsDeliveryReceipt'){
                        /* A incoming notifySmsDeliveryReceipt Response | SMS delivery report */
                        $loggerObj->LogInfo('SDP - notifySmsDeliveryReceipt Response Received');
                        $notifySmsDeliveryReceipt = true;
                    } else {
                        /* Nothing is provided */
                        $loggerObj->LogInfo('WARNING!!! SDP Send Empty Parameters');
                        $nothingProvided = true;
                    }
                }
            }

            if ($notifySmsReception) {
                /* Retrieve required parameters */
                self::$traceUniqueID = $sdpConvertedRequest['soapenvHeader']['ns1NotifySOAPHeader']['ns1traceUniqueID'];
                self::$msisdn =  substr($sdpConvertedRequest['soapenvBody']['ns2notifySmsReception']['ns2message']['senderAddress'], 4);
                self::$message = $sdpConvertedRequest['soapenvBody']['ns2notifySmsReception']['ns2message']['message'];
                self::$interface = (isset($sdpConvertedRequest['soapenvBody']['ns2notifySmsReception']['ns2interface'])) ?
                    $sdpConvertedRequest['soapenvBody']['ns2notifySmsReception']['ns2interface'] : null;
                self::$serviceID = isset($sdpConvertedRequest['soapenvHeader']['ns1NotifySOAPHeader']['ns1serviceId']) ?
                    $sdpConvertedRequest['soapenvHeader']['ns1NotifySOAPHeader']['ns1serviceId'] : '';
                self::$accessCode = substr($sdpConvertedRequest['soapenvBody']['ns2notifySmsReception']['ns2message']['smsServiceActivationNumber'], 4);

                /* clean posted data for illegal characters */
                self::clean_data_posted();
                $loggerObj->LogInfo("SERVICE ID: ". self::$serviceID." | MESSAGE : ". self::$message." | SRC MSISDN: ". self::$msisdn. " | WEB-PORTAL:". (is_null(self::$interface) ? 'NO' : 'YES'));

                if (!is_null(self::$msisdn)){
                    // check if it's a web portal request
                    if (is_null(self::$interface)){
                        /* check if the requested service is currently running */
                        $service_id = services::get_local_service_id(self::$serviceID);

                        /* check if the serviceID can be retrieved via an accessCode for exclusive service */
                        if (is_null($service_id)){
                            $service_id = services::get_local_service_id_by_accessCode(self::$accessCode);
                        }

                        if ($service_id){
                            /* check if requested service is trivia type, demand type */
                            if (in_array((new services($service_id))->service_type, array(2, 3))){
                                // process user request
                                $resp_tmp = self::process_user_response($service_id);
                                $process_portal[] = $resp_tmp[0];
                                $process_portal[] = $resp_tmp[1];
                            } else {
                                $process_portal[] = 'The service is unavailable or Invalid. Contact SP';
                                $process_portal[] = self::notifySmsReceptionResponse(5);
                            }
                        } else {

                            $process_portal[] = 'The service does not exist';
                            $process_portal[] = self::notifySmsReceptionResponse(4);
                        }
                    } else {
                        // web portal service product request
                        if (self::$message == 'subscribe'){
                            $process_portal = self::process_user_subscription(services::get_local_service_id(self::$serviceID));
                        } else {
                            $process_portal = self::process_user_unsubscription(services::get_local_service_id(self::$serviceID));
                        }
                    }

//                    /* check STOP associated service keyword */
//                    $stop_service_associated = null;
//                    if (strpos(strtoupper(self::$message), 'STOP') !== false){
//                        $stop_service_associated = trim(strtoupper(substr(self::$message, 4)));
//                        self::$message = 'STOP';
//                    }
//
//                    $keyword = services::is_service_keywords(strtoupper(self::$message));
//                    if (is_null($keyword)){
//
//                    } else if (is_bool($keyword)){
//                        /* subscribe request */
//                        if ($keyword == true){
//                            $process_portal = self::process_user_subscription();
//                        } else {
//                            /* un-subscribe request */
//                            /* check if user would like to stop all services */
//                            if ($stop_service_associated == 'ALL'){
//                                $keyword_list = services::all_services_keywords();
//                                foreach($keyword_list as $stop_services_keyword){
//                                    $process_portal .= self::process_user_unsubscription($stop_services_keyword);
//                                }
//                            } else {
//                                $process_portal = self::process_user_unsubscription($stop_service_associated);
//                            }
//                        }
//                    }

                } else {
                    $loggerObj->LogInfo("REQUIRED PARAMETERS MISSING. Please Review incoming Request Parameters ");
                }

            } else if ($syncOrderRelationship) {
                /* Retrieve required parameters */
                self::$msisdn = $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2userID']['ID'];
                self::$serviceID = $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2serviceID'];
                self::$productID = $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2productID'];
                self::$updateType = $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2updateType'];
                self::$ns1updateDesc = $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2updateDesc'];
                self::$transactionID = (self::$updateType == '2') ? $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2extensionInfo']['item'][10]['value']:
                                        $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2extensionInfo']['item'][5]['value'];
                self::$traceUniqueID = (self::$updateType == '2') ?
                                        $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2extensionInfo']['item'][13]['value'] :
                                        $sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2extensionInfo']['item'][19]['value'];
                /* to trace billing report, check upon free period */
                $trace_free_period = self::is_free_period($sdpConvertedRequest['soapenvBody']['ns2syncOrderRelation']['ns2extensionInfo']['item']);
                self::$freePeriod = (!is_null($trace_free_period)) ? (($trace_free_period == true) ? 1: 0) : 0;

                /* clean posted data for illegal characters */
                self::clean_data_posted();

                $loggerObj->LogInfo("TYPE: ". self::$ns1updateDesc." | SRC MSISDN: ". self::$msisdn. " | SDP_SERVICE_ID: ". self::$serviceID. ' | SDP_PRODUCT_ID: '. self::$productID);
                $request_type = (self::$updateType == '1') ? 'subscribe':((self::$updateType == '2') ? 'unsubscribe': ((self::$updateType == '3') ? 'renewal': null));

                if (!is_null(self::$msisdn) && !is_null(self::$serviceID)){
                    /* check if the requested service is currently running */
                    $service_id = services::get_local_service_id(self::$serviceID);

                    if ($service_id){
                        /* get local product id and check if it's associated to the current service */
                        $product_id = services::get_local_product_id($service_id, self::$productID);

                        if ($product_id){
                            $service_keywords = (new services($service_id))->keywords;
                            $service_list = thread_ctrl::all_service_status($service_id);
                            $service_state = thread_ctrl::service_has_started($service_list[0][1], $service_list[0][4]);
                            if ($service_state === true){
                                /* process user incoming service subscription /un-subscribe request */
                                $resp = self::report_sdp_dataSync(explode(',', self::process_user_SyncSubscription($service_id, $service_keywords, $product_id)), $service_id, $service_keywords);
                                $process_portal[] = $resp[1];
                                $process_portal[] = $resp[0];

                                $loggerObj->LogInfo(" ============= CONFIRMATION RESPONSE ===============");
                                $loggerObj->LogInfo(print_r($process_portal[1], true));

                            } elseif ($service_state === false){
                                /* service is closed */
                                $process_portal[1] = self::syncOrderRelationshipResponse(6);
                                /* log current request */
                                services::direct_request_logged(self::$msisdn, $service_keywords[0], $request_type,
                                    $service_id, 2, self::$updateType, self::$ns1updateDesc, self::$transactionID, self::$freePeriod, self::$serviceID, self::$productID);
                                /* update the request */
                                services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], $request_type, 0, 12);

                                $loggerObj->LogInfo(" ============= CONFIRMATION RESPONSE ===============");
                                $loggerObj->LogInfo(print_r($process_portal[1], true));
                            } else {

                                /* current service is not running or not enable to broadcast */
                                $process_portal[1] = ($service_list[0][2] != 0) ?
                                    self::syncOrderRelationshipResponse(7) : self::syncOrderRelationshipResponse(5);
                                /* log current request */
                                services::direct_request_logged(self::$msisdn, $service_keywords[0], $request_type,
                                    $service_id, 2, self::$updateType, self::$ns1updateDesc, self::$transactionID, self::$freePeriod, self::$serviceID, self::$productID);
                                /* update the request if the service hasn't started */
                                if ($service_state){
                                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], $request_type, 0, 13);
                                } else {
                                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], $request_type, 0, 16);
                                }

                                $loggerObj->LogInfo(" ============= CONFIRMATION RESPONSE ===============");
                                $loggerObj->LogInfo(print_r($process_portal[1], true));
                            }
                        } else {
                            /* wrong product service associated */
                            $process_portal[1] = self::syncOrderRelationshipResponse(1);
                            /* log current request */
                            services::direct_request_logged(self::$msisdn, 'NOTFOUND', $request_type,
                                0, 2, self::$updateType, self::$ns1updateDesc, self::$transactionID, self::$freePeriod, self::$serviceID, self::$productID);
                            /* update the request */
                            services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, 'NOTFOUND', $request_type, 0, 17);

                            $loggerObj->LogInfo(" ============= CONFIRMATION RESPONSE ===============");
                            $loggerObj->LogInfo(print_r($process_portal[1], true));
                        }
                    } else {
                        /* send an error to sdp */
                        $process_portal[1] = self::syncOrderRelationshipResponse(1);
                        /* log current request */
                        services::direct_request_logged(self::$msisdn, 'NOTFOUND', $request_type,
                            0, 2, self::$updateType, self::$ns1updateDesc, self::$transactionID, self::$freePeriod, self::$serviceID, self::$productID);
                        /* update the request */
                        services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, 'NOTFOUND', $request_type, 0, 14);

                        $loggerObj->LogInfo(" ============= CONFIRMATION RESPONSE ===============");
                        $loggerObj->LogInfo(print_r($process_portal[1], true));
                    }

                } else {
                    /* send an error to sdp */
                    $process_portal[1] = self::syncOrderRelationshipResponse(1);
                    /* log current request */
                    services::direct_request_logged(self::$msisdn, 'NOTFOUND', $request_type,
                        0, 2, self::$updateType, self::$ns1updateDesc, self::$transactionID, self::$freePeriod, self::$serviceID, self::$productID);
                    /* update the request */
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, 'NOTFOUND', $request_type, 0, 14);
                    $loggerObj->LogInfo("REQUIRED PARAMETERS MISSING. Please Review incoming Request Parameters ");
                }

            } else if ($notifySmsDeliveryReceipt) {
                /* Retrieve required parameters */
                self::$deliveryStatus = $sdpConvertedRequest['soapenvBody']['ns2notifySmsDeliveryReceipt']['ns2deliveryStatus']['deliveryStatus'];
                self::$msisdn = $sdpConvertedRequest['soapenvBody']['ns2notifySmsDeliveryReceipt']['ns2deliveryStatus']['address'];
                /* trace the report of sms that was sent */
                self::$correlatorID = $sdpConvertedRequest['soapenvBody']['ns2notifySmsDeliveryReceipt']['ns2correlator'];
                $loggerObj->LogInfo("SMS Report: MSISDN:".self::$msisdn."| traceID:".self::$correlatorID."| Result:".self::$deliveryStatus);

                /* DeliveredToTerminal */
                if (self::$deliveryStatus == 'DeliveredToTerminal'){
                    /* check current trace_id status */
                    $service_id = services::find_trace_service_id(self::$correlatorID);
                    if ($service_id){
                        $current_service = new services($service_id);
                        if (!services::current_broadcast_delivered_state(self::$correlatorID, $service_id)){
                           /* Update current broadcast state to delivered */
                            if (services::update_broadcast_delivered_state(self::$correlatorID, $service_id, 1)){} else {
                                $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: failed - Internal System Error ");
                            }
                        }

                        if ($current_service->service_type == 2){
                            /* update datasync queue delivery state */
                            if (services::update_datasync_trivia($service_id, null, null, 1, self::$correlatorID)){
                                $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: Success");
                            } else {
                                $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: failed - Internal System Error in DataSync Queue ");
                            }
                        }
                        /* notify SDP */
                        $process_portal[1] = self::notifySmsDeliveryReceiptResponse(0);

                    } else {
                        /* check if current trace/Sdp correlatorID belongs to a notify */
                        $service_id = services::has_notify_traced(self::$correlatorID);
                        if ($service_id){
                            if (services::prepare_datasync_notify(self::$correlatorID, null, true, 1, null, $service_id)){
                                $loggerObj->LogInfo("UPDATING BROADCAST STATUS: Success - datasync_notify - traceID:". self::$correlatorID);
                            }
                        } else {
                            $loggerObj->LogInfo("UPDATING BROADCAST STATUS: failed - no traceID found - Wrong SDP correlatorID");
                        }
                        /* notify SDP */
                        $process_portal[1] = self::notifySmsDeliveryReceiptResponse(0);
                    }
                } else if (self::$deliveryStatus == 'DeliveryImpossible'){
                    /* check current trace_id status */
                    $service_id = services::find_trace_service_id(self::$correlatorID);
                    if ($service_id){
                        $current_service = new services($service_id);
                        if (services::current_broadcast_delivered_state(self::$correlatorID, $service_id)){
                            /* Update current broadcast state to Undelivered */
                            if (services::update_broadcast_delivered_state(self::$correlatorID, $service_id, 0)){
                                /* set a retry for the current failed case */
                                if ($current_service->service_type == 1) {
                                    thread_ctrl::set_retry_operation(1,$service_id);
                                    $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: Success");
                                }
                            } else {
                                $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: failed - Internal System Error ");
                            }
                        }

                        if ($current_service->service_type == 2){
                            /* update datasync queue delivery state */
                            if (services::update_datasync_trivia($service_id, null, null, 0, self::$correlatorID)){
                                $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: Success");

                            } else {
                                $loggerObj->LogInfo("UPDATING BROADCASTING STATUS: failed - Internal System Error in DataSync Queue ");
                            }
                        }

                        /* notify SDP */
                        $process_portal[1] = self::notifySmsDeliveryReceiptResponse(0);
                    } else {
                        /* check if current trace/Sdp correlatorID belongs to a notify */
                        $service_id = services::has_notify_traced(self::$correlatorID);
                        if ($service_id){
                            if (services::prepare_datasync_notify(self::$correlatorID, null, true, 0, null, $service_id)){
                                $loggerObj->LogInfo("UPDATING BROADCAST STATUS: Success - datasync_notify - traceID:". self::$correlatorID);
                            }
                        } else {
                            $loggerObj->LogInfo("UPDATING BROADCAST STATUS: failed - no traceID found - Wrong SDP correlatorID");
                        }
                        /* notify SDP */
                        $process_portal[1] = self::notifySmsDeliveryReceiptResponse(0);
                    }
                } else {
                    /* DeliveryNotificationNotSupported: The SMSC does not provide the function of sending status reports. The SDP constructs status reports.*/
                    $loggerObj->LogInfo("UPDATING BROADCAST STATUS: UNCHANGED - The SMSC does not provide the function of sending status reports. The SDP constructs status reports");
                    $process_portal[1] = self::notifySmsDeliveryReceiptResponse(1);
                }

            }else {
                /* nothing is provided */
                $process_portal[1] = self::syncOrderRelationshipResponse(1);
                $loggerObj->LogInfo("SDP no provision | 0 parameters");
            }
        } else if (self::$gwTypeUSSD){
            /* SDP Incoming USSD request */
            $notifyUssdReception = null;
            $notifyUssdAbort = null;
            $nothingProvided = null;

            if (isset($sdpConvertedRequest['soapenvBody'])){
                foreach($sdpConvertedRequest['soapenvBody'] as $key => $value){

                    if ($key == 'ns2notifyUssdReception'){
                        /* SDP send a notifyUssdReception request */
                        $loggerObj->LogInfo('SDP notifyUssdReception Request Received');
                        $notifyUssdReception = true;
                    } else if ($key == 'ns2notifyUssdAbort'){
                        /* SDP requesting a abort */
                        $loggerObj->LogInfo('SDP notifyUssdAbort Request Received');
                        $notifyUssdAbort = true;
                    } else {
                        /* SDP sent nothing */
                        $loggerObj->LogInfo('WARNING!!! SDP Send Empty Parameters');
                        $nothingProvided = true;
                    }
                }
            }

            if ($notifyUssdReception) {
                /* Retrieve required parameters */
                self::$linkid = $sdpConvertedRequest['soapenvHeader']['ns1NotifySOAPHeader']['ns1linkid'];
                self::$traceUniqueID = $sdpConvertedRequest['soapenvHeader']['ns1NotifySOAPHeader']['ns1traceUniqueID'];
                self::$sendercb = $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2senderCB'];
                self::$receivecb = $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2receiveCB'];
                self::$msisdn = $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2msIsdn'];
                self::$message = $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2ussdString'];
                self::$serviceID = $sdpConvertedRequest['soapenvHeader']['ns1NotifySOAPHeader']['ns1serviceId'];
                self::$interface =  /* for the demo interface interaction */
                    (isset($sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2ussdInterface']))?
                    $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2ussdInterface']: null;
                self::$msgType = $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2msgType'];
                self::$ussdOpType = $sdpConvertedRequest['soapenvBody']['ns2notifyUssdReception']['ns2ussdOpType'];
                /* clean posted data for illegal characters */
                self::clean_data_posted();

                $loggerObj->LogInfo("SERVICE ID: ". self::$serviceID." | TraceUniqueID: ". self::$traceUniqueID.
                    " | SRC MSISDN: ". self::$msisdn." | MESSAGE: ". self::$message);

                if (self::$interface == 'subscription') { /* demo interactive subscription */
                    /* session | begin/continue/response */
                    if (self::$msgType == '2' && self::$ussdOpType == '3' && !is_null(self::$message)){
                        /* close extra menu when session ends */
                        services::close_ussd_extra_menu(self::$msisdn);
                        $process_portal = "MMI code cancelled. Please dial a ussd code *136# to restart your session|2,3";
                        self::sendUSSD(self::$sendercb, self::$msisdn, $process_portal, '2,3', services::get_local_service_id(self::$serviceID), self::$linkid);
                    } else {

                        /* initialise menu and session */
                        $session = '';
                        $USSD_menu = '';

                        /* session begin */
                        if (self::$msgType == '0' && self::$ussdOpType == '1'){
                            $session = '1,3';
                            $USSD_menu = "
                                    Please choose options below:<br>
                                    1. Glam squad trivia<br>
                                    2. Beauty Tips<br>
                                    3. Slay or Nay<br>
                                    4. Info and T&C<br>
                                    5. Unsubscribe<br>
                                |". $session;

                        } else if (self::$msgType == '1' && self::$ussdOpType == '3'){
                            /* session continue | check if extra menu session is opened */
                            if (services::has_extra_menu(self::$msisdn)){
                                /* Extra Menu Selection */
                                switch(self::$message) {
                                    case '1':
                                        /* STOP to service level 1 */
                                        $session = '0,1';
                                        $USSD_menu = self::process_user_unsubscription(services::get_keyword_service_id(1)). "<br>
                                                      1. Back to Main Menu
                                        |" . $session;
                                        services::close_ussd_extra_menu(self::$msisdn);
                                        break;
                                    case '2':
                                        /* STOP to service level 2 */
                                        $session = '0,1';
                                        $USSD_menu = self::process_user_unsubscription(services::get_keyword_service_id(2)). "<br>
                                                        1. Back to Main Menu
                                        |". $session;
                                        services::close_ussd_extra_menu(self::$msisdn);
                                        break;
                                    case '3':
                                        /* STOP to service level 3 */
                                        $session = '0,1';
                                        $USSD_menu = self::process_user_unsubscription(services::get_keyword_service_id(3))."<br>
                                                        1. Back to Main Menu
                                        |".  $session;
                                        services::close_ussd_extra_menu(self::$msisdn);
                                        break;
                                    case '4':
                                        /* STOP ALL */
                                        $service_ids = array(1, 2, 3);
                                        $resp = '';
                                        foreach($service_ids as $service_id){
                                            $resp .= self::process_user_unsubscription(services::get_keyword_service_id($service_id));
                                        }
                                        $session = '0,1';
                                        $USSD_menu = $resp. "<br>
                                                        1. Back to Main Menu
                                        |". $session;
                                        services::close_ussd_extra_menu(self::$msisdn);
                                        break;
                                    case '5':
                                        /* Go back */
                                        services::close_ussd_extra_menu(self::$msisdn);
                                        $session = '1,3';
                                        $USSD_menu = "
                                            Please choose options below:<br>
                                            1. Glam squad trivia<br>
                                            2. Beauty Tips<br>
                                            3. Slay or Nay<br>
                                            4. Info and T&C<br>
                                            5. Unsubscribe<br>
                                        |".  $session;
                                        break;
                                }
                            } else {
                                /* Main Menu Selection */
                                switch(self::$message){
                                    case '1':
                                        /* subscribe to service level 1 */
                                        $session = '0,1';
                                        $USSD_menu = self::process_user_subscription(services::get_keyword_service_id(1))."<br>
                                                        1. Back
                                        |".$session;
                                        break;
                                    case '2':
                                        /* subscribe to service level 2 */
                                        $session = '0,1';
                                        $USSD_menu = self::process_user_subscription(services::get_keyword_service_id(2))."<br>
                                                        1. Back
                                        |".$session;
                                        break;
                                    case '3':
                                        /* subscribe to service level 3 */
                                        $session = '0,1';
                                        $USSD_menu = self::process_user_subscription(services::get_keyword_service_id(3)). "<br>
                                                        1. Back
                                        |".$session;
                                        break;
                                    case '4':
                                        /* terms and conditions requested */
                                        $session = '0,1';
                                        $USSD_menu = "
                                        Presenting terms and conditions that you need to abide etc...
                                        <br>
                                                        1. Back
                                        |".$session;
                                        break;
                                    case '5':
                                        /* un-susbcribe requests | present Extra Menu
                                         in order to the request of this menu, we log user activity */
                                        services::start_ussd_extra_menu(self::$msisdn);
                                        $session = '1,3';
                                        $USSD_menu = "
                                            Please choose options below:<br>
                                            1. Stop Glam squad trivia<br>
                                            2. Stop Beauty Tips<br>
                                            3. Stop Slay or Nay<br>
                                            4. Stop ALL<br>
                                            5. Back<br>
                                        |". $session;
                                        break;
                                }
                            }
                        }

                        /* Send USSD Response if request Originated via USSD */
                        $process_portal = $USSD_menu;
                        $USSD_menu_Send = explode('|', $USSD_menu);

                        if (self::$gwTypeUSSD || self::$simulation){
                            self::sendUSSD(self::$sendercb, self::$msisdn, $USSD_menu_Send[0], $session, services::get_local_service_id(self::$serviceID), self::$linkid);
                        }
                    }

                } else if (self::$interface == 'trivia'){ /* demo interactive trivia */
                    if (self::$msgType == '2' && self::$ussdOpType == '3' && !is_null(self::$message)){
                        $process_portal = "MMI code cancelled. You have ended your session|2,3";
                        self::sendUSSD(self::$sendercb, self::$msisdn, '2,3', $process_portal, self::$linkid);
                    } else {
                        /* check if the requested service is currently running */
                        $service_id = services::get_local_service_id(self::$serviceID);
                        if ($service_id){
                            /* check if requested service is trivia type, demand type */
                            if (in_array((new services($service_id))->service_type, array(2, 3))){
                                $resp_tmp = self::process_user_response($service_id);
                                $process_portal = $resp_tmp[0];
                            }
                        }
                    }
                } else {/* Live SDP incoming ussd traffic here */


                }
            } else if ($notifyUssdAbort){
                /* Simply Log The abort Request */
                $loggerObj->LogInfo("Abort Reason : " . $sdpConvertedRequest['soapenvBody']['ns2notifyUssdAbort']['ns2abortReason']);
                /* close extra menu when session ends */
                services::close_ussd_extra_menu(self::$msisdn);
                $process_portal = "MMI code cancelled. Please dial a ussd code *136# to start the a new session|2,3";
                /* check if the requested service is currently running */
                if (self::$gwTypeUSSD){
                    self::sendUSSD(self::$sendercb, self::$msisdn, $process_portal, '2,3', services::get_local_service_id(self::$serviceID), self::$linkid);
                }
            } else {
                /* nothing is provided */
                $loggerObj->LogInfo("SDP no provision | 0 parameters");
                $process_portal = "Application ERROR. Please redial a ussd code *136# to start the a new session|2,3";

                if (self::$gwTypeUSSD){
                    self::sendUSSD(self::$sendercb, self::$msisdn, $process_portal, '2,3', services::get_local_service_id(self::$serviceID), self::$linkid);
                }
            }
        }

        return $process_portal;
    }

    /* check if datasync is free period */
    private static function is_free_period($items)
    {
        if (is_array($items)){
           for ($i=0; $i < count($items); ++$i){
               if ($items[$i]['key'] == 'isFreePeriod'){
                   return ($items[$i]['value'] == 'true') ? true : false;
               }
           }
        }

        return null;
    }

    /* clean incoming request */
    private static function clean_data_posted()
    {
        self::$linkid = (is_array(self::$linkid) && empty(self::$linkid)) ? null: self::$linkid;
        self::$traceUniqueID = (is_array(self::$traceUniqueID) && empty(self::$traceUniqueID))? null : self::$traceUniqueID;
        self::$sendercb =  (is_array(self::$sendercb) && empty(self::$sendercb)) ? null : self::$sendercb;
        self::$receivecb = (is_array(self::$receivecb) && empty(self::$receivecb)) ? null : self::$receivecb;
        self::$msisdn = (is_array(self::$msisdn) && empty(self::$msisdn)) ? null : self::$msisdn;
        self::$message = (is_array(self::$message) && empty(self::$message)) ? null : strip_tags(self::$message);
        self::$msgType = (is_array(self::$msgType) && empty(self::$msgType)) ? null : self::$msgType;
        self::$ussdOpType = (is_array(self::$ussdOpType) && empty(self::$ussdOpType)) ? null : self::$ussdOpType;
    }


    /* process user response to service trivia */
    private static function process_user_response($service_local_id)
    {
        $process_result = null;
        /* check if a user is active member or subscriber */
        /* check if the requested service trivia is running or enabled to broadcast */
        $service = thread_ctrl::all_service_status($service_local_id);
        $start_date = $service[0][1];
        $end_date = $service[0][4];
        $service_name = $service[0][5];
        $service_state = thread_ctrl::service_has_started($start_date, $end_date);
        if ( $service_state === true){
            $user = new user(user::get_user_id(self::$msisdn));
            if (user::has_current_subscription($user->services_stats, $service_local_id)){
                /* process user incoming request for an interactive service */
                $report = explode(',', self::start_service_on_demand($user->id, $service_local_id));
                if ($report[0] == 'success'){
                    $process_result[] = $report[1];
                    $process_result[] = self::notifySmsReceptionResponse(0);
                }
            } else {
                /* user is not active */
                $process_result[] = "MSISDN: ". self::$msisdn." is not active subscriber ! Please re-subscribe the user first";
                $process_result[] = self::notifySmsReceptionResponse(3);
                self::sendSMS(self::$msisdn, 'Oops ! You are not an active subscriber to'. $service_name.'. Contact Customer Care for more info.', thread_ctrl::get_unique_trace_id(), $service_local_id);
            }
        } elseif ($service_state === false){
            /* service has closed */
            $process_result[] = "Oops ! The requested service is already closed.";
            $process_result[] = self::notifySmsReceptionResponse(6);
            self::sendSMS(self::$msisdn, $process_result[0], thread_ctrl::get_unique_trace_id(), $service_local_id);

        } else {
            /* current service is not running */
            $process_result[] = "Oops ! The requested service will be launching on ". substr($start_date, 0, 10);
            $process_result[] = self::notifySmsReceptionResponse(5);
            self::sendSMS(self::$msisdn, $process_result[0], thread_ctrl::get_unique_trace_id(), $service_local_id);
        }
        return $process_result;
    }


    /* start service on request */
    public static function start_service_on_demand($user_id, $service_local_id)
    {
        /* required parameters */
        $params = array(
            'user_id' => $user_id,
            'msisdn' => self::$msisdn,
            'message'=>urlencode(self::$message),
            'gwtype'=> (self::$gwTypeSMS == true) ? 'sms': 'ussd'
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
        /* start queueing requested service */
        return file_get_contents('http://localhost'.DEFAULT_PORT.'/modules/servicing_on_demand?service_local_id='.$service_local_id, false, $context);
    }

    /* simulate incoming SDP syncOrderRelationship */

    private static function simultate_SDP_syncOrderRelation($service_id, $request_type, $force_result=null)
    {
        /* required parameters */
        $params = array(
            'msisdn' => self::$msisdn,
            'service_id' => $service_id,
            'request' => $request_type,
            'force_result'=> $force_result

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




    /********* USER SUBSCRIPTION/UN-SUBSCRIPTION/RENEWAL PROCESSES FROM WEB PORTAL **********/

    /* process user un-subscription request */
    private static function process_user_unsubscription($service_local_id)
    {
        global $loggerObj;
        $process_result = null;
        $loggerObj->LogInfo("Incoming Web Portal Unsubscribe Request | ". self::$msisdn);
        /* Check if user has subscription related keyword */
        $service = thread_ctrl::all_service_status($service_local_id);
        $start_date = $service[0][1];
        $broadcast = $service[0][2];
        $end_date = $service[0][4];
        $service_name = $service[0][5];
        $service_keywords = (new services($service_local_id))->keywords;

        if (user::has_current_subscription((new user(user::get_user_id(self::$msisdn)))->services_stats, $service_local_id)){
            /*check if the service is running */
            if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){
                services::request_logged($service_local_id, self::$msisdn, $service_keywords[0], 'unsubscribe', 1, 0);
                $loggerObj->LogInfo("Request Logged successfully!");

                /* forward request to SDP */
                $report = self::unsubscribeProductRequest($service_local_id);
                if (isset($report['code'])){

                    if ($report['code'] == '22007233'){
                        /* request pending approval */
                        $loggerObj->LogInfo("Request pending approval");
                        services::update_logged(self::$msisdn, 0, 1, 0, null, null, 0, null, $service_keywords[0], 'unsubscribe', 0);
                        /* notify the user his subscription request is pending approval */
                        $process_result = "Processing your unsubscribe request for $service_name ... - { SUCCESS } - ". $report['desc'];
                        if (self::$simulation){
                            /* simulate syncOrderRelationship */
                            sleep(5);
                            $process_result .= self::simultate_SDP_syncOrderRelation($service_local_id, 'unsubscribe');
                        }
                    } else if ($report['code'] == '0'){
                        /* request has been successful */
                        $loggerObj->LogInfo("Request successfully");
                        services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], 'unsubscribe', 1, 1);
                        /* notify the request report */
                        $process_result = "Processing your unsubscribe request for $service_name ... - { SUCCESS } - ". $report['desc'];
                        if (self::$simulation){
                            /* simulate syncOrderRelationship */
                            sleep(5);
                            $process_result .= self::simultate_SDP_syncOrderRelation($service_local_id, 'unsubscribe', 1);
                        }
                    } else {
                        /* request failed with an error code - desc */
                        $loggerObj->LogInfo("Request failed");
                        services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'unsubscribe', 0, 11);
                        /* notify the request report */
                        $process_result = "Processing your unsubscribe request for $service_name ... - { ERROR } - ". (is_null($report['desc'])) ? 'An internal Error - Could not format SDP Response' : $report['desc'];
                    }

                } else {
                    $loggerObj->LogInfo("SDP Internal Server Error ");
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], 'unsubscribe', 0, 15);
                    /* processing request to SDP is undergoing which will initiate the incoming syncOrderRelationship */
                    //$process_result = "Your susbcription to ". $service_list[0][5]." was successful. To Opt out dial *136# and choose 'Unsubscribe'. ";
                    $process_result = "Processing your unsubscribe request for $service_name ... - { ERROR } - 500 Internal Server Error";
                }

            } else {
                /* log current request */
                services::request_logged($service_local_id, self::$msisdn, $service_keywords[0], 'unsubscribe', 0, 0);
                $loggerObj->LogInfo("Request Logged successfully!");

                /* current service is not running */
                if (date('Y-m-d h:i:s') > $end_date){
                    services::update_logged(self::$msisdn, 0, 1, 0, null, null, 0, null, $service_keywords[0], 'unsubscribe', 12);
                    $process_result = "Oops ! The requested service $service_name is already closed. Please contact customer care on 0111222333 for any wrong billing";
                } else {
                    $process_result = "Oops ! The requested service $service_name hasn't started. Please try contact customer care on 0111222333 for any wrong billing";
                    services::update_logged(self::$msisdn, 0, 1, 0, null, null, 0, null, $service_keywords[0], 'unsubscribe', 16);
                }
            }

        } else {
            /* log current request */
            services::request_logged($service_local_id, self::$msisdn, $service_keywords[0], 'unsubscribe', 0, 0);
            $loggerObj->LogInfo("Request Logged successfully!");

            /* current service is not running */
            if (date('Y-m-d h:i:s') > $end_date){
                $process_result = "Oops ! The requested service - $service_name is already closed. Please try contact customer care on for any wrong billing";
                services::update_logged(self::$msisdn, 0, 1, 0, null, null, 0, null, $service_keywords[0], 'unsubscribe', 12);
            } else {
                $process_result = "Oops ! You are not subscribed to $service_name Please try contact customer care on for any wrong billing";
                services::update_logged(self::$msisdn, 0, 1, 0, null, null, 0, null, $service_keywords[0], 'unsubscribe', 10);
            }
        }

        return $process_result;
    }

    /* process user subscription-resubscribe requests from Portal */
    private static function process_user_subscription($service_local_id)
    {
        global $loggerObj;
        $process_result = null;

        $loggerObj->LogInfo("Incoming Portal Subscription Request | ". self::$msisdn);


        /* Check if user has subscription related keyword | an array if subscription exist or a string if not */
        $service = thread_ctrl::all_service_status($service_local_id);
        $start_date = $service[0][1];
        $broadcast = $service[0][2];
        $end_date = $service[0][4];
        $service_name = $service[0][5];
        $service_keywords = (new services($service_local_id))->keywords;

        /* log current request */

        if (user::has_current_subscription((new user(user::get_user_id(self::$msisdn)))->services_stats, $service_local_id)){
            services::request_logged($service_local_id, self::$msisdn, $service_keywords[0], 'subscribe', 0, 0);
            $loggerObj->LogInfo("Request Logged successfully!");

            /* User has a existing subscription | check if the service is running */
            if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){
                $process_result =  "You are already subscribed to $service_name To Opt out dial *136# and choose 'Unsubscribe'";
                services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 4);
            } else {
                /* current service is not running */
                if (date('Y-m-d h:i:s') > $end_date){
                    /* service is closed */
                    services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 12);
                    $process_result = "Oops ! The requested service $service_name has closed on $end_date Try again when the service re-open";
                } else {
                    services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 16);
                    $process_result = "Oops ! The requested service hasn't started. Please try again on $start_date";
                }
            }

        } else {
            /* User doesn't have a subscription | Send subscribeProduct to SDP for the requested service
            check if the service is running */
            if (thread_ctrl::service_has_started($start_date, $end_date) && $broadcast != 0){
                services::request_logged($service_local_id, self::$msisdn, $service_keywords[0], 'subscribe', 0, 1);
                $loggerObj->LogInfo("Request Logged successfully!");

                /* forward request to SDP */
                $report = self::subscribeProductRequest($service_local_id);
                if (isset($report['code'])){
                    if ($report['code'] == '22007233'){
                        /* request pending approval */
                        $loggerObj->LogInfo("Request pending approval");
                        services::update_logged(self::$msisdn, 0, 1, 0, null, null, 0, null, $service_keywords[0], 'subscribe', 0, 0);
                        /* notify the user his subscription request is pending approval */
                        $process_result = "Processing your subscription request for $service_name ... - { IN PROGRESS } - ". $report['desc'];
                        if (self::$simulation){
                            /* simulate syncOrderRelationship */
                            sleep(5);
                            $process_result .= self::simultate_SDP_syncOrderRelation($service_local_id, 'subscribe');
                        }
                    } else if ($report['code'] == '0'){
                        /* request has been successful */
                        $loggerObj->LogInfo("Request successful");
                        services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], 'subscribe', 1, 1);
                        /* notify the request report */
                        $process_result = "Processing your subscription request for $service_name ... - { SUCCESS } - ". $report['desc'];
                        if (self::$simulation){
                            /* simulate syncOrderRelationship */
                            sleep(5);
                            $process_result .= self::simultate_SDP_syncOrderRelation($service_local_id, 'subscribe', 1);
                        }
                    } else {
                        /* request failed with an error code - desc */
                        $loggerObj->LogInfo("Request failed");
                        services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 11);
                        /* notify the request report */
                        $process_result = "Processing your subscription request for $service_name ... - { ERROR } - ". (is_null($report['desc'])) ? 'An internal Error - Could not format SDP Response' : $report['desc'];
                    }
                } else {
                    $loggerObj->LogInfo("SDP Internal Server Error ");
                    services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 15);
                    /* processing request to SDP is undergoing which will initiate the incoming syncOrderRelationship */
                    //$process_result = "Your susbcription to ". $service_list[0][5]." was successful. To Opt out dial *136# and choose 'Unsubscribe'. ";
                    $process_result = "Processing your subscription request for $service_name ... - { ERROR } - 500 Internal Server Error";
                }
            } else {
                services::request_logged($service_local_id, self::$msisdn, $service_keywords[0], 'subscribe', 0, 0);
                $loggerObj->LogInfo("Request Logged successfully!");
                /* current service is not running */
                if (date('Y-m-d h:i:s') > $end_date){
                    /* service is closed */
                    services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 12);
                    $process_result = "Oops ! The requested service $service_name is already closed.";
                } else {
                    $process_result = "Oops ! The requested service $service_name hasn't started. Please try again on $start_date";
                    services::update_logged(self::$msisdn, 3, 0, null, null, null, null, null, $service_keywords[0], 'subscribe', 0, 16);
                }
            }
        }
        return $process_result;
    }

    /* process user dataSync SDP response */
    private static function report_sdp_dataSync($sub_response, $service_local_id, $service_keywords)
    {
        global $loggerObj;

        /* report :
          success,1,message_report = user added successfully | the report contain the new user_id
          success,2,message_report  = user added without| stats=current level, score, service_id | very unlikely to happen | dev team has been notified in case
          error,code,message_report  = Failed creating user into the database | dev team has been notified | report to SDP as an error due to system down
          success,4,message_report  = Current user has an existing subscription
        */
        $process_result = null;

        if ($sub_response[0] == 'success'){
            /* subscribed / re-subscribed request */
            switch($sub_response[1]){
                case '1':
                case '2':
                /* sent a syncOrderRelationshipResponse to confirm that the user has been recorded to SDP */
                $process_result[] = self::syncOrderRelationshipResponse(0);

                /* Update request State */
                //$loggerObj->LogInfo("SUCCESS now updating request type : KEYWORD|". services::get_keyword_byID(self::$serviceID)." | REQ_TYPE|". self::$message);

                services::update_logged(self::$msisdn, 3, 1, self::$updateType, self::$ns1updateDesc, null, self::$freePeriod, self::$serviceID, $service_keywords[0], self::$message, 1, $sub_response[1]);
                /* Please enable this for simulation only | assuming sending a welcome message is already been handled by SDP | */
                if (self::$simulation){
                    //self::sendSMS(self::$linkid, $timestamp, self::$hashedPassword, self::$msisdn, service_level_1::get_welcome_message());
                }

                /* prevent users that are subscribing after the closing time to be queued */
                $broadcast_settings = settings::get_broadcast_scheduled_set(1);
                if (date('H:i:s') > $broadcast_settings['close_time'] && (new services($service_local_id))->service_type != 3){
                    $logger = 'Content broadcast can not be queued. Service has closed today. Content will be sent in tomorrow broadcast';
                    /* process user entry into the draw with no notification */
                    // services::process_user_entry_allocation($report[2], $service_local_id, 'subscribe');
                } else {
                    /* check if it's an early morning subscription before the broadcast scheduled */
                    if (date('H:i:s') < $broadcast_settings['start_time'] && (new services($service_local_id))->service_type != 3){
                        /* process user entry into the draw with no notification */
                        //services::process_user_entry_allocation($report[2], $service_local_id, 'subscribe');
                        $logger = 'Content broadcast can not be queued. Service hasn\'t started. Content will be sent in today\'s broadcast';
                    } else {
                        /* delay the first content to allow a welcome message delivery from SDP */
                        $logger = (services::delay_content_delivery(0, user::get_user_id(self::$msisdn), $service_local_id, null, self::$msisdn, 30) == true) ?
                            'Content broadcast has been queued successfully for MSISDN: '. self::$msisdn. ' | service: '. (new services($service_local_id))->name : 'Internal Error - Unable to queue broadcast content for MSISDN: '. self::$msisdn;
                    }
                }

                $loggerObj->LogInfo($logger);

                $process_result[] = $logger;

                    break;
                case '4':
                    /* user has existing subscription | report to SDP as a success */
                    $process_result[] = self::syncOrderRelationshipResponse(0);
                    /* Update request State */
                    //$loggerObj->LogInfo("FAILS now updating request type : KEYWORD|". services::get_keyword_byID(self::$serviceID)." | REQ_TYPE|". self::$message. "| SERVICE_ID:|". self::$serviceID);
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 0, $sub_response[1]);
                    $process_result[] = $sub_response[2];
                    break;
                case '8':
                    /* user successfully re-subscribed */
                    $process_result[] = self::syncOrderRelationshipResponse(0);
                    /* Update request State */
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 1, $sub_response[1]);
                    $process_result[] = $sub_response[2];
                    break;

            }
            /* un-subscribed request */
        } else if ($sub_response[0] == 'deactivated') {
            switch($sub_response[1]){
                case '5':
                    /* user not found on the system - code 5 */
                    $process_result[] = self::syncOrderRelationshipResponse(3);
                    /* Update request State */
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 0, $sub_response[1]);
                    $process_result[] = $sub_response[2];
                    break;
                case '6':
                    /* user un-subscribed successfully! - code 6 */
                    $process_result[] = self::syncOrderRelationshipResponse(0);
                    /* Update request State */
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 1, $sub_response[1]);
                    $process_result [] = $sub_response[2];
                    break;
                case '10':
                    /* the user has no running subscription - code 10 ! */
                    $process_result[] = self::syncOrderRelationshipResponse(0);
                    /* Update request State */
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 0, $sub_response[1]);
                    $process_result[] = $sub_response[2];
                    break;
                default:
                    /* the service is currently down - code 7 */
                    $process_result[] = self::syncOrderRelationshipResponse(5);
                    /* Update request State */
                    services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 0, $sub_response[1]);
                    $process_result[] = $sub_response[2];
                    break;
            }
        } else if ($sub_response[0] == 'sdp'){
            $process_result[] = self::syncOrderRelationshipResponse(8);
            $process_result[] = 'SDP Report - Request has failed. Please try again ! - code 11';
            /* Update request State */
            services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 0, 11);

        } else if ($sub_response[0] == 'renewal') {
            /* subscription renewal has been successfully recorded */
            $process_result[] = self::syncOrderRelationshipResponse(0);
            $process_result[] = 'Subscription renewal has been successful ! - code 1';
            /* Update request State */
            services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 1, 1);
        } else {
            /* send an error to SDP of service currently down */
            $process_result[] = self::syncOrderRelationshipResponse(5);
            /* Update request State */
            services::update_logged(self::$msisdn, 3, 1, null, null, null, null, null, $service_keywords[0], self::$message, 0, 9);

            $process_result[] = 'Service currently down - code 9 - code 3';
        }
        return $process_result;
    }



    /* process user subscription or re-subscription requests  */
    private static function process_user_SyncSubscription($service_local_id, $service_keywords, $product_id)
    {
        //global $loggerObj;

        /* determine the syncOrderRelation request*/
        if (self::$updateType == '1'){
            /* subscription */
            self::$message = 'subscribe';
        } else if (self::$updateType == '2'){
            /* unsubscribe request */
            self::$message = 'unsubscribe';
        } else if (self::$updateType == '3') {
            /* subscription renewal */
            self::$message = 'renewal';
        } else {
            /* fails request | assume sdp send a type fails request decide upon it in our custom demo */
            if (self::$simulation){
                $request_type = explode('-',self::$ns1updateDesc);
                if ($request_type[1] == 'subscribe'){
                    self::$message = 'subscribe';
                } else if ($request_type[1] == 'unsubscribe') {
                    self::$message = 'unsubscribe';
                } else {
                    self::$message = 'renewal';
                }
            }
        }

        /* update incoming sdp report */
        services::direct_request_logged(self::$msisdn, $service_keywords[0], self::$message,
            $service_local_id, 2, self::$updateType, self::$ns1updateDesc, self::$transactionID, self::$freePeriod, self::$serviceID, self::$productID);

        /* on simulation a fails request type = 0 */
        if (self::$updateType == '0') {
            /* fails request */
            return 'sdp';
        }


        /* no servicing is needed on renewal */
        if (self::$updateType == '3'){
            return 'renewal';
        }

        /* return the report result */
        return file_get_contents('http://localhost'.DEFAULT_PORT.'/modules/servicing_subscription?mode='. self::$message.'&msisdn='.self::$msisdn.'&serviceID='.$service_local_id.'&productID='. $product_id);
    }


    /************** OUTGOING OPERATION TO SDP PROCESSES **************/

    /* send a subscribeProduct Request to SDP */
    public static function subscribeProductRequest($service_local_id, $authToken=null)
    {
        global $loggerObj;

        $loggerObj->LogInfo("======================== SUBSCRIBE TO PRODUCT REQUEST  =======================");

        /* initialise the service parameters */
        $current_service = new services($service_local_id);
        $serviceID = $current_service->service_sdp_id;
        $products = $current_service->products;
        $spID = $current_service->sp_id;
        $credentials = services::service_credentials($service_local_id);

        /* check if web recruit is requested */
        $auth_param = (is_null($authToken)) ? '': "<oauth_token>$authToken</oauth_token><tns:serviceId>$serviceID</tns:serviceId>";

        $processing_result = null;

        if (self::$simulation){
            /* simulate a processing response code  */
            $proccessing_resp_code = array(
                array('code'=>'10001211', 'desc'=>'An internal error occurs in the SDP.'), // enable to process the request
                array('code'=>'22007233', 'desc'=>'Subscription is being confirmed.'), // request is pending
                array('code'=>'0', 'desc'=>'successfully' ), // request being successful
                false // The requested URL returned error: 500 Internal Server Error
            );
            shuffle($proccessing_resp_code);
            $processing_result = $proccessing_resp_code[0];
        } else {
            $subscribeProductRequest = '
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/subscribe/manage/v1_0/local">
                    <soapenv:Header>
                        <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
                            <tns:spId>'. $spID.'</tns:spId>
                            <tns:spPassword>'.$credentials['hashedpassword'].'</tns:spPassword>
                            <tns:timeStamp>'.$credentials['timestamp'].'</tns:timeStamp>'. $auth_param.'
                        </tns:RequestSOAPHeader>
                    </soapenv:Header>
                    <soapenv:Body>
                        <loc:subscribeProductRequest>
                            <loc:subscribeProductReq>
                                <userID>
                                    <ID>'.self::$msisdn.'</ID>
                                    <type>0</type>
                                </userID>
                                <subInfo>
                                    <productID>'.$products[0]['product_sdp_id'].'</productID>
                                    <operCode>zh</operCode>
                                    <isAutoExtend>0</isAutoExtend>
                                    <channelID>2</channelID>
                                </subInfo>
                            </loc:subscribeProductReq>
                        </loc:subscribeProductRequest>
                    </soapenv:Body>
                </soapenv:Envelope>';
            $loggerObj->LogInfo($subscribeProductRequest);
            $result = self::curlHTTPRequestXML((self::$production == true) ? 'http://'.SDP_PROD_IP.'/SubscribeManageService/services/SubscribeManage':'http://'.SDP_TEST_IP.'/SubscribeManageService/services/SubscribeManage', $subscribeProductRequest);
            $loggerObj->LogInfo("======================== MTN SA SEND SUB-PRODUCT API RESPONSE =======================");
            $loggerObj->LogInfo(print_r($result, true));
            /* check the api response status to update the status of the request if needed */
            if (is_string($result) && strpos($result, 'Error') !== false){
                return false;
            } else {
                $sdpXMLResult = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result));
                $xml = new SimpleXMLElement($sdpXMLResult);
                $sdpResult = json_decode(json_encode((array)$xml), TRUE);
                //$loggerObj->LogInfo(print_r($sdpResult, true));
                $result_report = array();
                $result_report['desc'] = (isset($sdpResult['soapenvBody']['ns1subscribeProductResponse']['ns1subscribeProductRsp']['resultDescription']))? $sdpResult['soapenvBody']['ns1subscribeProductResponse']['ns1subscribeProductRsp']['resultDescription'] : null;
                $result_report['code'] = (isset($sdpResult['soapenvBody']['ns1subscribeProductResponse']['ns1subscribeProductRsp']['result']))? $sdpResult['soapenvBody']['ns1subscribeProductResponse']['ns1subscribeProductRsp']['result'] : null;
                $loggerObj->LogInfo('subscribeProductResponse:'.print_r($result_report, true));
                return $result_report; /* return result code - description */

            }
        }
        return $processing_result;
    }

    private static function unsubscribeProductRequest($service_local_id)
    {
        global $loggerObj;

        $loggerObj->LogInfo("======================== UNSUBSCRIBE TO PRODUCT  =======================");

        /* intialise variables */
        /* initialise the service parameters */
        $current_service = new services($service_local_id);
        $current_user_product_id = user::service_product_selected((new user(user::get_user_id(self::$msisdn)))->services_stats, $service_local_id);
        $productID = services::get_sdp_product_id_by_product_id($current_service->products, $current_user_product_id);
        $spID = $current_service->sp_id;
        $credentials = services::service_credentials($service_local_id);

        $processing_result = null;
        if (self::$simulation){
            /* simulate a processing response code  */
            $proccessing_resp_code = array(
                array('code'=>'10001211', 'desc'=>'An internal error occurs in the SDP.'), // enable to process the request
                array('code'=>'22007233', 'desc'=>'UnSubscription is being confirmed.'), // request is pending
                array('code'=>'0', 'desc'=>'successfully' ), // request being successful
                false // The requested URL returned error: 500 Internal Server Error
                );
            shuffle($proccessing_resp_code);
            $processing_result = $proccessing_resp_code[0];
        } else {
            $UnSubscribeProductRequest = '
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/subscribe/manage/v1_0/local">
                    <soapenv:Header>
                        <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
                            <tns:spId>'. $spID . '</tns:spId>
                            <tns:spPassword>'.$credentials['hashedpassword'].'</tns:spPassword>
                            <tns:timeStamp>'.$credentials['timestamp'].'</tns:timeStamp>
                        </tns:RequestSOAPHeader>
                    </soapenv:Header>
                    <soapenv:Body>
                        <loc:unSubscribeProductRequest>
                            <loc:unSubscribeProductReq>
                                <userID>
                                    <ID>'.self::$msisdn.'</ID>
                                    <type>0</type>
                                </userID>
                                <subInfo>
                                    <productID>'.$productID.'</productID>
                                    <operCode>zh</operCode>
                                    <isAutoExtend>0</isAutoExtend>
                                    <channelID>2</channelID>
                                </subInfo>
                            </loc:unSubscribeProductReq>
                        </loc:unSubscribeProductRequest>
                    </soapenv:Body>
                </soapenv:Envelope>
                ';
            $loggerObj->LogInfo($UnSubscribeProductRequest);
            $result = self::curlHTTPRequestXML((self::$production == true) ? 'http://'.SDP_PROD_IP.'/SubscribeManageService/services/SubscribeManage':'http://'.SDP_TEST_IP.'/SubscribeManageService/services/SubscribeManage', $UnSubscribeProductRequest);
            $loggerObj->LogInfo("======================== MTN SA SEND UNSUB-PRODUCT API RESPONSE =======================");
            $loggerObj->LogInfo(print_r($result, true));
            /* check the api response status to update the status of the request if needed */
            if (is_string($result) && strpos($result, 'Error') !== false){
                return false;
            } else {
                $sdpXMLResult = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result));
                $xml = new SimpleXMLElement($sdpXMLResult);
                $sdpResult = json_decode(json_encode((array)$xml), TRUE);
                $loggerObj->LogInfo(print_r($sdpResult, true));
                $result_report = array();
                $result_report['code'] = (isset($sdpResult['soapenvBody']['ns1unSubscribeProductResponse']['ns1unSubscribeProductRsp']['result']))? $sdpResult['soapenvBody']['ns1unSubscribeProductResponse']['ns1unSubscribeProductRsp']['result'] : null;
                $result_report['desc'] = (isset($sdpResult['soapenvBody']['ns1unSubscribeProductResponse']['ns1unSubscribeProductRsp']['resultDescription']))? $sdpResult['soapenvBody']['ns1unSubscribeProductResponse']['ns1unSubscribeProductRsp']['resultDescription'] : null;
                $loggerObj->LogInfo('unSubscribeProductResponse - :'.print_r($result_report, true));
                return $result_report; /* return result code and description */

            }
        }
        return $processing_result;
    }

    /* notify SDP acknowledge receipt of the request | a response */
    private static function UssdReceptionResponse()
    {
        global $loggerObj;

        $loggerObj->LogInfo("======================== RESPONSE TO SDP acknowledge receipt of the request SUCCESS =======================");
        if (self::$simulation){
            $loggerObj->LogInfo("======================== MTN CONGO SEND USSD API RESPONSE =======================");
            $loggerObj->LogInfo("USSDNotification SUCCESS");
        } else {
            $xmlPostData =
                '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                    xmlns:loc="http://www.csapi.org/schema/parlayx/ussd/notification/v1_0/local">
                <soapenv:Header/>
                    <soapenv:Body>
                        <loc:notifyUssdReceptionResponse>
                            <loc:result>0</loc:result>
                        </loc:notifyUssdReceptionResponse>
                    </soapenv:Body>
                </soapenv:Envelope>';

            $result = self::curlHTTPRequestXML('http://http://41.206.4.162:8443/USSDNotificationManagerService/services/USSDNotificationManager', $xmlPostData);
            $loggerObj->LogInfo("======================== MTN CONGO SEND USSD API RESPONSE =======================");
            $loggerObj->LogInfo(print_r($result, true));
        }
    }

    /* notify SDP acknowledge receipt of the request | a response */
    private static function SmsReceptionResponse()
    {
        global $loggerObj;

        $loggerObj->LogInfo("======================== RESPONSE TO SDP acknowledge receipt of the request SUCCESS =======================");
        if (self::$simulation){
            $loggerObj->LogInfo("======================== MTN SOUTH AFRICA SEND USSD API RESPONSE =======================");
            $loggerObj->LogInfo("SMSNotification SUCCESS");
        } else {
            $xmlPostData =
                '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                    xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <loc:notifySmsReceptionResponse/>
                    </soapenv:Body>
                </soapenv:Envelope>';

            $result = self::curlHTTPRequestXML('http://196.11.240.224:8310/SmsNotificationManagerService/services/SmsNotificationManager', $xmlPostData);
            $loggerObj->LogInfo("======================== MTN SOUTH AFRICA SEND USSD API RESPONSE =======================");
            $loggerObj->LogInfo(print_r($result, true));
        }
    }

    /* notify SDP acknowledge receipt of the request for current user a success | a response */
    private static function syncOrderRelationshipResponse($return_type)
    {
        $code = '0';
        $description = '';
        switch($return_type){
            case 0:
                /* success */
                $code = '0';
                $description = 'OK';
                break;
            case 1:
                /* wrong SDP send wrong format */
                $code = '1211';
                $description = 'The field format is incorrect or the service is invalid';
                break;
            case 2:
                /* subscription exists */
                $code = '2030';
                $description = 'The subscription relationship already exists';
                break;
            case 3:
                /* subscription doesn't exists */
                $code = '2031';
                $description = 'The subscription relationship does not exist';
                break;
            case 4:
                /* The service does not exist */
                $code = '2032';
                $description = 'The service does not exist';
                break;
            case 5:
                /* The service is unavailable */
                $code = '2033';
                $description = 'The service is unavailable. Contact SP';
                break;
            case 6:
                $code = '2034';
                $description = 'The service is unavailable. Campaign closed';
                break;
            case 7:
                $code = '2035';
                $description = 'The service is unavailable. Campaign is not opened for subscription. Contact SP';
                break;
            case 8:
                $code = '2500';
                $description = 'An internal system error occurred';
                break;
        }

        return
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/data/sync/v1_0/local">
                <soapenv:Header/>
                <soapenv:Body>
                    <loc:syncOrderRelationResponse>
                        <loc:result>'.$code.'</loc:result>
                        <loc:resultDescription>'.$description.'</loc:resultDescription>
                    </loc:syncOrderRelationResponse>
                </soapenv:Body>
            </soapenv:Envelope>';
    }

    /* notify SDP notifySmsDeliveryReceiptResponse */
    private static function notifySmsDeliveryReceiptResponse($return_type)
    {
        $code = '';
        $desc = '';
       switch($return_type){
           case 0:
               /* success */
               return
                   '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
                <soapenv:Header/>
                    <soapenv:Body>
                        <loc:notifySmsDeliveryReceiptResponse/>
                    </soapenv:Body>
            </soapenv:Envelope>';
               break;
           case 1:
            /* error */
            $code = '1211';
            $desc = 'SDP correlatorID received could not be verified from the previous request';
               break;
       }
        return
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
                <soapenv:Header/>
                    <soapenv:Body>
                        <loc:notifySmsDeliveryReceiptResponse>
                            <loc:result>'.$code.'</loc:result>
                            <loc:resultDescription>'.$desc.'</loc:resultDescription>
                        </loc:notifySmsDeliveryReceiptResponse>
                    </soapenv:Body>
            </soapenv:Envelope>';
    }

    /* notify SDP notifySmsDeliveryReceiptResponse */
    private static function notifySmsReceptionResponse($return_type)
    {
        $code = '';
        $description = '';
        switch($return_type){
            case 0:
                /* success */
                return
                    '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
                        <soapenv:Header/>
                        <soapenv:Body>
                            <loc:notifySmsReceptionResponse/>
                        </soapenv:Body>
                    </soapenv:Envelope>';
                break;
            case 3:
                /* The service does not exist */
                $code = '2031';
                $description = 'The user has no subscription';
                break;
            case 4:
                /* The service does not exist */
                $code = '2032';
                $description = 'The service does not exist';
                break;
            case 5:
                /* The service is unavailable */
                $code = '2033';
                $description = 'The service is unavailable or Invalid. Contact SP';
                break;
            case 6:
                $code = '2034';
                $description = 'The service is unavailable. Campaign closed';
                break;
            case 7:
                $code = '2500';
                $description = 'An internal system error occurred';
                break;
        }
        return
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
                <soapenv:Header/>
                    <soapenv:Body>
                        <loc:notifySmsReceptionResponse>
                            <loc:result>'.$code.'</loc:result>
                            <loc:resultDescription>'.$description.'</loc:resultDescription>
                        </loc:notifySmsReceptionResponse>
                    </soapenv:Body>
            </soapenv:Envelope>';
    }

    /* Send SMS */
    private static function sendSMS($msisdn, $message, $tracer_id, $service_id)
    {
        global $loggerObj;
        /* initialise the service parameters */
        $current_service = new services($service_id);
        $serviceID = $current_service->service_sdp_id;
        $accesscode = $current_service->accesscode;
        $spID = $current_service->sp_id;
        $credentials = services::service_credentials($service_id);
        $enable_serviceID = $current_service->service_type == 3 ? '' : '<v2:serviceId>'.$serviceID.'</v2:serviceId>';

        $xmlPostData =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1"
                xmlns:loc="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local">
                <soapenv:Header>
                    <v2:RequestSOAPHeader>
                        <v2:spId>'. $spID .'</v2:spId>
                        <v2:spPassword>' . $credentials['hashedpassword'] . '</v2:spPassword>'.$enable_serviceID.'
                        <v2:timeStamp>' . $credentials['timestamp'] . '</v2:timeStamp>
                        <v2:OA>' . $msisdn . '</v2:OA>
                        <v2:FA>' . $msisdn . '</v2:FA>
                   </v2:RequestSOAPHeader>
                </soapenv:Header>
                <soapenv:Body>
                    <loc:sendSms>
                        <loc:addresses>' . $msisdn . '</loc:addresses>
                        <loc:senderName>'.$accesscode.'</loc:senderName>
                        <loc:message>' . $message . '</loc:message>
                        <loc:receiptRequest>
                            <endpoint>'.ENDPOINTS_SMS.'</endpoint>
                            <interfaceName>SmsNotification</interfaceName>
                            <correlator>'. $tracer_id.'</correlator>
                        </loc:receiptRequest>
                    </loc:sendSms>
                </soapenv:Body>
            </soapenv:Envelope>';

        $loggerObj->LogInfo("======================== SEND SMS REQUEST =======================");
        if (self::$simulation) {
            $loggerObj->LogInfo($xmlPostData);
            $result = self::curlHTTPRequestXML('http://localhost'.DEFAULT_PORT.'/demo/simulate_smsResponse', $xmlPostData);
            $loggerObj->LogInfo("======================== MTN SMS API RESPONSE SIMULATION =======================");
            //$loggerObj->LogInfo(print_r($result, true));
            /* check the api response status to update the status of current request if needed  */
            if (is_string($result) && strpos($result, 'Error') !== false){
                return $result;
            } else {
                $sdpXMLResult = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result));
                $xml = new SimpleXMLElement($sdpXMLResult);
                $sdpResult = json_decode(json_encode((array)$xml), TRUE);
                //$loggerObj->LogInfo(print_r($sdpResult, true));
                $result_report = (isset($sdpResult['soapenvBody']['ns1sendSmsResponse']['ns1result'])) ? $sdpResult['soapenvBody']['ns1sendSmsResponse']['ns1result'] : null;
                $loggerObj->LogInfo('SmsResponse Result:'.print_r($result_report, true));
                if ($result_report && !is_array($result_report)){
                    /* sms sent successfully! */
                    return 'Sent successfully!';
                } else {
                    /* sms fails ! */
                    return $result_report['errorCode']. ' | '. $result_report['Description'];
                }
            }
        } else {
            //$loggerObj->LogInfo($xmlPostData);
            $result = self::curlHTTPRequestXML((self::$production == true) ? 'http://'. SDP_PROD_IP.'/SendSmsService/services/SendSms':'http://'. SDP_TEST_IP.'/SendSmsService/services/SendSms', $xmlPostData);
            $loggerObj->LogInfo("======================== MTN SA SEND SMS API RESPONSE =======================");
            $loggerObj->LogInfo(print_r($result, true));
            /* check the api response status to update the status of the request if needed */
            if (is_string($result) && strpos($result, 'Error') !== false){
                return $result;
            } else {
                $sdpXMLResult = trim(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result));
                $xml = new SimpleXMLElement($sdpXMLResult);
                $sdpResult = json_decode(json_encode((array)$xml), TRUE);
                //$loggerObj->LogInfo(print_r($sdpResult, true));
                $result_report = (isset($sdpResult['soapenvBody']['ns1sendSmsResponse']['ns1result']))? $sdpResult['soapenvBody']['ns1sendSmsResponse']['ns1result'] : null;
                $loggerObj->LogInfo('SmsResponse Result:'.print_r($result_report, true));
                if ($result_report && !is_array($result_report)){
                    /* sms sent successfully! */
                    return 'Sent successfully!';
                } else {
                    /* sms fails ! */
                    return $result_report['errorCode']. ' | '. $result_report['Description'];
                }
            }
        }
    }


    /* send USSD */
    private static function sendUSSD($senderCB, $msisdn, $message, $session, $service_id, $linkid=null)
    {
        global $loggerObj;
        /* <ns2:msgType>0</ns2:msgType>
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

        global $loggerObj;
        /* initialise the service parameters */
        $current_service = new services($service_id);
        $serviceID = $current_service->service_sdp_id;
        $spID = $current_service->sp_id;
        $credentials = services::service_credentials($service_id);

        /* determine the session */
        if (is_string($session)){

            $session_var = explode(',', $session);
            $msgType = null;
            $ussdOpType = null;
            /* session | continue > response */
            if ($session_var[0] == '1' && $session_var[1] == '3'){
                $msgType = $session_var[0];
                $ussdOpType = $session_var[1];
            } else if ($session_var[0] == '2' && $session_var[1] == '3'){
                /* session | close session - end > user started */
                $msgType = $session_var[0];
                $ussdOpType = $session_var[1];
            } else if ($session_var[0] == '0' && $session_var[1] == '1'){
                /* session | restart the session */
                $msgType = $session_var[0];
                $ussdOpType = $session_var[1];
            } else {
                $loggerObj->LogInfo("SEND USSD REQUEST - ERROR WRONG SESSION");
                die('Application ERROR - WRONG SESSION');
            }

            $xmlPostData =
                '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                            xmlns:loc="http://www.csapi.org/schema/parlayx/ussd/send/v1_0/local">
                <soapenv:Header>
                    <tns:RequestSOAPHeader xmlns:tns="http://www.huawei.com.cn/schema/common/v2_1">
                        <tns:spId>'.$spID.'</tns:spId>
                        <tns:spPassword>'.$credentials['hashedpassword'].'</tns:spPassword>
                        <tns:serviceId>'.$serviceID.'</tns:serviceId>
                        <tns:timeStamp>'.$credentials['timestamp'].'</tns:timeStamp>
                        <tns:OA>'.$msisdn.'</tns:OA>
                        <tns:FA>'.$msisdn.'</tns:FA>
                        <tns:linkid>'.$linkid.'</tns:linkid>
                    </tns:RequestSOAPHeader>
                </soapenv:Header>
                <soapenv:Body>
                    <loc:sendUssd>
                         <loc:msgType>'.$msgType.'</loc:msgType>
                         <loc:senderCB>'.$senderCB.'</loc:senderCB>
                         <loc:receiveCB/>
                         <loc:ussdOpType>'.$ussdOpType.'</loc:ussdOpType>
                         <loc:msIsdn>'.$msisdn.'</loc:msIsdn>
                         <loc:serviceCode>166</loc:serviceCode>
                         <loc:codeScheme>15</loc:codeScheme>
                         <loc:ussdString>'.$message.'</loc:ussdString>
                    </loc:sendUssd>
                </soapenv:Body>
            </soapenv:Envelope>';

            $loggerObj->LogInfo("======================== SEND USSD REQUEST =======================");
            if (self::$simulation) {
                $loggerObj->LogInfo($xmlPostData);
                $loggerObj->LogInfo("======================== MTN SA SEND USSD API RESPONSE =======================");
                $loggerObj->LogInfo("USSD SIMULATION Notification SUCCESS");
            } else {

                $loggerObj->LogInfo($xmlPostData);
                $result = self::curlHTTPRequestXML('https://41.206.4.162:8443/SendUssdService/services/SendUssd', $xmlPostData);
                $loggerObj->LogInfo("======================== MTN SA SEND USSD API RESPONSE =======================");
                $loggerObj->LogInfo(print_r($result, true));
            }

        } else {
            $loggerObj->LogInfo("Incorrect session params passed to sendUSSD method - Please provide a session value in string: 1,3 =continue/response; 2,3=end/session");
            die('Incorrect session params passed to sendUSSD method - Please provide a session value in string: 1,3 =continue/response; 2,3=end/session');
        }
    }
}