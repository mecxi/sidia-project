<?php
/**
 * User: Mecxi
 * Date: 9/9/2017
 * Time: 6:09 PM
 * exclusive service carries an allocated subroutine to handle the service requests.
 */



class service_base_code
{
    private static $allocated = 2; // nb of allocated sub routines

    /* check if current service is exclusive to carry base code task */
    public static function get_service_base_code_id($service_local_id)
    {
        $result = db::sql("SELECT id FROM `tl_service_base_code_references` WHERE service_local_id = '$service_local_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
                return (int) $id;
            }
        } return null;
    }

    public static function get_service_local_id_base_code($service_base_code_id)
    {
        $result = db::sql("SELECT service_local_id FROM `tl_service_base_code_references` WHERE  id = '$service_base_code_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
                return (int) $id;
            }
        } return null;
    }

    /* check allocated sub routines */
    public static function has_routines_available()
    {
        $base_code_references = mysqli_num_rows(db::sql("SELECT * FROM `tl_service_base_code_references`;", DB_NAME));
        if ($base_code_references < self::$allocated){
            return true;
        } return false;
    }

    /* add a new service base code */
    public static function create_references($service_local_id, $comments)
    {
        return db::sql("INSERT INTO `tl_service_base_code_references` (service_local_id, comments) VALUES('$service_local_id', '$comments');", DB_NAME);
    }

    /* start base_code process */
    public static function start_exclusive_process($current_service, $user_id, $message, $gwparams, $gwtype, $retries=null)
    {
        $result = null;
        $service_base_code_id = self::get_service_base_code_id($current_service->service_local_id);

        if ($service_base_code_id){
            //Upon susbcription
            if ($message == 'subscribe'){
                /* for exclusive service type, check if a welcome is required upon subscription */
                if ($current_service->opening_message){
                    $content_list = array();
                    $related_msg = service_messages::fetch_related_messages($current_service->service_local_id, 1);
                    $notify_message = $related_msg['message'];

                    if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
                        $notify_message = $current_service->name . ' - '. $notify_message;
                    }
                    $content_list[] = array(0, $notify_message);
                    if (self::prepare_sync_data($content_list, $current_service->service_local_id, $user_id)){
                        /* start broadcast */
                        $result = services::process_broadcast_request($content_list[0], $user_id, $current_service, $gwparams, $gwtype);
                    }
                }
            } else {
                //Related service request process
                switch($service_base_code_id){
                    case 1:
                        $result = bc_pharma::start_routine_process($current_service, $user_id, urldecode($message), $gwparams, $gwtype, $retries);
                        break;
                    case 2:
                        $result = bc_pharma::start_routine_process($current_service, $user_id, urldecode($message), $gwparams, $gwtype, $retries);
                        break;
                }
            }
        }
        return $result;
    }

    /* prepare content in the tl_servicing_content queue to be traced */
    public static function prepare_sync_data(&$data_list, $service_local_id, $user_id)
    {
        if (services::start_sync($data_list, $service_local_id, $user_id)){
            /* fetch tracer_id after data_sync creation */
            for($i = 0 ; $i < count($data_list); ++$i){
                $tracer_id = services::fetch_tracer_id($user_id, $service_local_id, $data_list[$i][0]);
                /* push current tracer_id and msisdn into current data list */
                array_unshift($data_list[$i], $tracer_id);
                array_unshift($data_list[$i], user::get_user_msisdn($user_id));
            }
            return true;
        }
        return null;
    }





}