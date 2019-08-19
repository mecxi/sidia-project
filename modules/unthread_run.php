<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * unset automated service level operation
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* close run schedule task */
$close_broadcast = thread_ctrl::run(null,null,'crontab -l | grep -v "set_run.php" | crontab - && echo done');
if ($close_broadcast[0] == 'done'){
    $loggerObj->LogInfo("Services broadcast Thread has been removed successfully!");
    //sleep(60);
    /* schedule cleanup every 30min execution interval */
    $schedule_cleanup = thread_ctrl::run(null, null, '(crontab -l ; echo "*/30 * * * * /usr/bin/php '.PROJECT_DIR.'/modules/set_cleanup.php") | crontab - && echo done');
    if ($schedule_cleanup[0] == 'done'){
        $loggerObj->LogInfo("Cleanup Operation has been initialised successfully!");
    } else {
        $loggerObj->LogInfo("Error scheduling cleanup operation - Please review!");
    }
}
