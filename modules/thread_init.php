<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * close allocating tasks, restart the database and set new task
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Starting today services Broadcast Operation phase ...");

/* set today broadcast operation */
/* reschedule today operation task
 * @thread_run interval : 1m
 * @thread_retry : 15m
*/
$start_thread = thread_ctrl::run(null, null, 'crontab -l | grep -v "set_reset.php" | crontab - && crontab -l | grep -v "set_cleanup.php" | crontab - && (crontab -l ; echo "*/1 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_run.php") | crontab - && (crontab -l ; echo "*/5 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_retry.php") | crontab - && (crontab -l ; echo "*/1 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_first_brcast.php") | crontab - && (crontab -l ; echo "*/1 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_notify.php") | crontab - && (crontab -l ; echo "*/1 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_datasync.php") | crontab - && (crontab -l ; echo "00 20 * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_fail_check.php") | crontab - && echo done');
if ($start_thread[0] == 'done'){
    $loggerObj->LogInfo("Service Broadcast has been started successfully @9AM !");
    //echo "Service Broadcast has been set @9AM successfully!";
} else {
    $loggerObj->LogInfo("Error starting today services Broadcast Operation phase  - Please review the task...");
    //echo "Error setting today services Broadcast Operation phase  - Please review the task...";
}

/* free up database system memory */
db::close();
