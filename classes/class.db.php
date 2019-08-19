<?php
/*
 * @class_name : db{}
 * @version: 1.0
 * @author : Mecxi Musa
 * @details: multi database operations handler, including query to a remote server
 * @HOW-TO examples:

   // single or normal query
   select query | @return mysqli_result
   update delete query | @return the number of affected rows
e.g.
$result = db::sql("SELECT * FROM `customer`", DB_NAME);
if (mysqli_num_rows($result)){
    while(list($surname) = mysqli_fetch_array($result)){
        echo 'SURNAME : '. $surname . '<br>';
    }
}

    // multiple query
    select queries | @return connection_link
$link = db::sql("SELECT * FROM `customer`; SELECT * FROM `city`; ", DB_NAME);
do {
    if ($result = mysqli_store_result($link)){
        while($row = mysqli_fetch_row($result)){
            echo $row[0] . ' -  '. $row[1] . '<br>';
        }
        mysqli_free_result($result);
    }
    if (!mysqli_more_results($link)){
        break;
    }
}while(mysqli_next_result($link));


    // prepare statement query  |
    please note that mysql native driver needed to get the result in procedural or object mysqli_stmt_get_result
    remove mysqli and install mysqlnd for the prepare statement to work
    update or delete query | @return a number of affected rows
    insert  query | @return the last inserted id; returned only if the primary key exists in target table or else 0 is returned
    select query | @return mysqli_result
    @require prepare_param_values fields | an array of referenced variables

    @select example:
$name = 'David';
$id = 1;
$name = strip_tags($name);
db::$prepare_param_values = array(&$name, &$id);
$result = db::sql("SELECT * FROM `customer` WHERE `name` = ? AND `id` = ?", DB_NAME);
$result_list = null;
if (mysqli_num_rows($result)){
    while($row = mysqli_fetch_array($result)){
        $result_list[] = $row['name'];
    }
}

    @update examples
$new_name = 'Andrews';
db::$prepare_param_values = array(&$new_name, &$name, &$id);
$affected_rows = multi_db::sql("UPDATE `customer` SET `name` = ? WHERE `name` = ? AND `id` = ?", DB_NAME);

multi_db::close();
 */

class db
{
    private static $server = DB_HOST;
    private static $username = DB_USERNAME;
    private static $password = DB_PASSWORD;
    private static $dbname;
    private static $selected_db;

    private static $statement_result;
    public static $prepare_param_values;
    private static $conn;
    private static $result_set;
    private static $retry_count = 0;
    private static $dev_contact = 'mecximusa@gmail.com';
    public static $errors;

    public function __construct($server=null, $username=null, $password=null)
    {
        /* a database log directory is required */
        if (!defined('DB_LOG_DIR')){
            echo "<i style='color:red'> An error occured. Please define a constant named 'DB_LOG', the location to save database related errors
                    before instantiating or calling a method of this class | " . __CLASS__ . '{}';
            die();
        }

        /* target server connection if needed */
        if ($server){
            self::$server = $server;
        }

        if ($username){
            self::$username = $username;
        } else {
            if (!defined('DB_USERNAME')){
                echo "<i style='color:red'> An error occurred. Please define a constant 'DB_USERNAME', the database username
                    before instantiating or calling a method of this class | " . __CLASS__ . '{}';
                die();
            }
        }

        if ($password){
            self::$password = $password;
        } else {
            if (!defined('DB_PASSWORD')){
                echo "<i style='color:red'> An error occurred. Please define a constant 'DB_PASSWORD', the database password
                    before instantiating or calling a method of this class | " . __CLASS__ . '{}';
                die();
            }
        }
    }

