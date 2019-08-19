<?php
/**
 * User: Mecxi
 * Date: 7/7/2017
 * Time: 8:31 PM
 */

class service_messages
{

    /* fetch related open close message related to the message_type_id */
    public static function fetch_related_messages($target_service_id, $message_type_id)
    {
        $result = db::sql("SELECT message, correct_reply, incorrect_reply FROM `tl_services_messages` WHERE target_service_id = '$target_service_id' AND message_type_id = '$message_type_id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($message, $correct, $incorrect) = mysqli_fetch_array($result)){
                return array('message'=>$message, 'correct'=>$correct, 'incorrect'=>$incorrect);
            }
        } return null;
    }

    /* return all cross_sell_services for the given service */
    public static function get_cross_sell_services($service_local_id, $service_names=null)
    {
        $result = db::sql("SELECT cross_sell_service_id FROM `tl_services_cross_sell_references` WHERE service_local_id = '$service_local_id';", DB_NAME);
        $service_ids = null;
        $service_names_list = null;
        if (mysqli_num_rows($result)){
            while(list($service_list)=mysqli_fetch_array($result)){
                $service_ids[] = (int) $service_list;
            }
            /* get services names if required */
            if ($service_names){
                foreach($service_ids as $service_id){
                    $service_names_list[] = (new services($service_id))->name;
                }
            }
        }

        return is_null($service_names) ? $service_ids : $service_names_list;
    }

    /* return the message type_id */
    public static function message_type_id($message_type)
    {
        $result = db::sql("SELECT id FROM `tl_message_type` WHERE type = '$message_type';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
                return (int) $id;
            }
        } return null;
    }

    /* update current service message */
    public static function update_service_message($message_type_id, $target_service_id,$data)
    {
        /* for cross_sell type, incorrect and correct will be modified */
        $data['message'] = (strpos($data['message'], "'") !== false) ? str_replace("'","\'", $data['message']) : $data['message'];
        $data['incorrect'] = (strpos($data['incorrect'], "'") !== false) ? str_replace("'","\'", $data['incorrect']) : $data['incorrect'];
        $data['correct'] = (strpos($data['correct'], "'") !== false) ? str_replace("'","\'", $data['correct']) : $data['correct'];

        if ($message_type_id == 7){
            return db::sql("UPDATE `tl_services_messages` SET message = '".$data['message']."', incorrect_reply = '".$data['incorrect']."', correct_reply = '".$data['correct']."'
                  WHERE message_type_id = '$message_type_id' AND target_service_id = '$target_service_id';", DB_NAME);
        } else {
            return db::sql("UPDATE `tl_services_messages` SET message = '".$data['message']."' WHERE message_type_id = '$message_type_id' AND target_service_id = '$target_service_id';", DB_NAME);
        }
    }

    /* get last content date */
    public static function last_content_date_created($service_local_id)
    {
        if ((new services($service_local_id))->service_type == 1){
            $result = db::sql("SELECT date_created FROM `tl_services_contents` WHERE service_id = '$service_local_id' ORDER BY date_created DESC LIMIT 1;", DB_NAME);
            if (mysqli_num_rows($result)){
                while(list($date_created) = mysqli_fetch_array($result)){
                    return $date_created;
                }
            } return null;
        } else {
            $result = db::sql("SELECT date_created FROM `tl_service_trivia` WHERE service_id = '$service_local_id' ORDER BY date_created DESC LIMIT 1;", DB_NAME);
            if (mysqli_num_rows($result)){
                while(list($date_created) = mysqli_fetch_array($result)){
                    return $date_created;
                }
            } return null;
        }
    }

    /* insert a service message */
    public static function create_service_message($message, $message_type_id, $target_service_id, $correct=null, $incorrect=null)
    {
        return db::sql("INSERT INTO `tl_services_messages` (message, correct_reply, incorrect_reply, message_type_id, target_service_id)
                VALUES('$message', '$correct', '$incorrect', '$message_type_id', '$target_service_id') ;", DB_NAME);
    }

    /* insert cross_sell services references */
    public static function create_crossell_service_references($local_service_id, $cross_service_id)
    {
        return db::sql("INSERT INTO `tl_services_cross_sell_references` (service_local_id, cross_sell_service_id) VALUES('$local_service_id', '$cross_service_id');", DB_NAME);
    }



}