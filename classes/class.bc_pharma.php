<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 9/11/2017
 * Time: 6:55 PM
 *  * ****** Service TextoPharma Service - BASE CODE: 1 *******
 *@details:
 * 1.1 What does the Service Offer?
TextoPharma makes it possible to request the price of a medecine in pharmacy by SMS.
1.2 Explain Subscriber Journey/Experience in accessing the Service
To use the service, the subscriber must activate a daily, weekly and Mothly subscription for unlimited use of the day.
To view the price of a medecine, the subscriber composes the keyword of the medecine by SMS, and then sends it to the Textopharma service number (2021).
If the medecine exists, the server responds with the result found (name, format and price). If the medecine does not exist, the server notifies that the desired medecine has not been found.
Syntax: Medecine Name # ok / send
2.2 Additional Services
? treatment Reminder: It allows to remind by SMS a user on a treatment that it must take.
Syntax: medecin Name * Recall Frequency in Hour * Day Treatment Time # ok / send
? Menstrual Cycle calculator: It allows to calculate and communicate by SMS the period of ovulation and the forecast of the next menstrual
Syntax: cycle time * first day of last Menstru # ok / send

 * @Request:
 * 1. Medicine reminder request
 * Syntax: name of medicine * reminder interval in hours * duration of the treatment in day #
 * e.g. maloxine *5*3#  : remind me for taking maloxine avery 5 hours during 3 days
 *
 * 2 - Calculation of ovulation cycle for woman
syntaxe: duration of evolution in day * date of first day for last cycle #
e.g 28*01/01/2017#  : the duration must be between 24 and 32 days
 *
 * 3- Request of medecine's price
syntax: medicine name#
e.g. doliprane 1000#
 *
 *
 */

class bc_pharma
{
    /*
     * BC_pharma has 2 types of content to be traced in tl_servicing content via the content_id reference
     * * content_id = 0 -> referring to service_message such the welcome msg intro
     * * 0 < content_id < 10 -> referring to to service error_msg
     * * content_id > 10 ->referring to REQUEST 1 -> Pharma notification | REQUEST 2 -> the ovulation calculation | REQUEST 3 -> medicine data
     *
     */