    private static function initiateDatabaseConnection($query, $dbname, $skip_query=null)
    {
        /* initialise db name*/
        self::$dbname = $dbname;

        /* initialise connection object */
        self::$conn = mysqli_connect(self::$server, self::$username, self::$password, self::$dbname);
        /* resolve |  json_encode(): Invalid UTF-8 sequence in argument */
        if (is_object(self::$conn)){
            mysqli_set_charset(self::$conn, "utf8") ;
        }

        /* assign current db selected */
        self::$selected_db = $dbname;

        /* check the connection */
        if(!self::$conn){
            /* handle current connection error */
            self::sql_connect_handler(mysqli_connect_errno(), mysqli_connect_error(). ' | Server : '. self::$server, $query);
            //echo ('['.date("Y-m-d H:i:s").'] <span style="color:red">Can\'t establish a database connection. Please review the error below: <br>'. mysqli_connect_errno() . ' | '. mysqli_connect_error().'</span>' ."\n");
        } else {
            //self::log_db('MYSQL :: Connecting to '. self::$selected_db .' - '. self::$server.' successfully established');
            /* retry the query | check if a skip query has been initiated */
            if (is_bool($skip_query)){
                self::sql($query, $dbname, $skip_query);
            } else {
                self::sql($query, $dbname);
            }
        }
    }

