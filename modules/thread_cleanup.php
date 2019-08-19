<?php
require_once('../config.php');
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 12/26/2016
 * Time: 10:40 PM
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* @11:30pm - close current task | stop running thread every 15min */
$broadcast_settings = settings::get_broadcast_scheduled_set(0);
if ($broadcast_settings){
    if (date('H:i:s') > $broadcast_settings['close_time']){
        $loggerObj->LogInfo("Closing All Services broadcast Operation phase ...");
        /* close all services task */
        $close_result = thread_ctrl::run(null,null,'crontab -l | grep -v "set_cleanup.php" | crontab - && crontab -l | grep -v "set_datasync_ck.php" | crontab - && crontab -l | grep -v "set_notify_ck.php" | crontab - && echo done');
        if ($close_result[0] == 'done') {
            $loggerObj->LogInfo("Clearing all schedules broadcast - done successfully!");
            /* 1min clearing time is needed to proceed */
            sleep(60);

            /* archive all services datasync */
            thread_ctrl::run(null, null, "php thread_archive_services.php");

            $loggerObj->LogInfo('Successfully archiving datasync related queues services');
            /* cleanup first content broadcast request data */
            $loggerObj->LogInfo((services::cleanup_content_delivery() > 0) ? 'Successfully cleanup first content db cache' : 'Internal error cleanup first content db cache');
            /* cleanup notify queues data */
            $loggerObj->LogInfo((services::cleanup_notify_queues() > 0) ? 'Successfully cleanup notify queues db cache' : 'Internal error cleanup notify queues db cache');
            /* cleanup notify queues data */
            $loggerObj->LogInfo((services::cleanup_datasync_queues() > 0) ? 'Successfully cleanup datasync queues db cache' : 'Internal error cleanup datasync queues db cache');
            /* cleanup cross_sell queues data */
            $loggerObj->LogInfo((services::cleanup_bulk_sync_queues() > 0) ? 'Successfully cleanup Bulk Sync queues db cache' : 'Internal error cleanup Bulk Sync queues db cache');

            /* reset last users_IDs logged */
            $reset_ids = services::reset_services_broadcast_tracking_log();
            if (is_numeric($reset_ids)) {
                $loggerObj->LogInfo("Reset Last BroadCast Tracking Logs - done successfully!");
                sleep(30);

                /* reschedule today operation task to prepare the daily Broadcast @08:50AM
                 From 1PM the system will start checking any fail contents and suspensions every 5min */
                $reschedule = thread_ctrl::run(null, null, '(crontab -l ; echo "'. $broadcast_settings['start_time'].' * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_reset.php") | crontab - && (crontab -l ; echo "'. $broadcast_settings['fail_check_time'].' * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_fail_check.php") | crontab - && echo done');
                if ($reschedule[0] == 'done'){
                    $loggerObj->LogInfo("Reschedule today service - done successfully!");
                    /* archive yesterday billing */
                    $archive_bill_res = thread_ctrl::run(null, null, "php thread_archive_billing.php && echo done");
                    if ($archive_bill_res[0] == 'done'){
                        /* prepare services pool contents */
                        //thread_ctrl::run(null, null, '(crontab -l ; echo "*/1 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_run.php") | crontab - && echo done');
                    }
                } else {
                    $loggerObj->LogInfo("Error rescheduling today broadcast - Please review the task!");
                }
            }
        }
    }
} else {
    $loggerObj->LogInfo("Internal Errors getting system start_close time - Please check the system settings are set to run cleaning Services broadcast Operation phase");
}

/* free up database system memory */
db::close();