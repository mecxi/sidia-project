<?php
/**
 * User: Mecxi
 * Date: 7/20/2017
 * Time: 9:39 PM
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
<?php  include_once('incs/alert_modal.php'); ?>

<?php if ($page_requested == 'services') include_once('incs/alert_editing.php'); ?>
<!-- include service story board stats -->
<?php if (in_array($_SESSION['role'], array('1'))){ include_once('incs/services_storyboad.php'); } ?>
<!-- include service overviews stats -->
<?php if ($page_requested == 'services') include_once('incs/services_overviews.php'); ?>
<!-- include service creation -->
<?php if ($page_requested == 'addservice') include_once('incs/services_creation.php') ?>
<!-- include campaign draw creation -->
<?php if ($page_requested == 'addDrawEngine') include_once('incs/draw_engine_creation.php') ?>
<!-- include campaign draw overviews -->
<?php if ($page_requested == 'draws') include_once('incs/draw_engine_overviews.php') ?>