<?php
/**
 * @author: Mecxi Musa
 * Web API - notification  tracker
 */

class notify
{
    /* check if related service been notified */
    public static function has_record($user_id, $trace_id, $related_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `sys_notify` WHERE user_id = '$user_id' AND trace_id = '$trace_id' AND related_id = '$related_id'", DB_NAME));
    }

    /* record current notify */
    public static function set_record($user_id, $trace_id, $related_id)
    {
        $date = date('Y-m-d');
        return db::sql("INSERT INTO `sys_notify` (user_id, trace_id, related_id, notify_date) VALUES('$user_id', '$trace_id', '$related_id', CURRENT_TIMESTAMP)", DB_NAME);
    }
}