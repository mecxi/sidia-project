<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * Current script keeps track of how resources are being used in order to run more thread during broadcast operations
 * Will be automate to run every min once the operation has started
 *     ps -ax | grep mysql #check if mysql is running
 *     ps -ax | grep httpd #check if apache is running
       free -t # display total RAM + SWAP usage  || cat /proc/meminfo
 */

/* check running threads operations and allocate more threads
we allocate more threads only when half of running threads are completed
arg 1: total thread to allocate
arg 2: the thread operation name
*/
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* parse params */
$total_threads = (isset($argv[1])) ? $argv[1]:null;
$service_local_id = (isset($argv[2])) ? $argv[2]:null;

$loggerObj->LogInfo("Parallel processing : ". $total_threads. ' | service local ID: '.$service_local_id);

if (!is_null($total_threads) && !is_null($service_local_id)){
    thread_ctrl::run(null, null, "php servicing_contents.php $total_threads $service_local_id");
}



















