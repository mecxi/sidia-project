<!-- Live - services Entries Reports -->
<div class="row">
    <div class="col-xs-12">
        <div class="box box-success" box_line="l_icon_entries">
            <div class="box-header">
                <h3 class="box-title">Live Services Requests | <span custom="clock">00:00:00</span></h3>
                <!-- box-tools -->
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-wrench"></i></button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#"><i class="icon fa fa-gear"></i> Settings</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:update_refresh_set_entries('0')"><i class="icon fa fa-refresh"></i> Refresh</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:update_refresh_set_entries('1')"><i class="icon fa fa-clock-o"></i> 1 Min</a></li>
                            <li><a href="javascript:update_refresh_set_entries('5')"><i class="icon fa fa-clock-o"></i> 5 Min</a></li>
                            <li><a href="javascript:update_refresh_set_entries('30')"><i class="icon fa fa-clock-o"></i> 30 Min</a></li>
                            <li><a href="javascript:update_refresh_set_entries('disable')"><i class="icon fa fa-power-off"></i> Turn Off</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="services_request" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Msisdn</th>
                        <th>Request</th>
                        <th>Service</th>
                        <th>Sub Type</th>
                        <th>Trial Period</th>
                        <th>State</th>
                        <th>Details</th>
                        <th>Requested</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>