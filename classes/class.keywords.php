<?php
/**
 * User: Mecxi
 * Date: 7/23/2017
 * Time: 6:06 PM
 * service related keywords
 */
class keywords
{
    public $id;
    public $keyword;

    public function __construct($id)
    {
        $result = db::sql("SELECT keyword FROM `tl_keyword` WHERE id = '$id';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($keyword) = mysqli_fetch_array($result)){
                $this->keyword = $keyword;
            }
        }
    }

    /* return the id related keyword */
    public static function get_id($keyword)
    {
        $result = db::sql("SELECT id FROM `tl_keyword` WHERE keyword = '$keyword';", DB_NAME);
        if (mysqli_num_rows($result)){
            while(list($id) = mysqli_fetch_array($result)){
                return $id;
            }
        } return null;
    }

    /* check keyword */
    private static function is_keyword_added($keyword)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_keyword` WHERE keyword = '$keyword';", DB_NAME));
    }

    /* check the number of keyword allowed has been met */
    public static function has_keyword_length($service_local_id)
    {
        return mysqli_num_rows(db::sql("SELECT * FROM `tl_services_keywords` WHERE service_local_id = '$service_local_id';", DB_NAME));
    }


    /* insert keyword */
    public static function add_keyword($keyword)
    {
       /* check keyword already added */
        if (self::is_keyword_added($keyword)){
            return self::get_id($keyword);
        }
        return db::sql("INSERT INTO tl_keyword (keyword) VALUES('$keyword')", DB_NAME);
    }

    /* link keyword to a service */
    public static function add_keyword_service_id($keyword_id, $service_local_id)
    {
        return db::sql("INSERT INTO `tl_services_keywords` (keyword_id, service_local_id) VALUES('$keyword_id', '$service_local_id');", DB_NAME);
    }

    public static function delete_keyword($keyword, $service_local_id)
    {
        /* get related id */
        $keyword_id = keywords::get_id($keyword);
        if ($keyword_id){
            return db::sql("DELETE FROM `tl_services_keywords` WHERE keyword_id = '$keyword_id' AND service_local_id = '$service_local_id';", DB_NAME);
        } else {
            return array('error'=>'keyword provided couldn\'t be found. Please review');
        }
    }


}