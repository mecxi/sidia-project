<?php
/**
 * @customised by Mecxi Musa
 * date: 2016-08-12
 */

/* check if the page is been requested directly */
if (!defined('BASE_URI')){
    require_once('../../config.php');
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
    exit();
}
?>
<!-- Your Page Content Here -->


<!-- include users stats -->
<?php include_once('incs/users_stats.php'); ?>
<!-- include alert modal -->
<?php include_once('incs/alert_modal.php'); ?>
<!-- include billing monitor reports -->
<?php if (in_array($_SESSION['role'], array('1', '2', '6'))){ include_once('incs/billing_monitor.php'); } ?>
<!-- include services entries reports -->
<?php if (in_array($_SESSION['role'], array('1', '2', '6'))){ include_once('incs/services_monitor.php'); } ?>

<?php if (in_array($_SESSION['role'], array('1', '2', '6'))){ include_once('incs/payment_monitor.php'); } ?>
<!-- include push broadcast reports -->
<?php include_once('incs/broadcast_monitor.php')?>

<?php
    //echo 'CURRENT SESSION is :<br><pre>'. print_r($_SESSION, true).'</pre>';
?>
