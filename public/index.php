<?php
session_start();
/**
 * @author: Mecxi Musa
 * This page includes the configuration file,
 * the templates, and any content-specific modules.
 */
require_once('../config.php');

/* check if logout is requested */
if (isset($_GET['logout'])){
    /* unset global variables */
    unset($_GET);
    /* verify logout request in order to close session */
    header('location:./modules/session_logger.php?mode=end_verify');
    exit();
}


/* check a user has a active session */
if (!isset($_SESSION['fullname'])){
    header('location:'. BASE_URI.'login/');
    exit();
}

/* validate the incoming view page request */
$page_requested = (isset($_GET['view'])) ? $_GET['view'] : (isset($_POST['view']) ? $_POST['view'] : null);

/* initialise page fields properties */
$title = '';
$logo_lg = '<img src="'. BASE_URI .'assets/dist/img/profile/'. COMPANY_LOGO.'" width="20%" height="20%" alt="Company Logo">';
$logo_mini = '<img src="'. BASE_URI .'assets/dist/img/profile/'. COMPANY_LOGO.'" width="50%" height="50%" alt="Company Logo">';
$page_Header = '';
$page_desc = '';
$page_icon = '<i class="fa fa-dashboard"></i> Level';
$page_subs_desc = '';


/* determine the viewing page request */
switch($page_requested){
    /*Manage/Subscriptions Menu */
    case 'subscriptions':
        $view = 'subscriptions.php';
        $title = 'Manage | Subscriptions';
        $page_Header = 'Subscriptions';
        $page_desc = 'Request';
        $page_icon = '<i class="fa fa-server"></i> Subscriptions';
        $page_subs_desc = 'Request';
        break;
    case 'services':
    case 'draws':
    case 'addservice':
    case 'addDrawEngine':
        $view = 'services.php';
        $title = ($page_requested == 'services') ? 'Manage | Services' : (
            ($page_requested == 'addservice') ? 'Manage | Add Service' : (
            ($page_requested == 'draws') ? 'Manage | Draws': 'Manage | Add Draw Engine'
            )
        );
        $page_Header = 'Services';
        $page_desc = ($page_requested == 'services') ? 'Management': (
            ($page_requested == 'addservice') ? 'Campaign Creation' : (
            ($page_requested == 'draws') ? 'Draws' :'Draw Engine Creation'
            )
        );
        $page_icon = '<i class="fa fa-tasks"></i> Services';
        $page_subs_desc = ($page_requested == 'services') ? 'Management' : (
            ($page_requested == 'addservice') ? 'Campaign Creation' : (
            ($page_requested == 'draws') ? 'Draws' :'Draw Engine Creation'
            )
        );
        break;
    /* Subscribers Menu */
    case 'new':
        $view = 'users_new.php';
        $title = 'Subscribers | New';
        $page_Header = 'Subscribers';
        $page_desc = 'New';
        $page_icon = '<i class="fa fa-server"></i> Subscribers';
        $page_subs_desc = 'New';
        break;
    case 'active':
        $view = 'users_active.php';
        $title = 'Subscribers | Active';
        $page_Header = 'Subscribers';
        $page_desc = 'Active';
        $page_icon = '<i class="fa fa-server"></i> Subscribers';
        $page_subs_desc = 'Active';
        break;
    case 'inactive':
        $view = 'users_inactive.php';
        $title = 'Subscribers | Inactive';
        $page_Header = 'Subscribers';
        $page_desc = 'Inactive';
        $page_icon = '<i class="fa fa-server"></i> Subscribers';
        $page_subs_desc = 'Inactive';
        break;
    case 'members':
        $view = 'users_members.php';
        $title = 'Subscribers | Members';
        $page_Header = 'Subscribers';
        $page_desc = 'Members';
        $page_icon = '<i class="fa fa-server"></i> Subscribers';
        $page_subs_desc = 'Members';
        break;
    /* Tools Menu */
    case 'draw-engine':
        if (isset($_GET['id'])){
            $current_service_draw = new service_draw($_GET['id']);
            $view = 'draw_engine.php';
            $title = 'Draw Engine | '. $current_service_draw->name;
            $page_Header = 'Draw Engine';
            $page_desc = $current_service_draw->name;
            $page_icon = '<i class="fa fa-cubes"></i> Draw Engine';
            $page_subs_desc = $current_service_draw->name;
        } else {
            $view = 'dashboard.php';
            $title = 'Dashboard | Welcome';
            $page_Header = 'Dashboard';
            $page_desc = 'Welcome';
            $page_icon = '<i class="fa fa-dashboard"></i> Dashboard';
            $page_subs_desc = 'Welcome';
        }
        break;
    /* Profile */
    case 'profile':
        $view = 'profile.php';
        $title = 'User | Profile';
        $page_Header = 'User';
        $page_desc = 'Profile';
        $page_icon = '<i class="fa fa-user"></i> User';
        $page_subs_desc = 'profile';
        break;
    /* reporting */
    case 'reporting':
        if (isset($_GET['id'])){
            $current_service = new services($_GET['id']);
            $view = 'reporting.php';
            $title = 'Billing | '. $current_service->name;
            $page_Header = 'Billing';
            $page_desc = $current_service->name;
            $page_icon = '<i class="fa fa-pie-chart"></i> Report';
            $page_subs_desc = $current_service->name;
        } else {
            $view = 'dashboard.php';
            $title = 'Dashboard | Welcome';
            $page_Header = 'Dashboard';
            $page_desc = 'Welcome';
            $page_icon = '<i class="fa fa-dashboard"></i> Dashboard';
            $page_subs_desc = 'Welcome';
        }
        break;
    case 'bulkSMS':
        $view = 'bulk_sms.php';
        $title = 'Bulk | SMS';
        $page_Header = 'Services';
        $page_desc = 'BulkSms';
        $page_icon = '<i class="fa fa-envelope-o"></i> Bulk';
        $page_subs_desc = 'Sms';
        break;
    default:
        /* redirect user member to profile page */
        if ($_SESSION['role'] == 3){
            header('location:'. BASE_URI .'users/profile/');
        }

        /* redirect user customer care to subscription page */
        if ($_SESSION['role'] == 4){
            header('location:'. BASE_URI .'manage/subscriptions/');
        }
        /* redirect user for bulk to bulk page */
        if ($_SESSION['role'] == 7){
            header('location:'. BASE_URI .'services/bulkSMS/');
        }

        $view = 'dashboard.php';
        $title = 'Dashboard | Welcome';
        $page_Header = 'Dashboard';
        $page_desc = 'Welcome';
        $page_icon = '<i class="fa fa-dashboard"></i> Dashboard';
        $page_subs_desc = 'Welcome';
        break;
}

/* if the view doesn't exist redirect to dashboard */
if (!file_exists('./views/'. $view)){
    $view = 'dashboard.php';
}

/* layout requested views */
include_once('./includes/header.php');
include_once('./includes/sidebar.php');
include_once('./views/'.$view);
include_once('./includes/footer.php');
