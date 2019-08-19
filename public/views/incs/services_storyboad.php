<!-- storyboard -->
<div class="row" id="storyboard">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="ion ion-ios-cart-outline"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Total Revenue Services</span>
                <span class="info-box-number"><?php echo CURRENCY; ?> 0.00</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box" title="display today skipped services">
            <span class="info-box-icon bg-red"><i class="fa fa-envelope-o"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Skipped Servicing Users</span>
                <span class="info-box-number">0</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <?php
        if ($_SESSION['role'] == '1'){
            if ($page_requested == 'services' || $page_requested == 'addservice'){
                echo '
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="info-box" title="Create a new service campaign">
                        <span class="info-box-icon bg-aqua"><i class="fa fa-bars"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Add New</span>
                            <span class="info-box-number">Campaign Service</span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
            ';
            } else {
                echo '
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="info-box" title="Create a new campaign Draw Service">
                        <span class="info-box-icon bg-yellow"><i class="fa fa-cubes"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">Add New</span>
                            <span class="info-box-number">Campaign Draw Service</span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
            ';
            }
        }
    ?>
</div>
<!-- /.row -->