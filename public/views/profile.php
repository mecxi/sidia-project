<?php
/**
 * @customised by Mecxi Musa
 * date: 2017-01-06
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
<?php include_once('incs/profile_activity.php') ?>

<?php
//    echo 'CURRENT SESSION is :<br><pre>'. print_r($_SESSION, true).'</pre>';
?>