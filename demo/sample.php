<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 9/24/2017
 * Time: 8:41 PM
 */

/* custom HTTP POST */
function curlHTTPRequest($url, $params){
    /* initialise curl resource */
    $curl = curl_init();

    /* result container, whether we are getting a feedback form url or an error */
    $result = null;

    /* encode to json format */
    $data_string = json_encode($params, JSON_FORCE_OBJECT);

    /* set resources options for GET REQUEST */
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_CONNECTTIMEOUT => 10000, //attempt a connection within 10sec
        CURLOPT_FAILONERROR => 1,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json; charset=utf-8'),
        CURLOPT_POSTFIELDS => $data_string
    ));

    /* execute curl */
    $result = curl_exec($curl);

    if(curl_error($curl)){
        $result = 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
    }
    /* close request to clear up some memory */
    curl_close($curl);

    /* return the result */
    return $result;
}

$user_params = array('Username'=>'B3873556', 'Password'=>'A!S@D#f4');
$result = curlHTTPRequest('http://web.mineworkers.co.za:8090/EBSphere.Service_deploy/api/Login', $user_params);
echo '<pre>'. print_r($result, true). '</pre>';
echo '<pre>'. print_r(json_decode($result, true), true). '</pre>';