    /* db connection error handler */
    private static function sql_connect_handler($error_no, $error_message, $query)
    {
        /* mysqli_connect_error code
        SERVER COMMON ERROR
         Error: 1040 SQLSTATE: 08004 |  Too many connections
         Error: 1045 | Access denied for user | no connection link exist
         Error: 1129 SQLSTATE: HY000 (ER_HOST_IS_BLOCKED) Message: Host '%s' is blocked because of many connection errors; unblock with 'mysqladmin flush-hosts'
         Error: 1152 SQLSTATE: 08S01 (ER_ABORTING_CONNECTION) Message: Aborted connection %ld to db: '%s' user: '%s' (%s)\
         Error: 1154 SQLSTATE: 08S01 (ER_NET_READ_ERROR_FROM_PIPE) Message: Got a read error from the connection pipe\
         Error: 1184 SQLSTATE: 08S01 (ER_NEW_ABORTING_CONNECTION) Message: Aborted connection %ld to db: '%s' user: '%s' host: '%s' (%s)
         Error: 1203 SQLSTATE: 42000 (ER_TOO_MANY_USER_CONNECTIONS) Message: User %s already has more than 'max_user_connections' active connections
         Error: 1408 SQLSTATE: HY000 (ER_STARTUP) Message: %s: ready for connections. Version: '%s' socket: '%s' port: %d %s
         *
         *
       CLIENT SIDE ERROR
        Error: 2002 (CR_CONNECTION_ERROR) Message: Can't connect to local MySQL server through socket '%s' (%d)
        Error: 2003 (CR_CONN_HOST_ERROR) Message: Can't connect to MySQL server on '%s' (%d)
        Error: 2004 (CR_IPSOCK_ERROR) Message: Can't create TCP/IP socket (%d)
        Error: 2005 (CR_UNKNOWN_HOST) Message: Unknown MySQL server host '%s' (%d)
        Error: 2006 (CR_SERVER_GONE_ERROR) Message: MySQL server has gone away
        Error: 2008 (CR_OUT_OF_MEMORY) Message: MySQL client ran out of memory
        Error: 2009 (CR_WRONG_HOST_INFO) Message: Wrong host info
        Error: 2013 (CR_SERVER_LOST) Message: Lost connection to MySQL server during query
        Error: 2017 (CR_NAMEDPIPEOPEN_ERROR) Message: Can't open named pipe to host: %s pipe: %s (%lu)
        Error: 2026 (CR_SSL_CONNECTION_ERROR) Message: SSL connection error: %s
        Error: 2032 (CR_DATA_TRUNCATED) Message: Data truncated
        Error: 2052 (CR_NO_STMT_METADATA) Message: Prepared statement contains no metadata
        Error: 2053 (CR_NO_RESULT_SET) Message: Attempt to read a row while there is no result set associated with the statement
        Error: 2055 (CR_SERVER_LOST_EXTENDED) Message: Lost connection to MySQL server at '%s', system error: %d
        */

        $error_code = array(1040, 1045, 1049, 1129, 1152, 1154, 1203, 2002, 2003, 2004, 2013, 2017, 2055);
        $return_message = null;
        $is_network_error = null;
        //$is_unique_key_related = null;
        /* check error related */
        if (in_array($error_no, $error_code)){
            /* check if too many connections is above 1000 */
            if ($error_no == 1040){
                /* get current allowed max connection */
                $max_conns = db::sql_max_connections();
                if (is_null($max_conns)){
                    $is_network_error = true;
                    $return_message = '[CONNECTION ERROR] '.$error_message. ':: [ERROR CODE]'. $error_no;
                } else {
                    if ($max_conns != 1000){
                        /* set max connections to 1000 and request a retry */
                        db::sql_set_max_connections();
                        $is_network_error = true;
                        $return_message = '[CONNECTION ERROR] '.$error_message. ':: [ERROR CODE]'. $error_no;
                    } else {
                        $is_network_error = true;
                        $return_message = '[CONNECTION ERROR] '.$error_message. ':: [ERROR CODE]'. $error_no;
                    }
                }

            } else {
                $is_network_error = true;
                $return_message = '[CONNECTION ERROR] '.$error_message. ':: [ERROR CODE]'. $error_no;
            }
        } else {
            /* possibly a query related error */
            $return_message = '[QUERY ERROR] '.$error_message. ':: [ERROR CODE]'. $error_no;
        }

        /* log this attempt and try a reconnection after 5 min */
        $logDir = DB_LOG_DIR;

        /* for connection related */
        if ($is_network_error){

            if (!file_exists($logDir)){
                if (!mkdir($logDir, 0744)){
                    self::log_db(' Error creating directory: '. $logDir);
                }
            }
            /* update log file */
            file_put_contents($logDir .'/'. date("Y-m-d"). '.log', date("H:i:s") . ' '. $return_message ."\n", FILE_APPEND);
            /* pause for 5min for a retry */
            sleep(30);
            /* update count */
            self::$retry_count = self::$retry_count + 1;
            /* email dev on 4 attempts */
            if (self::$retry_count > 5){
                $headers = 'From: error@mobi-apps.technology' . "\r\n" .
                    'Reply-To: mecximusa@gmail.com' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
                /* notify the dev 3 times only */
                if (self::$retry_count < 8){
                    if (!error_log($return_message, 1, self::$dev_contact, $headers)){
                        self::log_db("Error sending error report to dev");
                    }

                } else {
                    self::log_db("To avoid Allowed memory size being exhausted, the program is now being terminated");
                    die();
                }
            }

            self::$errors = $return_message;
                /* retry */
            self::initiateDatabaseConnection($query, self::$selected_db);

        } else {
            /* sql error, simply log and skip for now */
            if (!file_exists($logDir)){
                if (!mkdir($logDir, 0744)){
                    self::log_db(' Error creating directory: '. $logDir);
                }
            }
            /* update log file */
            file_put_contents($logDir .'/'. date("Y-m-d"). '.log', date("H:i:s") . $return_message ." | ". $query. "\n", FILE_APPEND);

            self::$errors = $return_message ." | ". $query;
            /* try a reconnection and skip */
            self::initiateDatabaseConnection($query, self::$selected_db, $skip_query=true);
        }
    }

