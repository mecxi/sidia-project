<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Create a service campaign</h3>
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
                            <div class="col-xs-3">
                                <label>Name:</label>
                                <input type="text" class="form-control" name="name" placeholder="Enter a service name">
                            </div>
                            <div class="col-xs-3">
                                <label>Description:</label>
                                <input type="text" class="form-control" name="description" placeholder="Enter a service description">
                            </div>
                            <div class="col-xs-3">
                                <label>Keywords:</label>
                                <input type="text" class="form-control" name="keywords" placeholder="Enter a service keywords separated by comma">
                            </div>
                            <div class="col-xs-3">
                                <label>Select Service Type:</label>
                                <select class="form-control" name="type">
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-xs-3">
                                <label>Content Services to cross-sell:</label><br/>
                                <span id="services_crossell"></span>

                            </div>
                            <div class="col-xs-3">
                                <label>Promotion Date:</label><br/>
                                <input type="text" class="daterange_promo" name="promotion_date" value="01/01/2017 - 01/31/2017" />
                            </div>
                            <div class="col-xs-3 hidden-xs hidden-sm hidden-md hidden-lg">
                                <label>Service Intro</label><br>
                                <input type="checkbox" name="br_opening" data-size="mini" checked><br/>
                                <i>A welcome message sent to the user upon subscription. If not required unchecked this option</i>

                            </div>
                        </div>
                        <div class="row hidden-xs hidden-sm hidden-md hidden-lg service-messages-box">
                            <hr>
                            <div class="col-xs-3">
                                <label>Welcome:</label>
                                <input type="text" class="form-control" name="msg_welcome" placeholder="Enter a welcome/opener msg to send to subscriber">
                            </div>
                            <div class="col-xs-3">
                                <label>Good Score:</label>
                                <input type="text" class="form-control" name="msg_good_score" placeholder="Enter a good score msg to send to subscriber">
                            </div>
                            <div class="col-xs-3">
                                <label>Poor Score:</label>
                                <input type="text" class="form-control" name="msg_poor_score" placeholder="Enter a poor score msg to send to subscriber">
                            </div>
                            <div class="col-xs-3">
                                <label>Last Play:</label>
                                <input type="text" class="form-control" name="msg_last_play" placeholder="Enter a last play msg to send to subscriber">
                            </div>
                            <div class="col-xs-3">
                                <label>Never Play:</label>
                                <input type="text" class="form-control" name="msg_never_play" placeholder="Enter a never play msg to send to subscriber">
                            </div>
                            <div class="col-xs-3">
                                <label>Excellent Score:</label>
                                <input type="text" class="form-control" name="msg_excel_score" placeholder="Enter an excel score msg to send to subscriber">
                            </div>
                            <div class="col-xs-3">
                                <label>Closing:</label>
                                <input type="text" class="form-control" name="msg_closing" placeholder="Enter a closing msg to send to subscriber">
                            </div>
                        </div>
                        <div class="row hidden-xs hidden-sm hidden-md hidden-lg message-cross-sell-box">
                            <hr>
                            <div class="col-xs-4">
                                <label>Cross Sell Msg:</label>
                                <input type="text" class="form-control" name="msg_cross_sell" placeholder="Enter a cross sell msg for this service">
                            </div>
                            <div class="col-xs-4">
                                <label>Cross Sell Correct Response:</label>
                                <input type="text" class="form-control" name="msg_cross_sell_correct" placeholder="Enter a cross sell correct msg for this service">
                            </div>
                            <div class="col-xs-4">
                                <label>Cross Sell Incorrect Response:</label>
                                <input type="text" class="form-control" name="msg_cross_sell_incorrect" placeholder="Enter a cross sell incorrect msg for this service">
                            </div>
                        </div>
                        <div class="row hidden-xs hidden-sm hidden-md hidden-lg exclusive-message-box">
                            <hr>
                            <div class="col-xs-4">
                                <label>Intro Service Message:</label>
                                <input type="text" class="form-control" name="msg_intro" placeholder="Enter a service intro message or disable it if not needed">
                            </div>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <div class="box box-info create-broadcast">
                    <div class="box-header with-border">
                        <h3 class="box-title">Broadcast</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <label>Select Type:</label>
                                <select class="form-control" name="br_type"></select>
                            </div>
                            <div class="col-xs-3">
                                <label>Length:</label>
                                <input type="text" class="form-control" name="br_length" placeholder="Enter no of content to send per day">
                            </div>
                            <div class="col-xs-3">
                                <label>Cross Sell:</label><br>
                                <input type="checkbox" name="br_crossell" data-size="mini" value="1" checked>
                            </div>
                            <div class="col-xs-3">
                                <label>Cross Sell Upon Subscription:</label><br>
                                <input type="checkbox" name="br_crossell_sub" data-size="mini" checked>
                            </div>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <div class="box box-info create-sdp-config">
                    <div class="box-header with-border">
                        <h3 class="box-title">SDP Config</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-2">
                                <label>SP ID:</label>
                                <input type="text" class="form-control" name="sp_id" placeholder="Enter your SP ID">
                            </div>
                            <div class="col-xs-2">
                                <label>SP PASSWORD:</label>
                                <input type="text" class="form-control" name="sp_password" placeholder="Enter your SP Password">
                            </div>
                            <div class="col-xs-3">
                                <label>SERVICE ID:</label>
                                <input type="text" class="form-control" name="sp_service_id" placeholder="Enter Service ID">
                            </div>
                            <div class="col-xs-3">
                                <label>SHORT-CODE/LONG-CODE:</label>
                                <input type="text" class="form-control" name="sp_shortcode" placeholder="Enter Service Shortcode">
                            </div>
                            <div class="col-xs-2">
                                <label>Free Trial/Period:</label>
                                <input type="text" class="form-control" name="sp_free_period" placeholder="Enter Service Trial Period">
                                <i>* for a free service set to the duration of the campaign.</i>
                            </div>
                        </div>
                        <div class="box-footer clearfix" style="text-align: left">
                            <button class="btn btn-primary" title="Click to add additional product"><i class="fa fa-plus"></i> Product</button>
                        </div>
                        <div class="row products">
                            <hr/>
                            <div class="col-xs-3">
                                <label>PRODUCT ID:</label>
                                <input type="text" class="form-control" name="sp_product_id" placeholder="Enter Product ID">
                            </div>
                            <div class="col-xs-3">
                                <label>Service Bill Rate:</label>
                                <input type="text" class="form-control" name="sp_bill_rate" placeholder="Enter Service Billing Rate">
                            </div>
                            <div class="col-xs-3">
                                <label>Billying Cycle:</label>
                                <select class="form-control" name="bl_cycle">
                                    <option value="1">Daily</option>
                                    <option value="2">Weekly</option>
                                    <option value="3">Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <div class="box-footer clearfix" style="text-align: center">
                    <button class="btn btn-info" title="Click to create a new service">Create Service</button>
                </div>
                <!-- /.box-footer -->
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    <!-- /.col -->
</div>