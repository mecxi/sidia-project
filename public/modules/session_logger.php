<?php
session_start();
/**
 * @customised by Mecxi Musa
 * date: 2016-08-12
 *The login page will pass after a successful verification, user session details
 * that we need to redirect the user to the dashboard
 */
require_once('../../config.php');

/* check if the page is been requested directly */
if (isset($_POST['mode']) && $_POST['mode'] == 'session_verified'){
    $_SESSION['id'] = (isset($_POST['id'])) ? $_POST['id'] : null;
    $name = (isset($_POST['name'])) ? $_POST['name'] : '';
    $surname = (isset($_POST['surname'])) ? $_POST['surname'] : '';
    $_SESSION['fullname'] = $name .' '. $surname;
    $_SESSION['phone'] = (isset($_POST['phone'])) ? $_POST['phone'] : null;
    $_SESSION['role'] = (isset($_POST['role'])) ? $_POST['role'] : null;
    $_SESSION['type'] = (isset($_POST['type']))? $_POST['type'] : null;
    $_SESSION['date_created'] = (isset($_POST['date_created'])) ? $_POST['date_created'] : 'Unknown';
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
} else if (isset($_GET['mode']) && $_GET['mode'] == 'end_verify'){
    /* User has ended the session, redirect to the login page */
    header('location:'. BASE_URI.'login/?mode=session_end');
} else {
    /* redirect to the dashboard */
    header('location:'. BASE_URI);
    exit();
}


