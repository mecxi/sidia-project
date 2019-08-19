<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 10/10/2017
 * Time: 3:55 PM
 */
require_once('../config.php');
$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );
$loggerObj->LogInfo("Starting database initialisation phase ...");

/* initialise database services | the initialisation can only be done if no service has been created yet */

$service_list = services::services_list();

if (is_null($service_list)){
    start_db_initialisation();
    $loggerObj->LogInfo("Database initialisation completed successfully!");
} else {
    $loggerObj->LogInfo("Database cannot be initialised. Services already exists.");
}


function start_db_initialisation(){
    db::sql("
ALTER TABLE bc_medicine_data AUTO_INCREMENT = 1;
ALTER TABLE tl_keyword AUTO_INCREMENT = 1;
ALTER TABLE tl_products AUTO_INCREMENT = 1;
ALTER TABLE tl_service_base_code_references AUTO_INCREMENT = 1;
ALTER TABLE tl_service_trivia AUTO_INCREMENT = 1;
ALTER TABLE tl_services_contents AUTO_INCREMENT = 1;
ALTER TABLE tl_services_cross_sell_references AUTO_INCREMENT = 1;
ALTER TABLE tl_services_draw_engine AUTO_INCREMENT = 1;
ALTER TABLE tl_services_keywords AUTO_INCREMENT = 1;
ALTER TABLE tl_services_messages AUTO_INCREMENT = 1;
ALTER TABLE tl_services_products AUTO_INCREMENT = 1;
ALTER TABLE tl_users AUTO_INCREMENT = 1;
ALTER TABLE tl_users_stats AUTO_INCREMENT = 1;
", "tl_contents");
}