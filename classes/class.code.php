<?php
/**
 * @author: Mecxi Musa
 * Web API - code verification
 */

class code
{
    private static $code_id;
    private static $user_id;

    /* check the submitted code to authenticate user to the Glam Squad */
    public static function verify($phone, $code, $type=null)
    {
        /* verify the phone number */
        $user_id = user::get_user_id(strip_tags($phone));
        if (is_null($user_id)){
            return array('error'=>'Phone number could not be verified. User does not exist in our system');
        } else{
            /* verify the code is valid */
            $code_id = code::is_valid(strip_tags($code));
            if ($code_id){
                /* check if the code is linked to the user */
                self::$code_id = $code_id;
                self::$user_id = $user_id;
                if (code::is_assigned()){
                    /* update the use date */
                    if (self::used_updated()){
                        return array('result'=>true);
                    } else {
                        /*
                         *  Glam Squad App allowing more access if user subscription is still valid
                         * Check if the request if glam Squad App request
                        */
                        if (!is_null($type) && $type == 'APP'){
                            return  (user::has_current_subscription((new user(self::$user_id))->msisdn, 3) > 0) ? true : false;
                        } else {
                            return array('result'=>'The code is already been used');
                        }
                    }

                } else {
                    return array('error'=>'The code is not assigned to '. strip_tags($phone).'. Please contact customer care');
                }

            } else {
                return array('error'=>'The code entered is invalid. Please try again');
            }
        }
    }

    /* check if the code is valid */
    private static function is_valid($code)
    {
        $result = db::sql("SELECT `id` FROM `service_code` WHERE `code` = '$code'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id)=mysqli_fetch_array($result)){
                return $id;
            }
        }
        return null;
    }

    /* check if the code is linked to the user */
    private static function is_assigned()
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `servicing_level_3` WHERE user_id = '". self::$user_id."' AND code_id = '". self::$code_id."'
        AND process_state = 1 ORDER BY process_start DESC LIMIT 1;", "mtn_promo_datasync"));
    }

    /* update the code being used */
    private static function used_updated()
    {
         return db::sql("UPDATE `service_code` SET `used` = 1, `date_used` = CURRENT_TIMESTAMP WHERE `id` = '".self::$code_id."' AND `date_used` IS NULL", DB_NAME);
    }

    /* check if the code has been used */
    public static function has_checked($code_id)
    {
        $result = db::sql("SELECT date_used FROM `service_code` WHERE id = '$code_id' AND used = 1 ", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($date_used) = mysqli_fetch_array($result)){
                return $date_used;
            }
        }
        return null;
    }
}