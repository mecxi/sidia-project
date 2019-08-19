<?php
/**
 * User: Mecxi
 * Date: 5/26/2017
 * Time: 11:12 PM
 */

require_once('../config.php');

/* retrieve required params */
/* parse params */
$msisdn = $argv[1];
$user_id = (isset($argv[2])) ? $argv[2] : null;
$service_id = (isset($argv[3]))? $argv[3]: null;;
$sleep = (isset($argv[4]))? $argv[4]: null;;

/* initialise the gateway needed fields */
gateway::$simulation = false;
gateway::$gwTypeSMS = true;
gateway::$message = 'subscribe';
gateway::$msisdn = $msisdn;

/* log the process has started */
if (services::delay_content_delivery(1, $user_id, $service_id, true)){

    sleep((int)$sleep);

    $result = gateway::start_service_on_demand($user_id, $service_id);
    /* update on success or no content found */
    if ($result == 'success'){
        services::delay_content_delivery(1, $user_id, $service_id);
    } else {
        services::delay_content_delivery(1, $user_id, $service_id, 0);
    }
}