    /* get max_connections */
    private static function sql_max_connections()
    {
        $result = db::sql('show variables like "max_connections";', DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($var_name, $var_value)=mysqli_fetch_array($result)){
                return (int)$var_value;
            }
        }
        return null;
    }

    /* set max_connections */
    private static function sql_set_max_connections()
    {
        db::sql("set global max_connections = 3000;", DB_NAME);
    }

    /* query for a result */
    public static function sql($query, $dbname, $skip_query=null)
    {
        /* a database log directory is required */
        if (!defined('DB_LOG_DIR')){
            echo "<i style='color:red'> An error has occurred. Please define a constant named 'DB_LOG_DIR', the location to save database related errors
                    before instantiating or calling a method of this class | " . __CLASS__ . '{}';
            die();
        }
        /* db_username is required */
        if (is_null(self::$username) || !defined('DB_USERNAME')){
            echo "<i style='color:red'> An error has occurred. Please define a constant 'DB_USERNAME', the database username
                    before instantiating or calling a method of this class | " . __CLASS__ . '{}';
            die();
        }

        if (is_null(self::$password) || !defined('DB_PASSWORD')){
            echo "<i style='color:red'> An error has occurred. Please define a constant 'DB_PASSWORD', the database password
                    before instantiating or calling a method of this class | " . __CLASS__ . '{}';
            die();
        }

        /* reset the object field result */
        self::$result_set = null;
        /* check if the connection still opened */
        if (self::$conn && self::$selected_db == $dbname){

            /* check if prepare statement is required */
            if (self::is_prepared_query($query)){
                /* check prepare values are assigned */
                if (self::$prepare_param_values && is_array(self::$prepare_param_values)){
                    /* create a prepared statement */
                    self::$statement_result = mysqli_prepare(self::$conn, $query);
                    /* prepare parameters makers for the binding statement */
                    array_unshift(self::$prepare_param_values, self::set_prepare_markers(self::$prepare_param_values));
                    array_unshift(self::$prepare_param_values, self::$statement_result);
                    /* call the binding statement by referencing parameters values */
                    if (call_user_func_array('mysqli_stmt_bind_param', self::$prepare_param_values)){
                        if (!mysqli_stmt_execute(self::$statement_result)){
                            self::log_db('Failed executing a prepared statement query ('. $query. ') : '. mysqli_stmt_errno(self::$statement_result). ' | '. mysqli_stmt_error(self::$statement_result));
                        } else {
                            if (!self::is_insert_query($query) && !self::is_update_delete_query($query)){
                                self::$statement_result = mysqli_stmt_get_result(self::$statement_result);
                            }
                        }

                    } else {

                        self::log_db('Error encountered during mysqli_stmt_bind_param for current query : '. $query. ' | Please review your current query');
                    }
                } else {
                    self::log_db('Failed querying : '. $query. ' | Please make sure your prepared values are set in object field as an array passed by reference');
                }
            } else {
                /* check if it's multi-query */
                if (self::is_multi_query($query)){
                    self::$result_set = mysqli_multi_query(self::$conn, $query);
                } else {
                    /* query for a result set */
                    self::$result_set =  mysqli_query(self::$conn, $query);
                }
            }

            /*check if there's a query error encountered */
            if (self::$result_set === false){
                /* check a skip current query has been initiated */
                if (!is_bool($skip_query)){
                    if (self::is_prepared_query($query) && !self::$statement_result){
                        self::sql_connect_handler(mysqli_errno(self::$conn), mysqli_error(self::$conn), $query);
                    } else {
                        self::sql_connect_handler(mysqli_errno(self::$conn), mysqli_error(self::$conn), $query);
                    }
                }
            } else {
                if (self::is_prepared_query($query)){
                    if (self::$statement_result === false){
                        self::log_db('Failed querying : '. $query);
                    } else {
                        //self::log_db('Successfully querying : '. $query);
                    }
                } else {
                    //self::log_db('Successfully querying : '. $query);
                }
            }

        } else {
            self::initiateDatabaseConnection($query, $dbname);
        }

        $return_query_call = null;
        /* return last inserted id if $bool param is set to true */
        if (is_bool(self::$result_set)){
            /* return the connection string for multiple query */
            if (self::is_multi_query($query)){
                $return_query_call = self::$conn;
            }
            else {
                /* check if mysqli_result is true to return the last inserted */
                if (self::$result_set && self::is_insert_query($query)){
                    $return_query_call = mysqli_insert_id(self::$conn);
                } else if (self::$result_set && self::is_update_delete_query($query)){
                    $return_query_call = mysqli_affected_rows(self::$conn);
                } else {
                    $return_query_call = self::$result_set;
                }
            }

        } else {
            /* check if it's a prepared statement result */
            if (self::is_prepared_query($query)){
                if (self::is_insert_query($query)){
                    $return_query_call = mysqli_insert_id(self::$conn);
                } else if (self::is_update_delete_query($query)){
                    $return_query_call = mysqli_affected_rows(self::$conn);
                } else {
                    $return_query_call = self::$statement_result;
                }
            } else {
                if (self::is_update_delete_query($query)){
                    $return_query_call = mysqli_affected_rows(self::$conn);
                } else {
                    $return_query_call = self::$result_set;
                }
            }
        }

        return $return_query_call;
    }

    private static function prepare_escape_string($conn, $prepared_values)
    {
        /* Escapes special characters in a string for use in an SQL statement */
        $prepared_values_escape = [];
        foreach($prepared_values AS $key => $values){
            $prepared_values_escape[$key] = mysqli_real_escape_string($conn, $values);
        }
        return $prepared_values_escape;
    }

    /* check if it's a multi query */
    private static function is_multi_query($query)
    {
        $is_multi = null;

        $list_query = explode(';', $query);
        if (count($list_query) > 1){
            if ($list_query[1]){
                $is_multi = true;
            }
        }
        /* check for html entities if present */
        if (strrpos($query, '&') !== false) $is_multi = null;

        return $is_multi;
    }

    /* check if a prepare query is requested */
    private static function is_prepared_query($query)
    {
        return (strpos($query, '?') ? ((!is_null(self::$prepare_param_values)) ? true : null) : null);
    }

    /* bind parameters for the prepare markers */
    private static function set_prepare_markers($prepare_values)
    {
        $markers = null;
        for($i=0; $i < count($prepare_values); ++$i){
            if (is_int($prepare_values[$i])){
                $markers .='i';
            }
            if (is_double($prepare_values[$i])){
                $markers .='d';
            }
            if (is_string($prepare_values[$i])){
                $markers .='s';
            }
        }
        return $markers;
    }

    /* check if last return inserted can be performed */
    private static function is_insert_query($query)
    {
        $result = null;
        /* make sure we can match the query search whether a upper or lowercase was used */
        $q_search = explode(' ', $query);
        $q_search[0] = strtoupper($q_search[0]);
        $query = implode(' ', $q_search);

        if (strpos($query, 'INSERT') !== false){
            $result = true;
        }
        return $result;
    }

    /* check if affected rows result can be performed */
    private static function is_update_delete_query($query)
    {
        $result = null;
        /* make sure we can match the query search whether a upper or lowercase was used */
        $q_search = explode(' ', $query);
        $q_search[0] = strtoupper($q_search[0]);
        $query = implode(' ', $q_search);

        if (strpos($query, 'UPDATE') !== false || strpos($query, 'DELETE') !== false){
            $result = true;
        }
        return $result;
    }

    /* log db querying activity */
    public static function log_db($log_message)
    {
        /* set log directory */

        $logDir = DB_LOG_DIR;

        if (!file_exists($logDir)){
            if (!mkdir($logDir, 0744)){
                self::log_db(' Error creating directory: '. $logDir);
            }
        }
        /* update log file */
        file_put_contents($logDir .'/'. date("Y-m-d"). '.log', date("H:i:s") . ' '. $log_message ."\n", FILE_APPEND);
    }


    /* close db connection */
    public static function close()
    {
        /* Free up all resources statements. */
        if (self::$result_set && !is_bool(self::$result_set)){
            mysqli_free_result(self::$result_set);
        }

        /* close db connection */
        if (self::$conn){
            if (mysqli_close(self::$conn)){
                //self::log_db('MYSQL :: Database connection successfully closed');
            } else {
                self::log_db('Failed closing MYSQL Database connection');
            }
        }
    }
}