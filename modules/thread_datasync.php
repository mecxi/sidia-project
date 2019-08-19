<?php
/**
 * User: Mecxi
 * Date: 4/25/2017
 * Time: 1:04 AM
 * @module_overview: Create sequential threading for the given operation
 * 4 threads per min that serves 400 TPS
 * 3 sec threading intervals
 */

require_once('../config.php');


if (thread_ctrl::running_threads("thread_datasync_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_datasync_tps.php");
}else {
    exit();
}

sleep(20);

if (thread_ctrl::running_threads("thread_datasync_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_datasync_tps.php");
}

sleep(20);

if (thread_ctrl::running_threads("thread_datasync_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_datasync_tps.php");
}






