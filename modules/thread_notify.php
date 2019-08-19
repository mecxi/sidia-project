<?php
/**
 * User: Mecxi
 * Date: 4/25/2017
 * Time: 1:04 AM
 * @module_overview: Create sequential threading for the given operation
 * 30s tl_datasync_notify threading intervals
 */

require_once('../config.php');

//if (thread_ctrl::running_threads("thread_run.php") > 0){
//    exit();
//}

if (thread_ctrl::running_threads("thread_notify_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_notify_tps.php");
} else {
    exit();
}

sleep(10);

if (thread_ctrl::running_threads("thread_notify_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_notify_tps.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_notify_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_notify_tps.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_notify_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_notify_tps.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_notify_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_notify_tps.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_notify_tps.php") < 1){
    thread_ctrl::run(null, null, "php thread_notify_tps.php");
}