<?php
/**
 * @author: Mecxi Musa
 * Web API - Login
 */

class login
{
    private static $token;
    private static $trace_id;
    public $fullname;
    public $email;
    public $phone;
    public $role;

    public function __construct($id=null)
    {
        if ($id){
            $result = db::sql("SELECT `name`, surname, email, phone_no, role FROM `tl_sys_login` WHERE `id` = '$id'", DB_NAME);
            if (mysqli_num_rows($result)){
                while(list($name, $surname, $email, $phone, $role)=mysqli_fetch_array($result)){
                    $this->fullname = $name . ' '. $surname;
                    $this->email = $email;
                    $this->phone = $phone;
                    $this->role = $role;
                }
            }
        }
    }

    /* get loginID */
    public static function get_login_id($msisdn)
    {
        $result = db::sql("SELECT `id` FROM `tl_sys_login` WHERE phone_no = '$msisdn'", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($login_id) = mysqli_fetch_array($result)){
                return $login_id;
            }
        }
        return null;
    }

    /* check login request - return id, username, role - error message on fail */
    public static function verify_request($username, $password, $remote_address)
    {
        /* check user data for unwanted chars */
        $pre_name = strip_tags($username);
        $pre_name = str_replace('+', '', $pre_name);
        if (is_numeric($pre_name)){
            /* accept phone number format starting with +27, 27, 0 */
            $pre_name = (strpos($pre_name, '0') == 0)? DIAL_CODE. substr($pre_name, 1): $pre_name;
        }
        //db::log_db('Phone number:'. $pre_name);
        $user_details = null;


        /* initialise prepares values */
        db::$prepare_param_values = array(&$pre_name);
        /* result whether username is a phone number or not */
        $result = (!is_numeric($username)) ?
            db::sql("SELECT id, `name`, surname, `password`, phone_no, role, date_created FROM `tl_sys_login` WHERE email = ?", DB_NAME) :
            db::sql("SELECT id, `name`, surname, `password`, phone_no, role, date_created FROM `tl_sys_login` WHERE phone_no = ?", DB_NAME);

        if (mysqli_num_rows($result)){

            while(list($id, $name, $surname, $saved_password, $phone, $role, $date_created)=mysqli_fetch_array($result)){
                /* check password match */
                if (self::resolve_has_equals($saved_password, crypt($password, $saved_password))){
                    /* record user session start */
                    $session_start = self::record_session_start($id, $remote_address);
                    if ($session_start === true || $session_start === 0){
                        /* password has been verified, send user details */
                        $user_details = array('id'=>$id, 'name'=>$name, 'surname'=>$surname, 'phone'=>$phone, 'role'=>$role, 'date_created'=>date_format(date_create($date_created), 'd M . Y'));
                    } else {
                        /* fail to create a session */
                        if ($session_start === false){
                            $user_details = array('error'=>'Error processing your request. Service is unavailable. Please try login later');
                        } else {
                            /* there's a open session from a different ip address, force user to close all session first */
                            $user_details = array('error'=>'Error processing your request. Multiple session is not allowed. You currently have an open session from '. $session_start.'. Please close all sessions in order to login', 'loginID'=>$id);
                        }
                    }
                } else {
                    /* wrong password */
                    $user_details = array('error'=>'Your password is incorrect. Please try again or use option forget password below');
                }
            }
//            /* check if the user is currently subscribed to a service and active */
//            //$active_phone = self::is_still_active_subscriber($pre_name);
//            if (!is_null($active_phone) && ($active_phone == $pre_name)){
//                while(list($id, $name, $surname, $saved_password, $phone, $role, $date_created)=mysqli_fetch_array($result)){
//                    /* check password match */
//                    if (self::resolve_has_equals($saved_password, crypt($password, $saved_password))){
//                        /* record user session start */
//                        $session_start = self::record_session_start($id, $remote_address);
//                        if ($session_start === true || $session_start === 0){
//                            /* password has been verified, send user details */
//                            $user_details = array('id'=>$id, 'name'=>$name, 'surname'=>$surname, 'phone'=>$phone, 'role'=>$role, 'date_created'=>date_format(date_create($date_created), 'd M . Y'));
//                        } else {
//                            /* fail to create a session */
//                            if ($session_start === false){
//                                $user_details = array('error'=>'Error processing your request. Service is unavailable. Please try login later');
//                            } else {
//                                /* there's a open session from a different ip address, force user to close all session first */
//                                $user_details = array('error'=>'Error processing your request. Multiple session is not allowed. You currently have an open session from '. $session_start.'. Please close all sessions in order to login', 'loginID'=>$id);
//                            }
//                        }
//                    } else {
//                        /* wrong password */
//                        $user_details = array('error'=>'Your password is incorrect. Please try again or use option forget password below');
//                    }
//                }
//            } else if (!is_null($active_phone) && ($active_phone !== $pre_name)) {
//                    $result_extra = db::sql("SELECT id, `name`, surname, `password`, phone_no, role, date_created FROM `tl_sys_login` WHERE phone_no = '$active_phone'", DB_NAME);
//                    if (mysqli_num_rows($result_extra)){
//                        while(list($id, $name, $surname, $saved_password, $phone, $role, $date_created)=mysqli_fetch_array($result_extra)){
//                            /* check password match */
//                            if (self::resolve_has_equals($saved_password, crypt($password, $saved_password))){
//                                /* record user session start */
//                                $session_start = self::record_session_start($id, $remote_address);
//                                if ($session_start === true || $session_start === 0){
//                                    /* password has been verified, send user details */
//                                    $user_details = array('id'=>$id, 'name'=>$name, 'surname'=>$surname, 'phone'=>$phone, 'role'=>$role, 'date_created'=>date_format(date_create($date_created), 'd M . Y'));
//                                } else {
//                                    /* fail to create a session */
//                                    if ($session_start === false){
//                                        $user_details = array('error'=>'Error processing your request. Service is unavailable. Please try login later');
//                                    } else {
//                                        /* there's a open session from a different ip address, force user to close all session first */
//                                        $user_details = array('error'=>'Error processing your request. Multiple session is not allowed. You currently have an open session from '. $session_start.'. Please close all sessions in order to login', 'loginID'=>$id);
//                                    }
//                                }
//                            } else {
//                                /* wrong password */
//                                $user_details = array('error'=>'Your password is incorrect. Please try again or use option forget password below');
//                            }
//                        }
//                    }
//            } else {
//                $user_details = array('error'=>'Error processing your request. You have no active subscription. Please re-subscribe to the service by dialing *136*921#');
//            }
        } else {
            if (is_numeric($username)){
                $user_details = array('error'=>'Error processing your request. We could not verify your phone number. Please register below');
            } else {
                $user_details = array('error'=>'Error processing your request. We could not verify your username. Please register below');
            }
        }

        return $user_details;
    }

    /* check if the user is currently subscribed to one of the services and active */
    public static function is_still_active_subscriber($username)
    {
        $services = array(1,2,3);
        /* check if any phone is active */
        $is_active_msisdn = null;
        if (!is_numeric($username)){
            $all_msisdn = self::get_registered_phone($username);
            foreach($services as $service_id){
                foreach($all_msisdn as $msisdn){
                    if (user::has_current_subscription($msisdn, $service_id)){
                        $is_active_msisdn = $msisdn;
                        break;
                    }
                }
                if ($is_active_msisdn){
                    break;
                }
            }
        } else {
            foreach($services as $service_id){
                if (user::has_current_subscription($username, $service_id)){
                    $is_active_msisdn = $username;
                    break;
                }
            }
        }
        return $is_active_msisdn;
    }

    private static function get_registered_phone($email)
    {
        $result = db::sql("SELECT phone_no FROM `tl_sys_login` WHERE email = '$email'", DB_NAME);
        $registered_phone = null;
        if (mysqli_num_rows($result)){
            while(list($msisdn)=mysqli_fetch_array($result)){
                $registered_phone[] = $msisdn;
            }
        }
        return $registered_phone;
    }

    /* resolve undefined function hash_equals() in password verification call */
    private static function resolve_has_equals($str1, $str2)
    {
        if (strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $ret |= ord($res[$i]);
            }
            return !$ret;
        }
    }

    /* record current user session start */
    private static function record_session_start($login_id, $remote_address)
    {
        $date = date('Y-m-d');
        /* check if there's an open session */
        $process_result = null;
        $result = db::sql("SELECT `remote_address` FROM `tl_sys_login_history` WHERE `start_session` LIKE '$date%' AND `login_id` = '$login_id' AND `end_session` IS NULL", DB_NAME);
        if (mysqli_num_rows($result)){
            /* for security reason, force user to login from the same computer if necessary or close current open session */
            while(list($rmt_address) = mysqli_fetch_array($result)){
                if ($remote_address == $rmt_address){
                    $process_result = true;
                } else {
                    $process_result = $rmt_address;
                }
            }
        } else {
            $process_result = db::sql("INSERT INTO `tl_sys_login_history` (`login_id`, `start_session`, `remote_address`) VALUES('$login_id', CURRENT_TIMESTAMP, '$remote_address')", DB_NAME);
        }
        return $process_result;
    }

    /* user logging out, close the session and record */
    public static function record_session_end($login_id, $remote_address, $priority=null)
    {
        /* close all session open on high priority */
        $result = (is_null($priority))? db::sql("UPDATE `tl_sys_login_history` SET `end_session` = CURRENT_TIMESTAMP WHERE `login_id` = '$login_id' AND remote_address = '$remote_address' ORDER BY `start_session` DESC LIMIT 1", DB_NAME):
            db::sql("UPDATE `tl_sys_login_history` SET `end_session` = CURRENT_TIMESTAMP WHERE `login_id` = '$login_id'  ORDER BY `start_session` DESC LIMIT 1", DB_NAME);
        if ($result){
           return array('result'=> true);
        } else {
            return array('error'=>true);
        }
    }

    public static function register($fullname, $email, $phone, $password)
    {
        /* check if user is a subscriber */
        $p_phone = strip_tags($phone);
        $p_phone = str_replace('+', '', $p_phone);
        /* accept phone number format starting with +27, 27, 0 */
        $p_phone = (strpos($p_phone, '0') == 0)? DIAL_CODE. substr($p_phone, 1): $p_phone;

        $services = services::services_list();
        $has_subscription = null;
        $current_user = new user(user::get_user_id($p_phone));
        foreach($services as $service){
            if (user::has_current_subscription($current_user->services_stats, $service['id'])){
                $has_subscription = true;
                break;
            } else {
                $has_subscription = false;
            }
        }
        $result = null;

        if (is_null($has_subscription)){
            $result = array('error'=>'Error processing your request. No subscription found with the phone number provided.');
        } else {
            if ($has_subscription === false){
                $result = array('error'=>'Error processing your request. You have no active subscription. Please re-subscribe to the service by dialing *136#');
            } else {
                /* check if already register */
                if (self::has_registered($p_phone)){
                    $result = array('error'=>'Error processing your request. Phone number provided has already been registered. Please choose I already have a membership or try forgot password in the login page');
                } else {
                    /* check user data for unwanted chars */
                    $p_fullname = strip_tags($fullname);
                    $name = '';
                    $surname = '';
                    if (strpos($p_fullname, ' ') !== false){
                        $p_fullname = explode(' ', $p_fullname);
                        $name = $p_fullname[0];
                        $surname = $p_fullname[1];
                    } else {
                        $name = $p_fullname;
                    }
                    /* encrypt password */
                    $hashed_password = crypt($password);
                    /* generate unique token, trace_id */
                    self::$token = service_level_3::generate_random_code();
                    self::$trace_id = thread_ctrl::get_unique_trace_id();

                    /* load current request into cache and send a unique token to verify the phone number owner */
                    if (is_int(self::insert_token_auth($name, $surname, $email,$hashed_password, $p_phone))){
                        if (self::send_token_auth($p_phone)){
                            $result = array('result'=>'For security reason, we have sent a token to the phone number provided. Please enter the code to register');
                        } else {
                            $result = array('error'=>'An error has occurred processing your registration. Enable to generate token. Please try again later.');
                            /* delete the current token saved in db */
                            self::delete_token_auth(self::$token);
                        }
                    } else {
                        /* delete current phone number in cache register */
                        self::clean_register_cache($p_phone);
                        $result = array('error'=>'An internal error has occurred processing your registration. Please try again one more time');
                    }
                }
            }
        }

        return $result;
    }

    /* verify if already registered */
    public static function has_registered($phone)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_sys_login` WHERE phone_no = '$phone'", DB_NAME));
    }

    /* send token for authentication */
    private static function send_token_auth($phone_number)
    {
        /* data_list contains an array of: [0] => msisdn, [1] => tracer_id, [2] => message */
        $message = 'Please verify your registration on our portal by entering token:'. self::$token.' If you did not request a registration, please ignore';
        $data_list = array($phone_number, self::$trace_id, $message);
        /* create a json object */
        $jsonData = json_encode($data_list, true);
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $jsonData
            )
        );
        $context  = stream_context_create($opts);
        return file_get_contents('http://localhost/mtnpromo/gateway/sms/?mode=token', false, $context);
    }

    /* delete current token */
    private static function delete_token_auth($token)
    {
        return db::sql("DELETE FROM `tl_sys_cache_register` WHERE token = '$token'", DB_NAME);
    }

    /* clean registration cache */
    public static function clean_register_cache($phone=null)
    {
        return (is_null($phone))? db::sql("DELETE FROM `tl_sys_cache_register`", DB_NAME)
            : db::sql("DELETE FROM `tl_sys_cache_register` WHERE phone_no = '$phone'", DB_NAME);
    }

    /* insert current token */
    private static function insert_token_auth($name, $surname, $email, $hashed_password, $p_phone)
    {
        return db::sql("INSERT INTO `tl_sys_cache_register` (`name`, surname, email, `password`, phone_no, by_date, token, trace_id)
                    VALUES('$name', '$surname', '$email', '$hashed_password', '$p_phone', CURRENT_TIMESTAMP, '". self::$token."', '".self::$trace_id."')", DB_NAME);
    }
    /* verify token */
    public static function verify_token($phone, $token)
    {
        $p_phone = (strpos($phone, '0') == 0)? DIAL_CODE. substr($phone, 1): $phone;

        $result = db::sql("SELECT `name`, surname, email, `password`, phone_no FROM `tl_sys_cache_register` WHERE phone_no = '$p_phone' AND token = '$token'", DB_NAME);
        $process_result = array('error'=>'Token entered could not be verified. Please retry or use the phone number you subscribe to the service');
        if (mysqli_num_rows($result)){
            while(list($name, $surname, $email, $password, $phone) = mysqli_fetch_array($result)){
                $reset_requested = ($name == '') ? 1 : null;
                if (self::proceed_registration($name, $surname, $email, $password, $phone, $reset_requested)){
                    $process_result = (is_null($reset_requested)) ? array('result'=>'You have registered successfully. Please wait ...'):
                        array('result'=>'Your password has been reset successfully. Please wait ...');
                    self::delete_token_auth($token);
                } else {
                    self::delete_token_auth($token);
                        $process_result = (is_null($reset_requested)) ? array('error'=>'An error occurred while processing your registration. Please try again to register later'):
                            array('error'=>'An error occurred while processing your request. Please try again to register later');

                }
            }
        }

        return $process_result;
    }

    /* process new registration */
    private static function proceed_registration($name, $surname, $email, $password, $phone, $reset=null)
    {
        $result = (is_null($reset))? db::sql("INSERT INTO `tl_sys_login` (`name`, surname, email, `password`, phone_no, role, date_created)
        VALUES('$name', '$surname', '$email', '$password', '$phone', 3, CURRENT_TIMESTAMP)", DB_NAME):
            db::sql("UPDATE `tl_sys_login` SET `password` = '$password' WHERE `phone_no` = '$phone'", DB_NAME);
        if (is_int($result) && $result != 0){
            return true;
        } else {
            return false;
        }
    }

    /* reset your password */
    public static function reset($phone, $password)
    {
        /* accept phone number format starting with +27, 27, 0 */
        $phone_clean = str_replace('+','', $phone);
        $phone_clean = (strpos($phone_clean, '0') == 0)? DIAL_CODE. substr($phone_clean, 1): $phone_clean;

        $process_result = null;
        $result = db::sql("SELECT phone_no FROM `tl_sys_login` WHERE phone_no = '$phone_clean'", DB_NAME);
        if (mysqli_num_rows($result)){
            /* check if subscriber still active */
            $process_result = array('error'=>'Error processing your request. Resetting password has been disabled. Please contact System Admin');
//            /* check if subscriber still active */
//            if (self::is_still_active_subscriber($phone_clean)){
//                while(list($phone_number)=mysqli_fetch_array($result)){
//                    /* encrypt password */
//                    $hashed_password = crypt($password);
//                    /* generate unique token, trace_id */
//                    self::$token = service_level_3::generate_random_code();
//                    self::$trace_id = thread_ctrl::get_unique_trace_id();
//
//                    /* load current request into cache and send a unique token to verify the phone number owner */
//                    if (is_int(self::insert_token_auth('', '', '',$hashed_password, $phone_number))){
//                        if (self::send_token_auth($phone_number)){
//                            $process_result = array('result'=>'For security reason, we have sent a token to the phone number provided. Please enter the code to register');
//                        } else {
//                            $process_result = array('error'=>'An error has occurred processing your request. Enable to generate token. Please try again later.');
//                            /* delete the current token saved in db */
//                            self::delete_token_auth(self::$token);
//                        }
//                    } else {
//                        /* delete current phone number in cache register */
//                        self::clean_register_cache($phone_number);
//                        $result = array('error'=>'Oops we could not get that!! Please verify one more time');
//                    }
//                }
//            } else {
//                $process_result = array('error'=>'Error processing your request. You have no active subscription. Please re-subscribe to the service by dialing *136*921#');
//            }
        } else {
            $process_result = array('error'=>'Error processing your request. We could not verify your phone number. Please contact System Admin');
        }
        return $process_result;
    }

    /* return last login */
    public static function last_session_logged($login_id)
    {
        $result = db::sql("SELECT start_session FROM `tl_sys_login_history` WHERE login_id = '$login_id' ORDER BY start_session DESC LIMIT 1", DB_NAME);
        $return_result = null;
        if (mysqli_num_rows($result)){
            while(list($last_login)=mysqli_fetch_array($result)){
                $return_result = date_format(date_create($last_login), 'Y-m-d H:i');
            }
        }
        return $return_result;
    }

    /* check the login session */
    public static function has_session_started($login_id, $remote_address)
    {
        $process_result = null;
        //$current_timestamp = date('Y-m-d');
        $result = db::sql("SELECT remote_address FROM `tl_sys_login_history` WHERE login_id = '$login_id' AND end_session IS NULL ORDER BY start_session DESC LIMIT 1", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($rmt_address) = mysqli_fetch_array($result)){
                if ($remote_address == $rmt_address){
                    $process_result = true;
                } else {
                    $process_result = false;
                }
            }
        }
        return $process_result;
    }

    /* keep-alive current session or logout the user on multi session
    @return result: true - session exits false - session is already ended or close  | error: if there's no session */
    public static function keep_alive_session($login_id, $remote_address)
    {
        $process_result = array('error'=>true);
        $current_session = self::has_session_started($login_id, $remote_address);
        if ($current_session === true){
            $process_result = array('result'=>true);
        } else if ($current_session === false){
            $process_result = array('result'=>false);
        }
        return $process_result;
    }

}
