<?php
/**
 * @customised by Mecxi Musa
 * date: 2017-03-17
 * Service Billing Report
 */

/* check if the page is been requested directly */
if (!defined('BASE_URI')){
    require_once('../../config.php');
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
    exit();
}
?>

<!-- include overall billing stats -->

<?php if (in_array($_SESSION['role'], array('1', '2'))){ include_once('incs/billing_stats.php'); } ?>
<!-- include alert modal -->
<?php include_once('incs/alert_modal.php') ?>
<!-- include users graph -->
<div class="row">
    <div class="col-xs-6">
        <?php include_once('incs/users_graph_daily.php')?>
    </div>
    <div class="col-xs-6">
        <?php include_once('incs/users_graph_totals.php')?>
    </div>
</div>

<!-- include weekly play_rate -->
<div class="row">
    <div class="col-xs-12">
        <?php include_once('incs/users_weekly_rate.php')?>
    </div>
</div>


<!-- include billing graph -->
<div class="row">
    <div class="col-xs-6">
        <?php if (in_array($_SESSION['role'], array('1', '2'))){ include_once('incs/billing_graph_revenue_daily.php'); }?>
    </div>
    <div class="col-xs-6">
        <?php if (in_array($_SESSION['role'], array('1', '2'))){ include_once('incs/billing_graph_revenue_total.php'); }?>
    </div>
</div>

<!-- include billing overall stats -->
<?php if (in_array($_SESSION['role'], array('1', '2'))){ include_once('incs/billing_overall_stats.php'); } ?>

