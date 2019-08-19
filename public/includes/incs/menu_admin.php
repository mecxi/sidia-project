<ul class="sidebar-menu">
    <li class="header">MAIN NAVIGATION</li>
    <!-- Dashboard -->
    <li><a href="<?php echo BASE_URI; ?>#"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>

    <!-- Manage Folder -->
    <li class="treeview">
        <a href="#">
            <i class="fa fa-laptop"></i> <span>Manage</span> <i class="fa fa-angle-left pull-right"></i>
        </a>
        <ul class="treeview-menu">
            <!-- Campaign Service -->
            <li class="active"><a href="<?php echo BASE_URI; ?>manage/services/"><i class="fa fa-circle-o text-aqua"></i> Services</a></li>
            <!-- Campaign Draw -->
            <li class="active"><a href="<?php echo BASE_URI; ?>manage/draws/"><i class="fa fa-circle-o text-aqua"></i> Draws</a></li>
            <!-- Subscriptions Management -->
            <li><a href="<?php echo BASE_URI; ?>manage/subscriptions/"><i class="fa fa-circle-o"></i> Subscriptions</a></li>
            <li id="side_bar_subs">
                <!-- Users Management -->
                <a href="<?php echo BASE_URI; ?>#"><i class="fa fa-circle-o"></i> Users <i class="fa fa-angle-left pull-right"></i></a>
                <ul class="treeview-menu">
                    <li><small class="label pull-right bg-blue">0</small><a href="<?php echo BASE_URI; ?>subscribers/new/"><i class="fa fa-circle-o"></i>New</a></li>
                    <li><small class="label pull-right bg-green">0</small><a href="<?php echo BASE_URI; ?>subscribers/active/"><i class="fa fa-circle-o"></i>Active</a></li>
                    <li><small class="label pull-right bg-red">0</small><a href="<?php echo BASE_URI; ?>subscribers/inactive/"><i class="fa fa-circle-o"></i>Inactive</a></li>
                    <li><small class="label pull-right bg-yellow">0</small><a href="<?php echo BASE_URI; ?>subscribers/members/"><i class="fa fa-circle-o"></i>Members</a></li>
                </ul>
            </li>
        </ul>
    </li>

    <!-- Reporting -->
    <?php if (in_array($_SESSION['role'], array('1', '2', '6'))){
            echo '
            <li class="treeview">
                <a href="#">
                    <i class="fa fa-pie-chart"></i> <span>Reporting</span> <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu" id="reporting"></ul>
            </li>
            ';
        }
    ?>


    <!-- Utilities -->
    <li class="treeview">
        <a href="#">
            <i class="fa fa-cubes"></i> <span>Draw Engine</span> <i class="fa fa-angle-left pull-right"></i>
        </a>
        <ul class="treeview-menu" id="draw-engine"></ul>
    </li>
</ul>