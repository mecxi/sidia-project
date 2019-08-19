<?php
/**
 * @author: Mecxi Musa
 * object for managing operations settings, systems settings
 */

class settings
{
    public static $broadcast_SET = 1;

    /***************** Set Daily Broadcast Operations *********************/

    /* get the stopping daily broadcast operations | @type: 1 requested on the portal, 0: requested in backend operation*/
    public static function get_broadcast_scheduled_set($type)
    {
        $result = db::sql("SELECT start_time, close_time, pre_close_time, fail_check_time FROM `tl_sys_settings` WHERE set_ID = '". self::$broadcast_SET."'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($start_time, $close_time, $pre_close_time, $fail_check_time) = mysqli_fetch_array($result)){
                if ($type == 0){
                    return array(
                        'start_time'=>date_format(date_create($start_time), 'i H'),
                        'close_time'=>date_format(date_create($close_time), 'H:i:s'),
                        'pre_close_time'=>date_format(date_create($pre_close_time), 'H:i:s'),
                        'fail_check_time'=>date_format(date_create($fail_check_time), 'i H')
                    );
                } else {
                    return array(
                        'start_time'=>date_format(date_create($start_time), 'H:i:s'),
                        'close_time'=>date_format(date_create($close_time), 'H:i:s')
                    );
                }
            }
        }
        return null;
    }

    /* get system entry allocation time */
    public static function get_system_services_scheduled($cross_sell_name)
    {
        $result = db::sql("SELECT start_time, close_time FROM `tl_sys_settings` WHERE set_name = '$cross_sell_name'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($start_time, $close_time) = mysqli_fetch_array($result)){
                return array(
                    'start_time'=>date_format(date_create($start_time), 'H:i:s'),
                    'close_time'=>date_format(date_create($close_time), 'H:i:s')
                );
            }
        }
        return null;
    }

}