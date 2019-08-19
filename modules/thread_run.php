<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * run service prepare operation 
 */
require_once('../config.php');

$broadcast_settings = settings::get_broadcast_scheduled_set(0);

if (date('H:i:s') > $broadcast_settings['pre_close_time']) {
    /* force unset on pre_close time to unset the schedule running thread */
    thread_ctrl::run('unset_run');
}

if (thread_ctrl::running_threads("thread_run_pool.php") < 1){
    thread_ctrl::run(null, null, "php thread_run_pool.php");
} else {
    exit();
}

sleep(10);

if (thread_ctrl::running_threads("thread_run_pool.php") < 1){
    thread_ctrl::run(null, null, "php thread_run_pool.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_run_pool.php") < 1){
    thread_ctrl::run(null, null, "php thread_run_pool.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_run_pool.php") < 1){
    thread_ctrl::run(null, null, "php thread_run_pool.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_run_pool.php") < 1){
    thread_ctrl::run(null, null, "php thread_run_pool.php");
}

sleep(10);

if (thread_ctrl::running_threads("thread_run_pool.php") < 1){
    thread_ctrl::run(null, null, "php thread_run_pool.php");
}
