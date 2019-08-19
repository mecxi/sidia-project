<!-- Live - today broadcast Reports -->
<div class="row">
    <div class="col-xs-12">
        <div class="box box-success" box_line="l_icon_push">
            <div class="box-header">
                <h3 class="box-title">Live Broadcast Push | <span custom="clock">00:00:00</span></h3>
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
                            <li><a href="javascript:update_refresh_set_push('0')"><i class="icon fa fa-refresh"></i> Refresh</a></li>
                            <li class="divider"></li>
                            <li><a href="javascript:update_refresh_set_push('1')"><i class="icon fa fa-clock-o"></i> 1 sec</a></li>
                            <li><a href="javascript:update_refresh_set_push('5')"><i class="icon fa fa-clock-o"></i> 5 Sec</a></li>
                            <li><a href="javascript:update_refresh_set_push('30')"><i class="icon fa fa-clock-o"></i> 30 Sec</a></li>
                            <li><a href="javascript:update_refresh_set_push('60')"><i class="icon fa fa-clock-o"></i> 1 Min</a></li>
                            <li><a href="javascript:update_refresh_set_push('disable')"><i class="icon fa fa-power-off"></i> Turn Off</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <!-- Automated Daily Push Traffic -->
                    <div class="col-md-12" id="broadcast">
                        <p class="text-center">
                            <strong>Daily Traffic - (Automated) Scheduled @ 09:00 AM | Retries interval : @hourly</strong>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>