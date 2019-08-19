<?php
/**
 * @customised by Mecxi Musa
 * date: 2016-08-12
 * Display all services entries | daily, weekly, monthly
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
<?php include_once('incs/users_stats.php') ?>
<!-- table 200 last un-Subscribers with no services -->
<?php
if (in_array($_SESSION['role'], array('1', '2', '6'))) {
    echo '
<div class="row">
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header">
                <h3 class="box-title">Last 100 unsubscribed with no services </h3>
                <!-- box-tools -->
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="users_unsub_all" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Msisdn</th>
                        <th>Service Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- table 200 last un-Subscribers -->
<div class="row">
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header">
                <h3 class="box-title">Last 100 recent unsubscribed requests</h3>
                <!-- box-tools -->
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="users_unsub" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Msisdn</th>
                        <th>Service Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
';
}
?>