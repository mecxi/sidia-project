<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Create a service Draw Engine</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="box box-info create-basic">
                    <div class="box-header with-border">
                        <h3 class="box-title">Basic</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-2">
                                <label>Name:</label>
                                <input type="text" class="form-control" name="name" placeholder="Enter a Campaign draw name">
                            </div>
                            <div class="col-xs-3">
                                <label>Description:</label>
                                <input type="text" class="form-control" name="description" placeholder="Describe this campaign draw">
                            </div>
                            <div class="col-xs-4">
                                <label>Notify:</label>
                                <input type="text" class="form-control" name="notify" placeholder="Enter notify message to sent to winners">
                            </div>
                            <div class="col-xs-3">
                                <label>Winners Rollout:</label><br/>
                                <input type="checkbox" name="draw_win_rollout" data-size="mini" value="1" data-on-text="Once" data-off-text="Always" checked><br/>
                                <i>*Select whether a previous winner can still be part of the draw.</i>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-xs-2">
                                <label>Raffle Draw Type:</label><br/>
                                <select class="form-control" name="draw_type">
                                </select>
                            </div>
                            <div class="col-xs-2">
                                <label>Number's winners per Draw:</label><br/>
                                <input type="text" class="form-control" name="draw_winner_num" placeholder="Enter the number of winner per draw">
                            </div>
                            <div class="col-xs-2">
                                <label>Draw Engine Type:</label><br/>
                                <input type="checkbox" name="draw_engine_type" data-size="mini" value="1" data-on-text="Auto" data-off-text="Manual" disabled><br/>
                                <i>*Select whether the system will automatically run the draw or manually done by an admin.</i>
                            </div>
                            <div class="col-xs-3">
                                <label>Service Draw Run Date:</label><br/>
                                <input type="text" name="draw_range_date" value="01/01/2017 - 01/31/2017" /><br/>
                                <i>*Define the date range the draw will be allowed to run</i>
                            </div>
                            <div class="col-xs-3">
                                <label>Draw associated services:</label><br/>
                                <span id="services_draws_linked"></span><br/>
                                *Add services that the draw will be linked to
                            </div>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <div class="box-footer clearfix" style="text-align: center">
                    <button class="btn btn-info" title="Click to create a new service">Create Draw Engine</button>
                </div>
                <!-- /.box-footer -->
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    <!-- /.col -->
</div>