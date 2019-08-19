<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 5/15/2017
 * Time: 1:00 AM
 */
require_once('config.php');

$result = db::sql("SELECT id, msisdn FROM `users`;", DB_NAME);
if (mysqli_num_rows($result)){
    while(list($user_id, $msisdn)= mysqli_fetch_array($result)){
        db::sql("UPDATE `users_stats` SET week_entries = 0 WHERE user_id = '$user_id' AND service_id = 2;", DB_NAME);
        db::sql("UPDATE `users_stats` SET month_entries = 0 WHERE user_id = '$user_id' AND service_id = 1;", DB_NAME);
    }
}

echo 'done cleaning up entries';