<?php
/**
 * User: Mecxi
 * Date: 4/28/2017
 * Time: 12:53 PM
 * @overview: reset today services
 */

require_once('../config.php');

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* stop all threading services task */
if (stop_services() == 'done'){
    $reset_contents_delivery = services::cleanup_content_delivery();
    $reset_notifies = services::cleanup_notify_queues();
    $reset_datasync = services::cleanup_datasync_queues();
    $reset_bulk = services::cleanup_bulk_sync_queues();

    if ( ($reset_contents_delivery !== false && $reset_notifies !== false && $reset_datasync !== false)){
        $loggerObj->LogInfo('Resetting contents_delivery successfully | deleted rows :'. $reset_contents_delivery);
        $loggerObj->LogInfo('Resetting notify_queues successfully | deleted rows :'. $reset_notifies);
        $loggerObj->LogInfo('Resetting datasync_queues successfully | deleted rows :'. $reset_datasync);

        /* reset last users_IDs logged */
        $reset_ids = services::reset_services_broadcast_tracking_log();
        if (is_numeric($reset_ids)) {
            $loggerObj->LogInfo('Resetting last services broadcast users successfully!');
            reset_datasync_queues();
            reset_threading_records();
            reset_retries_logged();
            reset_last_users_broadcast();
        }

    }
}


function stop_services(){
    $result = thread_ctrl::run(null,null,'crontab -l | grep -v "set_retry.php" | crontab -  && crontab -l | grep -v "set_first_brcast" | crontab - && crontab -l | grep -v "set_notify" | crontab - && crontab -l | grep -v "set_datasync" | crontab - && echo done');
    return $result[0];
}

function reset_datasync_queues(){
    global $loggerObj;

    db::sql("DELETE FROM `tl_datasync_trivia`", "tl_datasync");
    db::sql("DELETE FROM `tl_servicing_contents`", "tl_datasync");
    $result = db::sql("DELETE FROM `tl_servicing_trivia`", "tl_datasync");
    if ($result !== false){
        $loggerObj->LogInfo("Resetting datasync queues successfully for all services");
    }
}

function reset_threading_records(){
    db::sql("DELETE FROM `tl_threading_services`;", "tl_gateway");
}

function reset_retries_logged(){
    global $loggerObj;
    $date = date('Y-m-d');
    $result = db::sql("DELETE FROM `tl_services_retries` WHERE date_created = '$date';", "tl_datasync");
    if ($result !== false){
        $loggerObj->LogInfo("Resetting gateway schedule_retries successfully for all services");
    }
}

function reset_last_users_broadcast(){
    global $loggerObj;
    $result = db::sql("SELECT user_id, last_brcast_id FROM `tl_users_stats`;", DB_NAME);
    if (mysqli_num_rows($result)){
        while(list($user_id, $last_brd_cast) = mysqli_fetch_array($result)){
            if ((int)$last_brd_cast > 0 ){
                $res = db::sql("UPDATE `tl_users_stats` SET last_brcast_id = '". ($last_brd_cast - 1)."' WHERE user_id = '$user_id';", DB_NAME);
                if ($res == 0 || $res === false){
                    $loggerObj->LogInfo("Error resetting last broadcast for USERID: $user_id ");
                }
            }
        }
    }
}


