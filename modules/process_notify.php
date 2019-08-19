<?php
require_once('../config.php');
/**
 * @author: Mecxi Musa
 * @overview: Threading fails notify in the system background per request
 */
require_once('../config.php');

$loggerObj = new KLogger (LOG_FILE_PATH."BROADCAST_".LOG_DATE.".log", KLogger::DEBUG );

/* parse params */
$user_id = $argv[1];
$score = (isset($argv[2])) ? $argv[2] : null;
$trace_id = (isset($argv[3]))? $argv[3]: null;;
$trivia_id = (isset($argv[4]))? $argv[4]: null;
$sleep = (isset($argv[5]))? $argv[5]: null;
$service_local_id = (isset($argv[6]))? $argv[6]: null;
$msisdn = (isset($argv[7]))? $argv[7]: null;



/* prepage gateawy params */
$gwparams = array(
    'linkid'=>null,
    'senderCB'=>null,
    'service_local_id'=>$service_local_id
);

/* log current process has started */
services::log_notify_thread_started($trace_id);

sleep((int)$sleep);

/* get notify message / failed opening /closing message
trivia_id = 0 if user is still sending answer after already received the closing message
*/

/* initialise required variables */
$notify_message = '';
$process_request = null;

$current_service = new services($service_local_id);

if (is_null($msisdn)) {
    $loggerObj->LogInfo("Failure starting notify process queue -- no msisdn is present!!!!!");
    die();
}

$loggerObj->LogInfo("Starting notify process queue | MSISDN : $msisdn | triviaID: $trivia_id");

switch($score){
    case 'closing':
        /* send closing message related to the target service */
        $related_message = service_messages::fetch_related_messages($service_local_id, 8);
        $notify_message = $related_message['message']. ' Find out tomorrow if you one of 10 lucky winners.';
        if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
            $notify_message = $current_service->name . ': '. $notify_message;
        }
        $process_request = true;
        break;
    case 'opening':
        $notify_message = services::get_service_opening_message($current_service, $user_id, $msisdn);
        $process_request = true;
        break;
    case 'closing_points':
        /* notify users the points earned to check it out more in the portal */
        /* check user performance */
        $points = (int) user::today_points($service_local_id, $user_id);
        $average_pts = (int) ($current_service->broadcast_length * 10) / 2;
        /* allocate bonus entry to top performance */
        if ($points > $average_pts || $points == $average_pts){
            /* daily entry allocation */
            if (services::has_notify_started($service_local_id, $user_id, $trivia_id)){
                services::entry_allocation($user_id, $service_local_id, 'D');
            }
        }
        $entries = service_draw::get_service_entries($user_id, $service_local_id, 1, true);
        $entries_word = ($entries > 1) ? $entries . ' entries, ' : $entries . ' entry, ';
        $total_pts = user::totals_points($service_local_id, $user_id);
        $rate = $points == 0 ? 'Oops! Better luck next time! '.$entries_word . $total_pts.' pts total.' :
            ( ( $points < $average_pts) ? 'Good one! '. $entries_word . $total_pts.' pts total.' :
                'Congratulations! Won 1 more entry. ' . $entries_word . $total_pts.' pts total.') ;
//            $portal_link = (login::has_registered($current_user->msisdn) > 0) ? 'goo.gl/4vhI7N' : 'goo.gl/GEa9Nz';
        $related_message = service_messages::fetch_related_messages($service_local_id, 8);
        $notify_message = $related_message['message'] . ' '. $points.' pts scored! '.$rate. ' Stay tuned tomorrow for more.';
        $process_request = true;
        break;
    case 'advert':
        /* advertise other promo for non subscribed users */
        if ($service_id == 1){
            $notify_message = 'Dial 136*921# Discover Beauty Tips and be Glam and a chance to win R70,000 in monthly prizes! First 3 days FREE, R1,50/day thereafter!';
            $process_request = true;
        } else if ($service_id == 2) {
            $notify_message = 'Dial 136*921# to join the Glam Squad Trivia & learn to look and feel good, You could WIN R15,000 plus goodies, weekly! 7 days FREE week & R2/day afterwards';
            $process_request = true;
        } else {
            /* service 3 */
        }
        break;
    case 'closing_2':
        $entries = service_draw::get_service_entries($user_id, $service_id, ($service_id == 1) ? 'W': 'M', true);
        $entries_word = ($entries > 1) ? $entries . ' entries ' : $entries . ' entry ';
        $total_pts = user::totals_points($service_local_id, $user_id);

        $notify_message = ($service_id == 1) ? 'Congratulations! You have won 1 more entry into the weekly draw and could be the winner! '. $entries_word . $total_pts.' pts total. Stay tuned for more beauty tips.' :
                'Congratulations! You have won 1 more entry into the monthly draw and could be the winner! '. $entries_word .'in total. Stay tuned for more beauty tips.';

        $process_request = true;
        break;
    case 'sponsors':
        /* cross_sell sponsors */
        if ($service_id == 1){
            $notify_message = 'The Glam Squad Trivia prizes are proudly sponsored by Living Brands, The Makeup Issue, Lajawi Cafe, Hisense and Marico SA. TsCs goo.gl/6nI8mT';
            $process_request = true;
        } else if ($service_id == 2) {
            $notify_message = 'The Beauty Tips prizes are proudly sponsored by Living Brands, The Makeup Issue, Lajawi Cafe, Hisense and Marico SA. TsCs goo.gl/6nI8mT';
            $process_request = true;
        } else {
            /* full subscribed */
        }
        break;
    case 'pharma':
        /* Pharma service data notify */
        $notify_message = html_entity_decode(bc_pharma::get_service_notify_data_request($trivia_id));
        if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
            $notify_message = $current_service->name . ' - '. $notify_message;
        }
        $process_request = true;
        break;
    case 'winners':
        /* notify draw winners */
        $notify_message = (new service_draw($trivia_id))->notify;
        if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
            $notify_message = $current_service->name . ' - '. $notify_message;
        }
        $process_request = true;
        break;
    default:
        $notify_message = services::fetch_notify_related_message($service_local_id, $trivia_id, $score);
        if ( (strlen($current_service->name) + strlen($notify_message)) < 159){
            $notify_message = $current_service->name . ': '. $notify_message;
        }
        $process_request = true;
        break;
}

if ($process_request === true){
    if (process_send_notify($msisdn, $trace_id, $notify_message, $gwparams, $service_local_id, $score) === false){
        /* temporary suspended current trace_id process for the next process loop */
        services::update_suspended_notify($msisdn, 1, $service_local_id);
    }
}



/* send notification */
function process_send_notify($msisdn, $trace_id, $notify_message, $gwparams, $service_local_id, $score){
    global $loggerObj;
    /* wait a random number of sec before starting current thread */
    //sleep(rand(0, 4));

    $data_list = array($msisdn, $trace_id, null, $notify_message, $gwparams);
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
    $response = file_get_contents('http://localhost'.DEFAULT_PORT.'/gateway/sms/?service_type=content', false, $context);
    /* process | Update current broadcast operation result in datasync */
    $response = json_decode($response, true);

    if ($response['result'] == 'success'){
        if (services::prepare_datasync_notify($data_list, null, true, null, null, $service_local_id)){
            $loggerObj->LogInfo($score == 'opening' ? "Opening Message Sent successfully ": "Notify Sent successfully !");
        }
        return true;
    } else {
        $loggerObj->LogInfo('tracer_id :'. $trace_id. ' | '. $response['message']);
        /* log current process has ended */
        services::log_notify_thread_started($trace_id, true);
        return false;
    }
}

/* free up database system memory */
db::close();









