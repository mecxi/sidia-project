<div class="row">
    <div class="col-md-4">

        <!-- Profile Services Stats -->
        <div class="box box-primary">
            <div class="box-body box-profile">
                <img class="profile-user-img img-responsive img-circle" src="<?php echo BASE_URI .'assets/';?>dist/img/avatar.png" alt="User profile picture">

                <h3 class="profile-username text-center"><?php echo $_SESSION['fullname']?></h3>

                <p class="text-muted text-center"><?php echo $_SESSION['type'];?></p>
                <div id="profile-stat">
<!--                    <hr>-->
<!--                    <strong><i class="fa fa-book margin-r-5"></i> Glam Squad</strong>-->
<!--                    <p class="text-muted">-->
<!--                        Status: <i class="active">Subscribed</i> | Cc Pts: <i>50</i> | Total Pts: <i>100</i> | Last Play: <i>2017-07-18</i>-->
<!--                    </p>-->
<!--                    <hr>-->
<!--                    <strong><i class="fa fa-book margin-r-5"></i> Glam Squad</strong>-->
<!--                    <p class="text-muted">-->
<!--                        Status: <i class="inactive">Deactivated</i> | Cc Pts: <i>50</i> | Total Pts: <i>100</i> | Last Play: <i>2017-07-18</i>-->
<!--                    </p>-->
                </div>

                <!-- Services Stats -->
<!--                <ul class="list-group list-group-unbordered">-->
<!--                    <li class="list-group-item">-->
<!--                        <b>Glam Squad</b> <a class="pull-right"><i class="service_stop">&nbsp;</i></a>-->
<!--                    </li>-->
<!--                    <li class="list-group-item">-->
<!--                        <b>Beauty Tips</b> <a class="pull-right"><i class="service_stop">&nbsp;</i></a>-->
<!--                    </li>-->
<!--                    <li class="list-group-item">-->
<!--                        <b>Slay or Nay</b> <a class="pull-right"><i class="service_stop">&nbsp;</i></a>-->
<!--                    </li>-->
<!--                    <li class="list-group-item">-->
<!--                        <span class="text-muted"><i>Today Points</i></span><a class="pull-right"><span class="badge bg-red">0</span></a>-->
<!--                    </li>-->
<!--                    <li class="list-group-item">-->
<!--                        <span class="text-muted"><i>Total Points</i></span><a class="pull-right"><span class="badge bg-red">0</span></a>-->
<!--                    </li>-->
<!--                    <li class="list-group-item">-->
<!--                        <span class="text-muted"><i>Last Played</i></span><a class="pull-right"><i>&nbsp;</i></a>-->
<!--                    </li>-->
<!--                    <li class="list-group-item">-->
<!--                        <span class="text-muted"><i>Last Seen</i></span><a class="pull-right"><i>&nbsp;</i></a>-->
<!--                    </li>-->
<!--                </ul>-->
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
    <!-- /.col -->
    <div class="col-md-8">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#activity" data-toggle="tab">Activity</a></li>
                <li><a href="#settings" data-toggle="tab">Settings</a></li>
            </ul>
            <div class="tab-content">
                <div class="active tab-pane" id="activity">
                    <!-- Post-TIPS -->
                    <div class="post" id="default">
                        <div class="user-block">
                            <img class="img-circle img-bordered-sm" src="<?php echo BASE_URI .'assets/';?>dist/img/profile/notify.jpg" alt="User Image">
                        <span class="username">
                          <a href="#">Notify</a>
                          <a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>
                        </span>
                            <span class="description">Loading ...</span>
                        </div>
                        <!-- /.user-block -->
                        <p>
                            Your Recent Services activities will update automatically.
                        </p>

                    </div>
                </div>
                <!-- /.tab-pane -->
                <div class="tab-pane" id="settings">
                    <p class="text-muted text-left"><i>Update your profile below</i></p>
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label for="inputName" class="col-sm-2 control-label">Name</label>

                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="inputName" placeholder="Name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputName" class="col-sm-2 control-label">Surname</label>

                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="inputName" placeholder="Name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputEmail" class="col-sm-2 control-label">Email</label>

                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="inputEmail" placeholder="Email">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputName" class="col-sm-2 control-label">Phone no.</label>

                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="inputName" value="<?php echo $_SESSION['phone'];?>" placeholder="" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-danger">Update Info</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.tab-pane -->
            </div>
            <!-- /.tab-content -->
        </div>
        <!-- /.nav-tabs-custom -->
    </div>
    <!-- /.col -->
</div>