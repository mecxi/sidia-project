<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 7/2/2017
 * Time: 2:53 PM
 */

require_once('../config.php');

if (gateway::$simulation === true){

    $services_list = services::services_list();
    foreach($services_list as $service){
        $current_service = new services($service['id']);
        $keywords = $current_service->keywords;
        /* get all active subs */
        $active_subs = user::fetch_active_subs($current_service->service_local_id);
        foreach($active_subs as $user){
            $user_id = $user['id'];
            $msisdn = $user['msisdn'];
            $req_service_id = services::get_sdp_service_id($current_service->service_local_id);
            $curren_user_product_id = user::service_product_selected((new user($user_id))->services_stats, $current_service->service_local_id);
            $req_product_id = services::get_sdp_product_id_by_product_id($current_service->products, $curren_user_product_id);

            $result = db::sql("INSERT INTO `tl_sync_services_requests`(msisdn, keyword, req_type, req_service, req_state, by_date, req_sent, processing, resp_time, resp_type, resp_desc, resp_service_id, resp_product_id, transID, gracePeriod, daily_count, date_created, code)
            VALUES('$msisdn', '". $keywords[0]."', 'renewal', '$current_service->service_local_id', '1', CURRENT_TIMESTAMP, '0', '2', CURRENT_TIMESTAMP, '3', 'Modification', '$req_service_id', '$req_product_id', '*136*921##', '0', '1', CURRENT_TIMESTAMP, '1')", "tl_gateway");
            if (is_numeric($result)){
                echo 'Service Billing for MSISDN :'. $msisdn. ' | '. $current_service->name. ' <b style="color:green">SUCCESS</b><br/>';
            } else {
                echo 'Service Billing for MSISDN :'. $msisdn. ' | '. $current_service->name. ' <b style="color:red">FAILED</b> - Details : '. db::$errors.'<br/>';
            }
        }

    }
} else {
    echo 'The gateway simulation is OFF. turn ON to add billing on testing';
}

