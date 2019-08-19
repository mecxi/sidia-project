<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 11/24/2017
 * Time: 11:17 PM
 */

require_once('../config.php');

echo thread_ctrl::running_threads("thread_run.php");