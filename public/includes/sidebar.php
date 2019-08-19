<?php
/**
 * @customized by Mecxi Musa
 * date:2016-08-12
 */
/* check if the page is been requested directly */
if (!defined('BASE_URI')){
    require_once('../../config.php');
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
    exit();
}
?>
<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="<?php echo BASE_URI .'assets/';?>dist/img/avatar.png" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
                <p id="profile_number"><?php echo $_SESSION['phone'];?></p>
                <!-- Status -->
                <a href="#" id="keep-alive"><i class="fa fa-circle text-success"></i> <span>Online</span></a>
            </div>
        </div>

        <!-- search form (Optional)
        <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search...">
              <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
            </div>
        </form>
        <!-- /.search form -->

        <!-- Sidebar Menu -->
        <?php if (in_array($_SESSION['role'], array('1', '2', '6'))){ include_once('incs/menu_admin.php');} ?>
        <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            <?php echo $page_Header; ?>
            <small><i><?php echo $page_desc; ?></i></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo BASE_URI; ?>#"><?php echo $page_icon; ?></a></li>
            <li class="active"><?php echo $page_subs_desc; ?></li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
