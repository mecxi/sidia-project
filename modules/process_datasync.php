<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * @overview: Threading datasync in the system background per request
 */
require_once('../config.php');

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* parse params */
$user_id = $argv[1];
$service_local_id = $argv[2];
$message = (isset($argv[3]))? $argv[3]: null;
$tracer_id = (isset($argv[4]))? $argv[4]: null;
$sleep = (isset($argv[5]))? $argv[5]: null;

if (is_null($service_local_id)){
    exit();
}

$current_user = new user($user_id);

$loggerObj->LogInfo("Starting datasync broadcast Queue | MSISDN : $current_user->msisdn | service: ". (new services($service_local_id))->name);

/* log current datasync has started */
services::log_datasync_thread_started($tracer_id);

/* force a sleep */
sleep((int)$sleep);

/* wait a random number of sec before starting current thread */
$wait_period = 0;
$result = thread_ctrl::run(null, null, "php threading_trivia.php $user_id $service_local_id $wait_period sms $message $tracer_id");
echo $result[0];

/* free up database system memory */
db::close();





