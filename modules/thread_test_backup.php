<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 11/25/2017
 * Time: 8:03 PM
 */

require_once('../config.php');

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* start ddtabse backup on required day */
backup_database_system();


/* backup server database on weekly basis */
function backup_database_system(){
    global $loggerObj;
    if (date('D') == 'Sun'){
        $loggerObj->LogInfo("Starting database backup weekly utility ...");
        $result = thread_ctrl::run(null, null, "/usr/bin/mysqldump -u root -p0023353 --all-databases | gzip -9 > /backup/dump-$( date '+%Y-%m-%d' ).sql.gz && echo done");
        if (isset($result[0]) && $result[0] == 'done'){
            $loggerObj->LogInfo("Backup file created successfully ...");
            /* delete previous backup the uncompressed file */
            $current_week = thread_ctrl::get_start_end_date(date('W'), date('Y'));
            /* chek if the file exist */
            if (file_exists("/backup/dump-".$current_week['week_start']."sql.gz")){
                $del_result = thread_ctrl::run(null, null, "rm -f -r /backup/dump-".$current_week['week_start']."sql.gz && echo done");
                if (isset($del_result[0]) && $del_result[0] == 'done'){
                    $loggerObj->LogInfo("Deleting previous  archive backup file successfully ...");
                } else {
                    $loggerObj->LogInfo("Error deleting non archive backup file !");
                }
            }
        } else {
            $loggerObj->LogInfo("An error has occurred creating backup file!");
        }
    }
}