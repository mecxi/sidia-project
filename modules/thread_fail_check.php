<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * close allocating tasks, restart the database and set new task
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Starting services fail checking operations ...");

/* set today broadcast operation */
/* reschedule today operation task
 * @thread_run interval : 1m
 * @thread_retry : 15m
*/
$start_thread = thread_ctrl::run(null, null, 'crontab -l | grep -v "set_fail_check.php" | crontab - && (crontab -l ; echo "*/2 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_datasync_ck.php") | crontab - && (crontab -l ; echo "*/4 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_notify_ck.php") | crontab - && echo done');
if ($start_thread[0] == 'done'){
    $loggerObj->LogInfo("Service fail checking has been started successfully @1PM !");
    //echo "Service Broadcast has been set @9AM successfully!";
} else {
    $loggerObj->LogInfo("Error starting today services fail check Operation phase  - Please review the task...");
    //echo "Error setting today services Broadcast Operation phase  - Please review the task...";
}

/* free up database system memory */
db::close();
