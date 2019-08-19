<?php
/**
 * @customised by Mecxi Musa
 * date: 2016-12-08
 */

/* check if the page is been requested directly */
if (!defined('BASE_URI')){
    require_once('../../config.php');
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
    exit();
}

?>
</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
        <i><?php echo COMPANY_NAME . ' | Ver. '. APP_VER; ?></i>
    </div>
    <!-- Default to the left -->
    <span> &copy; <?php echo date('Y'); ?> <i class="visible-xs"><?php echo COMPANY_NAME . ' | Ver. '. APP_VER; ?></i></span>
</footer>

<!-- Control Sidebar
<aside class="control-sidebar control-sidebar-dark">
    <!-- Create the tabs
    <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
        <li class="active"><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
        <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
    </ul>
    <!-- Tab panes
    <div class="tab-content">
        <!-- Home tab content
        <div class="tab-pane active" id="control-sidebar-home-tab">
            <h3 class="control-sidebar-heading">Recent Activity</h3>
            <ul class="control-sidebar-menu">
                <li>
                    <a href="javascript::;">
                        <i class="menu-icon fa fa-birthday-cake bg-red"></i>

                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading">Langdon's Birthday</h4>

                            <p>Will be 23 on April 24th</p>
                        </div>
                    </a>
                </li>
            </ul>
            <!-- /.control-sidebar-menu

            <h3 class="control-sidebar-heading">Tasks Progress</h3>
            <ul class="control-sidebar-menu">
                <li>
                    <a href="javascript::;">
                        <h4 class="control-sidebar-subheading">
                            Custom Template Design
                            <span class="label label-danger pull-right">70%</span>
                        </h4>

                        <div class="progress progress-xxs">
                            <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
                        </div>
                    </a>
                </li>
            </ul>
            <!-- /.control-sidebar-menu

        </div>
        <!-- /.tab-pane -->
        <!-- Stats tab content
        <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
        <!-- /.tab-pane -->
        <!-- Settings tab content
        <div class="tab-pane" id="control-sidebar-settings-tab">
            <form method="post">
                <h3 class="control-sidebar-heading">General Settings</h3>

                <div class="form-group">
                    <label class="control-sidebar-subheading">
                        Report panel usage
                        <input type="checkbox" class="pull-right" checked>
                    </label>

                    <p>
                        Some information about this general settings option
                    </p>
                </div>
                <!-- /.form-group
            </form>
        </div>
        <!-- /.tab-pane
    </div>
</aside>
<!-- /.control-sidebar -->
<!-- Add the sidebar's background. This div must be placed
     immediately after the control sidebar
<div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->

<!-- REQUIRED JS SCRIPTS -->
<!-- jQuery 2.2.0 -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/jQuery/jQuery-2.2.0.min.js"></script>
<!-- Bootstrap 3.3.5 -->
<script src="<?php echo BASE_URI .'assets/';?>bootstrap/js/bootstrap.min.js"></script>

<!-- DataTables -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URI .'assets/';?>plugins/datatables/dataTables.bootstrap.min.js"></script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
     Both of these plugins are recommended to enhance the
     user experience. Slimscroll is required when using the
     fixed layout. -->
<!-- SlimScroll 1.3.0 -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/fastclick/fastclick.js"></script>
<!-- Moment Js -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/momentjs/moment.js"></script>
<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="<?php echo BASE_URI .'assets/';?>plugins/morris/morris.min.js"></script>

<!-- FLOT CHARTS -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/flot/jquery.flot.min.js"></script>

<!-- FLOT RESIZE PLUGIN - allows the chart to redraw when the window is resized -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/flot/jquery.flot.resize.min.js"></script>
<!-- iCheck 1.0.1 -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/iCheck/icheck.min.js"></script>
<!-- Date range picker -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap switch -->
<script src="<?php echo BASE_URI .'assets/';?>plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>

<!-- AdminLTE App -->
<script src="<?php echo BASE_URI .'assets/';?>dist/js/app.min.js"></script>
<!-- Custom Script -->
<script>
    var server = "<?php echo $_SERVER['HTTP_HOST'];?>";
    var client = "<?php echo $_SERVER['REMOTE_ADDR'];?>";
    var loginID = "<?php echo (isset($_SESSION['id']))? $_SESSION['id']:'';?>";
    var role = "<?php echo (isset($_SESSION['role']))? $_SESSION['role']:'';?>";
    var base_uri = "<?php echo BASE_URI; ?>";
    var currency = "<?php echo CURRENCY; ?>";
</script>
<script src="<?php echo BASE_URI ;?>modules/dashboard.js"></script>
<script src="<?php echo BASE_URI ;?>modules/page.js"></script>

<?php
/* load required modules controller */
if ($view == 'reporting.php'){
    echo '<script src="'. BASE_URI .'modules/reporting.js"></script>';
    echo '<script src="'. BASE_URI .'modules/charts.js"></script>';
}

if ($view == 'draw_engine.php'){
    echo '<script src="'. BASE_URI .'modules/draws_engine.js"></script>';
}
?>
</body>
</html>

