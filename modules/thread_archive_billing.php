<?php
require_once('../config.php');
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 5/27/2017
 * Time: 1:28 AM
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* billing archived will process yesterday billing report. As the process is being triggered
by cleanup scheduler, this will run in the next hour */
$loggerObj->LogInfo("Archiving yesterday billing initialise ...");

//sleep(60);
sleep(4500); //wait for 1h15min

$loggerObj->LogInfo("Archiving yesterday billing started ...");

/* get all bills */
$target_date = date('Y-m-d', strtotime("yesterday"));
$services = services::services_list();
foreach ($services as $service ) {
    process_billing_times($service['id'], $target_date);
}

$loggerObj->LogInfo("Archiving yesterday billing completed successfully ...");

/* backup system database on weekly basis */
backup_database_system();

/* cleanup db gateway*/
cleanup_request_db();



/* process billing time lines */
function process_billing_times($service_id, $target_date){
    /* get billing time-lines */
    $all_bills = services::billing_timelines_pool($service_id, $target_date);
    /* get current play_rate saved */
    $current_play_rate = (new services($service_id))->play_rate;
    for($i=0; $i < count($all_bills); ++$i){
        /* insert into billing service report */
        $day = is_null($target_date) ? $all_bills[$i]['day'] : get_last_day_reported($service_id);
        db::sql("INSERT INTO `tl_services_billing` (`day`,	`date`,	total_new_day, total_unsubs_day, total_subs, total_day_bills, target_day_bills, rate_day_bills, repeat_bills, total_overall_bills, service_id, play_rate)
    VALUES('". (is_null($target_date) ? $day : ++$day) ."', '". $all_bills[$i]['date'] ."', '". $all_bills[$i]['total_new_day'] ."', '". $all_bills[$i]['total_unsubs_day'] ."', '". $all_bills[$i]['total_subs'] ."',
    '". $all_bills[$i]['total_day_bills'] ."', '". $all_bills[$i]['target_day_bills'] ."', '". $all_bills[$i]['rate_day_bills'] ."', '". $all_bills[$i]['repeat_bills'] ."',
    '". $all_bills[$i]['total_overall_bills'] ."', '$service_id', '$current_play_rate')", DB_NAME);
    }
}

/* return the last day reported */
function get_last_day_reported($service_id){
    $result = db::sql("SELECT MAX(`day`) FROM `tl_services_billing` WHERE service_id = '$service_id'", DB_NAME);
    if (mysqli_num_rows($result)){
        while(list($day) = mysqli_fetch_array($result)){
            return (int)$day;
        }
    }
    return 0;
}

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
            if (file_exists("/backup/dump-".$current_week['week_start'].".sql.gz")){
                $del_result = thread_ctrl::run(null, null, "rm -f -r /backup/dump-".$current_week['week_start'].".sql.gz && echo done");
                if (isset($del_result[0]) && $del_result[0] == 'done'){
                    $loggerObj->LogInfo("Deleting previous  archived backup file successfully ...");
                } else {
                    $loggerObj->LogInfo("Error deleting previous archive backup file !");
                }
            }
        } else {
            $loggerObj->LogInfo("An error has occurred creating backup file!");
        }
    }
}

function cleanup_request_db(){
    db::sql("DELETE FROM `tl_sync_services_requests` WHERE req_service = 1 AND resp_type = 3;", "tl_gateway");
    db::sql("DELETE FROM `tl_sync_services_requests` WHERE `code` = 0 AND resp_product_id IS NULL;", "tl_gateway");
}
