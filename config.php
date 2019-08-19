<?php
/**
 * @author: Mecxi Musa
 * Project configuration file
 */

/* initialise required system variables */
$default_project_root = 'telecoms';
$default_port = '2500';
$database_host = 'localhost';
$database_username = 'webuser';
$database_password = '0023353';
$default_database = 'tl_contents';
$default_logo = 'logo_mosicomm.png';
$server_timezone = server_timezone();
$system_currency = 'ZAR';
$system_country_code = '27';

/* initialise SDP gateway addresses */
$sdp_prod_gateway = '41.206.4.162:8310';
$sdp_test_gateway = '41.206.4.219:8310';

/* initialise required front-end variables */
$company_name = 'Telecoms';

$app_version = '2.0';


/* determine a script is being called through a server or not */
if (isset($_SERVER['HTTP_HOST'])){
    /* Determine whether we are working on a local server or a on the live server */
    $host = substr($_SERVER['HTTP_HOST'], 0, 5);
    $local = null;
    if (in_array($host, array('local', '127.0', '192.1', '10.0'))){
        $local = true;
    }

    /* set time zone */
    date_default_timezone_set(is_null($server_timezone) ? 'Africa/Johannesburg' : $server_timezone);

    /* set project directory */
    $project_dir = $_SERVER['DOCUMENT_ROOT'];

    /* set log directory */
    $log_dir = $project_dir.'/log';
    if (!file_exists($log_dir)){
        if (!mkdir($log_dir)){
            echo ' Error creating directory: '. $log_dir;
        }
    }

    /* Determine location of files and the URL of the site:
    Allow for development on different servers. */
    if ($local){
        /* set display errors */
        error_reporting(E_ALL); ini_set('display_errors', 1);

        /* Define constants:*/
        define("LOG_FILE_PATH", $log_dir.'/');
        define("LOG_DATE", date("Y-m-d"));
        define('DB_HOST', $database_host);
        define('DB_USERNAME', $database_username);
        define('DB_PASSWORD', $database_password);
        define('DB_LOG_DIR', $log_dir);
        define('BASE_URI', '/public/');
        define('DEFAULT_PORT', ':'.$default_port);
        define('PROJECT_DIR', $project_dir);
        define('ENDPOINTS_SMS', 'http://'.$_SERVER['HTTP_HOST']. '/gateway/sms/');
        define('SDP_PROD_IP', $sdp_prod_gateway);
        define('SDP_TEST_IP', $sdp_test_gateway);

        /* front-end UI constants */
        define('COMPANY_NAME', $company_name);
        define('COMPANY_LOGO', $default_logo);
        define('CURRENCY', $system_currency);
        define('DIAL_CODE', $system_country_code);
        define('APP_VER', $app_version);

    } else {
        /* Define the constants for live server */
        define("LOG_FILE_PATH", $log_dir.'/');
        define("LOG_DATE", date("Y-m-d"));
        define('DB_HOST', $database_host);
        define('DB_USERNAME', $database_username);
        define('DB_PASSWORD', $database_password);
        define('DB_LOG_DIR', $log_dir);/* define the base URI */
        define('BASE_URI', '/public/');
        define('DEFAULT_PORT', ':'.$default_port);
        define('PROJECT_DIR', $project_dir);
        define('ENDPOINTS_SMS', 'http://'.$_SERVER['HTTP_HOST']. '/gateway/sms/');
        define('SDP_PROD_IP', $sdp_prod_gateway);
        define('SDP_TEST_IP', $sdp_test_gateway);

        /* front-end UI constants */
        define('COMPANY_NAME', $company_name);
        define('COMPANY_LOGO', $default_logo);
        define('CURRENCY', $system_currency);
        define('DIAL_CODE', $system_country_code);
        define('APP_VER', $app_version);
    }

    /* set default database */
    define('DB_NAME', $default_database);

} else {
    /* for standalone scripts calls */

    /* set project directory */
    $project_dir = '/var/www/'.$default_project_root;

    /* set time zone */
    date_default_timezone_set(is_null($server_timezone) ? 'Africa/Johannesburg' : $server_timezone);

    /* set log directory */
    $log_dir = $project_dir.'/log';
    if (!file_exists($log_dir)){
        if (!mkdir($log_dir)){
            echo ' Error creating directory: '. $log_dir;
        }
    }

    /* Define the constants:*/
    define("LOG_FILE_PATH", $log_dir.'/');
    define("LOG_DATE", date("Y-m-d"));
    define('DB_HOST', $database_host);
    define('DB_USERNAME', $database_username);
    define('DB_PASSWORD', $database_password);
    define('DB_LOG_DIR', $log_dir);
    define('DB_NAME', $default_database);
    /* define the base URI */
    define('PROJECT_DIR', $project_dir);
    define('DEFAULT_PORT', ':'.$default_port);
    define('DIAL_CODE', $system_country_code);
    define('CURRENCY', $system_currency);
    define('ENDPOINTS_SMS', 'http://192.168.8.150:2500/gateway/sms/');
    define('SDP_PROD_IP', $sdp_prod_gateway);
    define('SDP_TEST_IP', $sdp_test_gateway);
}

spl_autoload_register('my_autoloader');

/* get current timezone from the server */
function server_timezone(){
    $output = null;
    exec("ls -l /etc/localtime", $output);
    $ls = explode('/', $output[0]);
    $set_city = end($ls);
    /* get geolocation */
    $result = null;
    exec("timedatectl list-timezones | grep $set_city", $result);
    return is_array($result) ? $result[0] : null;
}

/* register CLASSPATH */
function my_autoloader($class) {
    global $project_dir;
    $file = $project_dir. '/classes/class.'.$class.'.php';;

    /* check class root folder*/
    if (file_exists($file)) {
        include $file;
        return true;
    } else {
        return false;
    }
}