    /* initialise errors referenced field */
    public static $errors = array(
        1 => 'Erreur syntaxe ! Veuillez terminer votre saisie par un # et envoyez.',
        2 => 'Erreur syntaxe ! Veuillez soumettre le bon format : nom du m&eacute;dicament*intervalle rappel en heure *dur&eacute;e du traitement en jour#.',
        3 => 'Erreur syntaxe ! La fr&eacute;quence de rappel et la dur&eacute;e du traitement doivent &ecirc;tre des nombres.',
        4 => 'Erreur syntaxe ! Veuillez soumettre le bon format : La dur&eacute;e du cycle*premier jour du dernier menstruel#',
        5 => 'La dur&eacute;e du cycle doit &ecirc;tre comprise entre 24 et 32 jours.',
        6 => 'Erreur syntaxe ! Veuillez soumettre le bon format : nom du m&eacute;dicament#',
        7 => "Aucun m&eacute;dicament de ce nom trouv&eacute;. Corrigez l'hortographe et r&eacute;essayez."
        //7 => "Aucun m&eacute;dicament de ce nom trouv&eacute;. Corrigez l'hortographe et r&eacute;essayez."
    );
    /* start routine process  */
    public static function start_routine_process(&$current_service, $user_id, $message, $gwparams, $gwtype, $retries)
    {
        /* check if a retries is requested */
        $result = null;
        if ($retries){
            $service_data = self::failed_service_data($current_service->service_local_id, $user_id);
            if ($service_data){
                /* retrieve failed content  */
                if ($service_data['content_id']){
                   /* determine service data type references */
                    if ($service_data['content_id'] < 11){
                        //Service errors related
                        $notify_message = $current_service->name . ' - '. self::$errors[$service_data['content_id']];
                        $result = services::process_broadcast_request(array(user::get_user_msisdn($user_id), $service_data['trace_id'], $service_data['content_id'], html_entity_decode($notify_message)),
                            $user_id, $current_service, $gwparams, $gwtype);
                    } else {
                        //Service Requests
                        $notify_message = $current_service->name . self::get_archives_data_request($service_data['content_id']);
                        $result = services::process_broadcast_request(array(user::get_user_msisdn($user_id), $service_data['trace_id'], $service_data['content_id'], html_entity_decode($notify_message)),
                            $user_id, $current_service, $gwparams, $gwtype);
                    }
                } else {
                    /* retrieve the intro msg service related */
                    $related_msg = service_messages::fetch_related_messages($current_service->service_local_id, 1);
                    $notify_message = html_entity_decode($related_msg['message']);
                    if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
                        $notify_message = $current_service->name . ' - '. $notify_message;
                    }
                    $result = services::process_broadcast_request(array(user::get_user_msisdn($user_id), $service_data['trace_id'], $service_data['content_id'], $notify_message),
                        $user_id, $current_service, $gwparams, $gwtype);
                }
            }
        } else {

            /* check user request */
            $service_request = self::process_user_request($message);
            if (is_array($service_request)){
                switch($service_request['request']){
                    case 1:
                        /* check if the current user has available reminder */
                        if (!self::has_service_notify($user_id)){
                            $request_message = 'Il est temps de prendre votre médicament:  '. $service_request['data']['name']. '. Le rappel suivant sera à ';
                            $content_id = self::set_archives_data_request($user_id, $service_request['request'], $message, $request_message);
                            if ($content_id){
                                $next_reminder = self::set_service_notify($content_id, $service_request['data']['hours'], $service_request['data']['days'], $user_id);
                                if ($next_reminder){
                                    $notify_message = htmlentities($current_service->name . ' - Vos paramètres de rappel traitement ont été enregistrés avec succès. Prochain rappel à '. $next_reminder, ENT_QUOTES, 'ISO-8859-15');
                                    $result = self::sync_data_content_broadcast($content_id, html_entity_decode($notify_message), $current_service, $user_id, $gwparams, $gwtype);
                                }
                            }
                        }
                        break;
                    case 2:
                        /* record current request */
                        $request_message = 'Selon vos Paramètres renseignés, votre ovulation est prévue du '. $service_request['data']['start_period'].
                            ' au '. $service_request['data']['end_period'].'. Prochaines règles à partir du '. $service_request['data']['next_period'];
                        $content_id = self::set_archives_data_request($user_id, $service_request['request'], $message, $request_message);
                        $request_message = htmlentities($current_service->name . ' - '. $request_message, ENT_QUOTES, 'ISO-8859-15');
                        if ($content_id) $result = self::sync_data_content_broadcast($content_id, html_entity_decode($request_message), $current_service, $user_id, $gwparams, $gwtype);
                        break;
                    case 3:
                        /* record current request */
                        $data_message = self::get_data_medicine_requested($service_request['data']);
                        //db::log_db("RESULT: ". print_r($data_message, true));
                        if ($data_message){
                            $request_message = $data_message['designation']. ' : '. $data_message['prix']. ' FCFA.';
                            $content_id = self::set_archives_data_request($user_id, $service_request['request'], $message, $request_message);
                            if ($content_id) $result = self::sync_data_content_broadcast($content_id, $current_service->name . ' - '. $request_message, $current_service, $user_id, $gwparams, $gwtype);
                        } else {
                            $error_message = $current_service->name . ' - '. html_entity_decode(self::$errors[7]);
                            $result = self::sync_data_content_broadcast(7,$error_message , $current_service, $user_id, $gwparams, $gwtype);
                        }
                        break;
                }
            } else {
                /* return the error message */
                $error_message = $current_service->name . ' - '. html_entity_decode(self::$errors[$service_request]);
                $result = self::sync_data_content_broadcast($service_request, $error_message, $current_service, $user_id, $gwparams, $gwtype);
                //db::log_db("SYNC DATA RESULT: ". print_r($result));
            }
        }
        return $result;
    }

    /* fetch failed content */
    private static function failed_service_data($service_local_id, $user_id)
    {
        $result = db::sql("SELECT `tracer_id`, `content_id`FROM `tl_servicing_contents` WHERE `user_id` = '$user_id'
                  AND service_id = '$service_local_id' AND `process_state` = 3  LIMIT 1", "tl_datasync");
        if (mysqli_num_rows($result)){
            while(list($trace_id, $content_id) = mysqli_fetch_array($result)){
                return array('trace_id'=>$trace_id, 'content_id'=> (int) $content_id);
            }
        } return null;
    }

    /* sync content data and broadcast */
    private static function sync_data_content_broadcast($content_id, $message, $current_service, $user_id, $gwparams, $gwtype)
    {
        $result = null;
        $content_list = array(array($content_id, $message));
        if (service_base_code::prepare_sync_data($content_list, $current_service->service_local_id, $user_id)){
            /* start broadcast */
            $result = services::process_broadcast_request($content_list[0], $user_id, $current_service, $gwparams, $gwtype);
        } return $result;
    }


    /* determine teh user request */
    private static function process_user_request($message)
    {
        $result = 1;
       //db::log_db('User INPUT:: '. print_r(htmlentities($message, ENT_QUOTES, 'ISO-8859-15'), true));
        /* check required characters */
        if (substr($message, -1) == '#'){
            /* verify the service required request */
            if (strpos($message, '*') !== false){
                if (strpos($message, '*') !== strrpos($message, '*')){
                    //Reminder request
                    $request = substr($message, 0, -1);
                    $t = explode('*', $request);
                    if (is_array($t) && count($t) == 3){
                        if (is_numeric($t[1]) && is_numeric($t[2])){
                            $result = array(
                                'request'=>1,
                                'data'=>array('name'=>$t[0], 'hours'=>(int) $t[1], 'days'=>(int) $t[2])
                            );
                        } else {
                            $result = 3;
                        }
                    } else {
                        $result = 3;
                    }

                } else {
                    //Ovulation cycle Request
                    $r = substr($message, 0, -1);
                    $request = explode('*', $r);
                    if (is_numeric($request[0])){
                        if (strrpos($request[1], '/') !== false){
                            if ($request[0] > 23 && $request[0] < 33){
                                $d_fr = date_create_from_format('d/m/Y', $request[1]);
                                $start_period = date_format($d_fr, 'Y-m-d');;
                                $cycle_period = $request[0];
                                $result = array(
                                    'request'=>2,
                                    'data'=>array(
                                        'start_period'=>date('d/m/Y', strtotime("$start_period +9 days")),
                                        'end_period'=>date('d/m/Y', strtotime("$start_period +15 days")),
                                        'next_period'=>date('d/m/Y', strtotime("$start_period +$cycle_period days"))
                                    )
                                );
                            } else {
                                $result = 5;
                            }
                        } else {
                            $result = 4;
                        }

                    } else {
                        $result = 4;
                    }
                }
            } else {
                // Price request
                $r = substr($message, 0, -1);
                $request = explode(' ', $r);
                if (is_array($request) && count($request) > 0){
                    $result = array(
                      'request'=>3,
                        'data'=>array('name'=>strtoupper($request[0]), 'serial'=>strtoupper($request[1]))
                    );
                } else {
                    $result = 6;
                }
            }
        }
        return $result;
    }


    /* get medecine data by user submitted request */
    private static function get_data_medicine_requested($request)
    {
        /* get matched name */
        $data = null;
        $result = db::sql("SELECT designation, prix_pub FROM `bc_medicine_data` WHERE designation LIKE '". $request['name']."%';", DB_NAME);
        $names_matched = null;
        if (mysqli_num_rows($result)){
            while(list($designation, $prix) = mysqli_fetch_array($result)){
                $names_matched[] = array('designation'=>$designation, 'prix'=>$prix);
            }
        }
        /* verify serial for a match */
        if ($names_matched){
            if (count($names_matched) == 1){
                $data = $names_matched[0];
            } else {
                foreach($names_matched as $key => $list){
                    /* check if serial can match */
                    if (strrpos($list['designation'], $request['serial']) !== false){
                        $data = $names_matched[$key];
                        break;
                    }
                }
            }

            $data = is_null($data) ? $names_matched[0] : $data;
        }
        return $data;
    }

    /* get service request reference */
    private static function get_archives_data_request($content_id)
    {
        $result = db::sql("SELECT outcome_result FROM `tl_bc_pharma_service_requests` WHERE id = '$content_id';", "tl_datasync_archives");
        if (mysqli_num_rows($result)){
            while(list($outcome) = mysqli_fetch_array($result)){
                return $outcome;
            }
        } return null;
    }

    /* get service notify message */
    public static function get_service_notify_data_request($content_id)
    {
        $notify_message = self::get_archives_data_request($content_id);
        /* retrieve related data */
        $service_data = self::service_notify_available_data($content_id);
        if ($service_data){
            $next_reminder = self::get_service_next_notify_slot($service_data[0]['freq_h'], $service_data[0]['last_notify'], true);
            $notify_message = $notify_message . ' '.  date('H:i', strtotime("$next_reminder"));
        } else {
            $notify_message = substr($notify_message, 0, -31). 'Vos rappels sont termin&eacute;s.';
        }
        return $notify_message;

    }

    /* record current request */
    public static function set_archives_data_request($user_id, $request_id, $request_detail, $outcome)
    {
        $request_detail = htmlentities($request_detail, ENT_QUOTES, 'ISO-8859-15');
        $outcome = htmlentities($outcome, ENT_QUOTES, 'ISO-8859-15');
        return db::sql("INSERT INTO `tl_bc_pharma_service_requests` (user_id, request_id, request_detail, outcome_result, date_created)
        VALUES('$user_id', '$request_id', '$request_detail', '$outcome', CURRENT_TIMESTAMP);", "tl_datasync_archives");
    }

    /* record service notify */
    public static function set_service_notify($archive_request_id, $freq_h, $duration_d, $user_id)
    {
        /* calculate the flag, the number of notification */
        $flag = round( ($duration_d * 24) / $freq_h, 0, PHP_ROUND_HALF_DOWN);

        $result = db::sql("INSERT INTO `tl_bc_pharma_notify` (archive_request_id, freq_hours, duration_days, flag, user_id, last_notify)
        VALUES('$archive_request_id', '$freq_h', '$duration_d', '$flag', '$user_id', CURRENT_TIMESTAMP)", "tl_datasync_archives");
        /* return the next reminder */
        $freq_m = $freq_h * 60;
        return is_numeric($result) ? date('H:i', strtotime("+$freq_m minutes")) : false;
    }

    /* update service notify */
    public static function update_service_notify($archive_request_id, $user_id, $flag)
    {
        return db::sql("UPDATE `tl_bc_pharma_notify` SET flag = '". --$flag."', last_notify = CURRENT_TIMESTAMP WHERE  archive_request_id = '$archive_request_id' AND user_id = '$user_id'", "tl_datasync_archives");
    }

    /* check if a service notify available */
    private static function has_service_notify($user_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_bc_pharma_notify` WHERE user_id = '$user_id' AND flag <> 0 AND flag IS NOT NULL;", "tl_datasync_archives"));
    }

    /* return available service notify */
    public static function service_notify_available_data($archive_request_id=null)
    {
        $result = is_null($archive_request_id) ?
            db::sql("SELECT archive_request_id, user_id, freq_hours, duration_days, last_notify, flag FROM `tl_bc_pharma_notify` WHERE flag <> 0 AND flag IS NOT NULL;", "tl_datasync_archives"):
            db::sql("SELECT archive_request_id, user_id, freq_hours, duration_days, last_notify, flag FROM `tl_bc_pharma_notify` WHERE flag <> 0 AND flag IS NOT NULL AND archive_request_id = '$archive_request_id';", "tl_datasync_archives");
        $service_data = null;
        if (mysqli_num_rows($result)){
            while(list($service_request_id, $user_id, $freq_h, $d_days, $last_notify, $flag) = mysqli_fetch_array($result)){
                $service_data[] = array('service_request_id'=>$service_request_id, 'user_id'=>$user_id, 'freq_h'=>$freq_h, 'd_days'=>$d_days, 'last_notify'=>$last_notify, 'flag'=>$flag);
            }
        } return $service_data;
    }

    /* return service data next notify */
    public static function get_service_next_notify_slot($freq_h, $last_notify, $resolve_delay=null)
    {
        $freq_m = $freq_h * 60;
        /* add 3 minutes to resolve delay */
        if ($resolve_delay){
            $last_notify = date("Y-m-d H:i:s", strtotime("$last_notify +3 minutes"));
        }
        return date('Y-m-d H:i', strtotime("$last_notify +$freq_m minutes"));
    }




}