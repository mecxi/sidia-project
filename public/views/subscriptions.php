<?php
/**
 * @customised by Mecxi Musa
 * date: 2017-02-23
 */

/* check if the page is been requested directly */
if (!defined('BASE_URI')){
    require_once('../../config.php');
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
    exit();
}
?><!-- Your Page Content Here -->
<!-- include alert modal -->
<?php include_once('incs/alert_modal.php') ?>
<!-- include users stats -->
<?php include_once('incs/request_activity.php') ?>
