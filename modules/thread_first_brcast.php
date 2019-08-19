<?php
/**
 * User: Mecxi
 * Date: 3/16/2017
 * Time: 11:58 AM
 * module : thread_first_brcast will pick queuing first messages to broadcast
 * @update:
 * 16/4/2017 : resolve servicing_on_demand module logging thread while no contents found on a specific date.
 */

require_once('../config.php');


/* prepare thread for first content delivery */
$set_thread_services = 'echo start ';

$users = services::get_first_contents_users();

if ($users){
    $limiter = 0; //limit 100 thread load for session
    foreach($users as $user){
        /* initialise the gateway needed fields */
        $msisdn = $user['msisdn'];
        $user_id = $user['user_id'];
        $service_id = $user['service_id'];
        $sleep = $user['sleep'];
        $set_thread_services .="& php first_brcast_delayer.php $msisdn $user_id $service_id $sleep";
        if ($limiter > 100) break; ++$limiter;
    }
}

if ($set_thread_services != 'echo start '){
    thread_ctrl::run(null, null, $set_thread_services);
}
