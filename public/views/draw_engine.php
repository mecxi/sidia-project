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
<!-- include alert modal -->
<?php include_once('incs/alert_modal.php') ?>
<!-- table 100 Top Players -->
<div class="row">
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header">
                <h3 class="box-title"><?php echo $current_service_draw->draw_type_name; ?> Date Range: <span custom="range">00:00:00</span>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Select a Draw Date: <input type="text" name="service_draw_range_date" value="01/01/2017 - 01/31/2017" /><br/></h3>
                <!-- box-tools -->
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-wrench"></i></button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#"><i class="icon fa fa-gear"></i> Raffle Settings</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:process_draw_selection(1);"><i class="icon  fa fa-calendar-check-o"></i> Select Top Winners</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:process_draw_selection(0);"><i class="icon fa fa-calendar-plus-o"></i> Select Random Winners</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:request_export_engine('player_preview');"><i class="fa fa-arrow-circle-o-down"></i> Export As CSV</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="player_preview" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Msisdn</th>
                        <th>Service</th>
                        <th>Score</th>
                        <th>Entries</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- table 100 Players Winners -->
<div class="row">
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header">
                <h3 class="box-title"><?php echo $current_service_draw->draw_type_name; ?> Winners</h3>
                <!-- box-tools -->
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-wrench"></i></button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="javascript:force_page_refresh('NOW');"><i class="icon fa fa-refresh"></i> Refresh</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:reset_current_winner();"><i class="icon  fa fa-flash"></i> Reset</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:request_export_engine('player_winners');"><i class="icon fa fa-arrow-circle-o-down"></i> Export As CSV</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:request_notify_winners();"><i class="icon fa fa-envelope"></i> Notify Winners</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="players_winners" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Msisdn</th>
                        <th>Score</th>
                        <th>Entries</th>
                        <th>Type</th>
                        <th>Drawn By</th>
                        <th>Date Created</th>
                        <th>Notify Sent</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>


