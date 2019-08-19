<!-- include monthly graph -->
<div class="row">
    <div class="col-xs-12">
        <?php include_once('users_graph_monthly.php')?>
    </div>
</div>


<div class="row">
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header">
                <!-- date range -->
                <!--                <div class="form-group  pull-left">-->
                <!--                    <div class="input-group">-->
                <!--                        <button type="button" class="btn btn-default pull-right" id="daterange-btn">-->
                <!--                            <i class="fa fa-calendar"></i> Date range picker-->
                <!--                            <i class="fa fa-caret-down"></i>-->
                <!--                        </button>-->
                <!--                    </div>-->
                <!--                </div>-->
                <!-- box-tools -->
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-wrench"></i></button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="javascript:refresh_page();"><i class="icon fa fa-refresh"></i> Refresh</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:request_printing_report('CSV', <?php echo isset($_GET['id']) ? $_GET['id'] : null; ?>)"><i class="icon fa fa-print"></i> Download As Excel</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="billing_report" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Daily Subs</th>
                        <th>Daily Cancel</th>
                        <th>Daily PlayRate</th>
                        <th>Total Subs</th>
                        <th>Daily Revenue</th>
                        <th>Daily Target Revenue</th>
                        <th>Daily Bill Rate</th>
                        <th>Repeat Subs</th>
                        <th>Total Revenue</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>