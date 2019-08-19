<?php
/**
 * File-upload object handler
 * User: Mecxi
 * Date: 4/23/2017
 * Time: 10:03 PM
 */

class upload
{
    public static function process_upload_request(&$file)
    {
        /* check required file extension */
        $filenames = explode(".", $file['name']);
        $extension = end($filenames);
        if (in_array($extension, array("csv", "txt"))){
            /* check file type */
            if (in_array($file['type'], array('application/vnd.ms-excel'))){
                /* check if file is exempt from error */
                if ($file['error'] == 0){
                    return self::start_upload($file);
                } else {
                    return array('error'=>'An internal error occurred - '. $file['error']);
                }
            } else {
                return array('error'=>'Error processing your request - Unsupported File. Please upload a CSV file');
            }
        } else {
            return array('error'=>'Error processing your request - Unsupported File. Please upload a CSV file');
        }
    }

    /* start upload processs */
    private static function start_upload(&$file)
    {
        /* save file */
        if (self::save_file($file)){
            return true;
        } else {
            return array('error'=>'An internal error occurred - enable to write file to server');
        }
    }
    /* save the upload file */
    private static function save_file(&$file)
    {
        $dir_path= DB_LOG_DIR;
        $file_dir = $dir_path. '/'. $file['name'];

        if (!file_exists($dir_path)){
            mkdir($dir_path, 0744);
        }
        /* save file */
        return move_uploaded_file($file["tmp_name"], $file_dir);
    }

    public static function fetch_file(&$file, $file_type=null)
    {
        $dir_path= DB_LOG_DIR;
        $file_dir = $dir_path. '/'. $file['name'];
        $buffer = array();

        if (file_exists($file_dir)){
            /* read current CSV file */
            $tempfile = fopen($file_dir, 'r');

            switch($file_type){
                case 'CSV':
                    while (($data = fgetcsv($tempfile, 1000, ",")) !== FALSE) {
                        $buffer[] = $data;
                    }
                    break;
                default:
                    while (!feof($tempfile)) {
                        /* read data per line */
                        $buffer[] = fgets($tempfile, 4096);
                    }
                    break;
            }
            fclose($tempfile);
        } else {
            /* create the file */
           return array('error'=>'An internal error occurred. - enable to fetch uploaded file on the server');
        }

        if (is_array($buffer)){
            return $buffer;
        } else {
            return null;
        }
    }

}