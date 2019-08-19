<?php
require_once('../config.php');
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 5/27/2017
 * Time: 1:28 AM
 */
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

$loggerObj->LogInfo("Archiving today services datasync, services draw started ...");
/* prepare draw entries */
prepare_draw_entries_pool();

/* archive current play_rate */
archive_play_rate();

/* delete previous archives */
//delete_previous_archive_data();

/* archiving services */
archive_services_trivia();
archive_services_contents();
reset_threading_records();


$loggerObj->LogInfo("Archiving today services datasync, services draw completed successfully ...");

/* archive datasync ::trivia */
function archive_services_trivia($allowed_backup=null){
    if ($allowed_backup) {
        $result = db::sql("SELECT tracer_id, user_id, trivia_id, user_answer, score, process_start, process_end, process_state, cross_sell_id, service_id FROM `tl_servicing_trivia`;", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($trace_id, $user_id, $trivia_id, $user_answer, $score, $process_start, $process_end, $process_state, $cross_sell_id, $service_id) = mysqli_fetch_array($result)) {
                $process_end = (strlen($process_end) > 0) ? $process_end : date('Y-m-d');
                db::sql("INSERT INTO `tl_servicing_trivia`(tracer_id, user_id, trivia_id, user_answer, score, process_start, process_end, process_state, cross_sell_id, service_id)
            VALUES('$trace_id', '$user_id', '$trivia_id', '$user_answer', '$score', '$process_start', '$process_end', '$process_state', '$cross_sell_id', '$service_id');", "tl_datasync_archives");
            }
            /* datasync trivia */
            db::sql("DELETE FROM `tl_servicing_trivia`;", "tl_datasync");
        }
    } else {
        /* datasync trivia */
        db::sql("DELETE FROM `tl_servicing_trivia`;", "tl_datasync");
    }
}

/* archive datasync ::contents */
function archive_services_contents($allowed_backup=null){
    if ($allowed_backup){
        $result = db::sql("SELECT tracer_id, user_id, content_id, process_start, process_end, process_state, service_id, suspended FROM `tl_servicing_contents`;", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($trace_id, $user_id, $content_id, $process_start, $process_end, $process_state, $service_id, $suspended) = mysqli_fetch_array($result)){
                $process_end = (strlen($process_end) > 0) ? $process_end : date('Y-m-d');
                db::sql("INSERT INTO `tl_servicing_contents`(tracer_id, user_id, content_id, process_start, process_end, process_state, service_id, suspended)
            VALUES('$trace_id', '$user_id', '$content_id', '$process_start', '$process_end', '$process_state', '$service_id', '$suspended');", "tl_datasync_archives");
            }

            /* datasync contents */
            db::sql("DELETE FROM `tl_servicing_contents`;", "tl_datasync");
        }
    } else {
        /* datasync contents */
        db::sql("DELETE FROM `tl_servicing_contents`;", "tl_datasync");
    }
}


function reset_threading_records(){
    db::sql("DELETE FROM `tl_threading_services`;", "tl_gateway");
}

function prepare_draw_entries_pool(){
    $service_local_ids = services::services_list();
    foreach($service_local_ids as $service){
        service_draw::process_draw_allocation($service['id'], null, null, true);
    }
}

function archive_play_rate(){
    $service_local_ids = services::services_list();
    foreach($service_local_ids as $service){
        $current_play_rate = services::get_play_rate($service['id']);
        if ($current_play_rate){
            db::sql("UPDATE `tl_services` SET play_rate = '$current_play_rate' WHERE service_local_id = '". $service['id']."'", DB_NAME);
        }
    }
}

/* delete previous archive data */
function delete_previous_archive_data(){
    db::sql("DELETE FROM `tl_servicing_trivia`;", "tl_datasync_archives");
    db::sql("DELETE FROM `tl_servicing_contents`;", "tl_datasync_archives");
}










