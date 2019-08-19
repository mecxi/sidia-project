<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * Start service draw operation scheduler
 */

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Automated Service Draw Requests Process has started");

if (thread_ctrl::running_threads("thread_service_draw.php") < 3){
    /* fetch all process requests logged */
    $requests = service_draw::fetch_process_requests();

    if ($requests){
        foreach($requests as $q){
            /* log current process */
            $logged = service_draw::log_process_routine($q['service_draw_id'], $q['date_range'], true);
            if ($logged){
                $result = service_draw::process_raffle_selection($q['service_draw_id'], $q['date_range'], $q['type'], $q['login_id']);
                if (isset($result['error'])){
                    /* update the result based on the outcome. if no data was found, delete the current draw process */
                    if (strpos($result['error'], 'winners') !== false){
                        service_draw::update_process_request($q['service_draw_id'], $q['date_range'], 0, $result['error']);
                    } else {
                        service_draw::delete_process_request($q['service_draw_id'], $q['date_range']);
                    }
                }

                if (isset($result['result'])){
                    service_draw::update_process_request($q['service_draw_id'], $q['date_range'], 1, $result['result']);
                }
                service_draw::log_process_routine($q['service_draw_id'], $q['date_range']);
            }
        }

    }

}


