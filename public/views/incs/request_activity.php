<div class="row">
    <div class="col-md-6">
        <!-- MSISDN QUERY -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i>Subscriber Info</i></h3>
            </div>
            <div class="box-body box-request">
                <br>
                <div class="col-xs-3"></div>
                <div class="col-xs-4"></div>
                <!-- query box area -->
                <div class="input-group input-group-sm col-xs-12">
                    <input type="text" class="form-control" placeholder="Enter User Mobile Number (e.g. 0810001123)" id="sub_request">
                    <span class="input-group-btn">
                      <button type="button" class="btn btn-info btn-flat"> Search </button>
                    </span>
                </div>
                <br><br>
                <!-- request report -->
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item"><i id="sub_report">Enter the mobile number in search box format (e.g. 0830001123). The current number profile will be query as well</i></li>
                </ul>
                <span id="query_display">
<!--                                    <!-- Services Stats -->
<!--                <strong><i class="fa fa-book margin-r-5"></i> Glam Squad Trivia</strong>-->
<!---->
<!--                <p class="text-muted">-->
<!--                    Subscribed on 4 April 2017 12:56 AM &nbsp;&nbsp;&nbsp;<button class="btn btn-danger btn-sm">Request Cancellation</button>-->
<!--                    <b>&nbsp;</b><a class="pull-right"><i>Subscribed</i></a>-->
<!--                </p>-->
<!---->
<!--                <hr>-->
<!---->
<!--                <strong><i class="fa fa-book margin-r-5"></i> Beauty Tips</strong>-->
<!---->
<!--                <p class="text-muted">-->
<!--                    Cancelled on 4 April 2017 12:56 AM &nbsp;&nbsp;&nbsp;<button class="btn btn-primary btn-sm">Request Activation</button>-->
<!--                    <b>&nbsp;</b><a class="pull-right"><i class="service_stop">Deactivated</i></a>-->
<!--                </p>-->
<!---->
<!--                <hr>-->
                </span>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    <!-- /.col -->

    <div class="col-md-6">
        <!-- MSISDN PROFILE HISTORY -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i>User Services Activities</i></h3>
            </div>
            <div class="box-body box-profile">
                <div class="col-xs-12" id="activity">
                    <!-- Related Service activities -->
                    <div class="post" id="default">
                        <div class="user-block">
<!--                            <img class="img-circle img-bordered-sm" src="--><?php //echo BASE_URI .'assets/';?><!--dist/img/profile/notify.jpg" alt="User Image">-->
<!--                        <span class="username">-->
<!--                          <a href="#">User Profile</a>-->
<!--                          <a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>-->
<!--                        </span>-->
                            <span class="description">Loading ...</span>
                        </div>
                        <!-- /.user-block -->
                        <p>
                            User Recent Services activities will load here.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>