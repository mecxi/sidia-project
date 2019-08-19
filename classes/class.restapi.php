<?php
/**
 * @author: Mecxi Musa
 * Web service API: global settings
 *
 */

class restapi
{
    private static $httpVersion = "HTTP/1.1";

    /* set httpHeaders */
    public static function setHttpHeaders($contentType, $statusCode)
    {
        $statusMessage = self::getHttpStatusMessage($statusCode);

        header(self::$httpVersion. " ". $statusCode ." ". $statusMessage);
        header("Content-Type:". $contentType);
    }

    /* return the http error message */
    private static function getHttpStatusMessage($statusCode)
    {
        $httpStatus = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported');
        return ($httpStatus[$statusCode]) ? $httpStatus[$statusCode] : $httpStatus[500];
    }

    /* return custom errors */
    public static function custom_errors($error_code)
    {
        $result = null;
        switch($error_code){
            case 400:
                /* Bad Request - Method not all */
                $result = array('error'=>'Bad Request - Wrong method call. Please check manual');
                break;
            case 404:
                /* Request not found */
              $result = array('error'=>'Resource not found');
                break;
            case 503:
                /* Service Unavailable */
                $result = array('error'=>'Unable to process your request. Service Unavailable');
                break;
            case 405:
                /* Wrong parameters or format */
                $result = array('error'=>'Empty or incorrect JSON parameters format. Please check manual for more info');
                break;
            case 406:
                /* Incorrect parameters count */
                $result = array('error'=>'Incorrect parameter count or mistyped. Please check manual for more info');
                break;
            case 407:
                /* parameters not required */
                $result = array('error'=>'Parameters are not required for the given methods. Please check manual for more info');
                break;
            case 401:
                /* Unauthorised access */
                $result = array('message'=>'You are not authorised to access this resource');
                break;
        }
        return $result;
    }

    /* return custom errors */
    public static function custom_errors_xml($error_code)
    {
        $result = null;
        switch($error_code){
            case 401:
                /* Unauthorised access */
                $error = array('detail'=>'You are not authorised to access this resource');
                $xml = new DOMDocument();
                $InfoElement = $xml->createElement("Error");
                foreach ($error as $key => $value) {
                    $xmlNode = $xml->createElement($key,$value);
                    $InfoElement->appendChild($xmlNode);
                }
                $xml->appendChild($InfoElement);
                $result = $xml->saveXML();
                break;
        }
        return $result;
    }

    /* return custom app errors */
    public static function custom_message_app($code)
    {
        switch($code){
            case 0:
                return array('message'=>'You are not authorised to access this resource');
                break;
            case 1:
                return array('message'=>'Invalid parameters submitted. Please review documentation for the correct parameters');
                break;
            case 2:
                return array('message'=>'Invalid access-code. Your request could not be authenticated.');
                break;
            case 3:
                return array('message'=>'Unknown request type received. Please review documentation for the correct request');
                break;
        }
    }


}