<?php
session_start();
/**
 * @customised by Mecxi Musa
 * date: 2016-08-12
 */
require_once('../config.php');
if (isset($_SESSION['fullname']) && (isset($_GET['mode']) && $_GET['mode'] != 'session_end')){
    /* redirect to the dashboard if user is has a active session */
    header('location:'. BASE_URI);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo COMPANY_NAME; ?> | Log in</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="<?php echo BASE_URI .'assets/'?>bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo BASE_URI .'assets/'?>dist/css/AdminLTE.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo BASE_URI .'assets/'?>plugins/iCheck/square/blue.css">

    <!-- custom css -->
    <link rel="stylesheet" href="<?php echo BASE_URI .'assets/'?>custom/css/dashboard.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div id="loader"></div>
    <div class="login-logo">
        <a href="<?php echo BASE_URI ?>#"><b><?php echo COMPANY_NAME; ?></b><img class="img-circle img-bordered-sm" src="<?php echo BASE_URI .'assets/dist/img/profile/'. COMPANY_LOGO; ?>" width="20%" height="20%" alt="User Image"></a>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
        <p class="login-box-msg">Sign in to start your session</p>

        <form id="login-form" novalidate>
            <input type="hidden" id="form_type" value="0">
            <div class="form-group has-feedback">
                <input type="email" class="form-control" placeholder="Email or Phone no (e.g. 083..)">
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" placeholder="Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    <div class="checkbox icheck">
                        <label>
                            <input type="checkbox"> Remember Me
                        </label>
                    </div>
                </div>
                <!-- /.col -->
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
                </div>
                <!-- /.col -->
            </div>
        </form>
        <div class="social-auth-links text-center">
            <p style="color:red"><i id="report"></i></p>
        </div>
        <!--
        <div class="social-auth-links text-center">
            <p>- OR -</p>
            <a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i> Sign in using
                Facebook</a>
            <a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i> Sign in using
                Google+</a>
        </div>
        <!-- /.social-auth-links -->

        <a href="<?php echo BASE_URI. 'reset/'?>"><i>I forgot my password</i></a><br>
<!--        <a href="--><?php //echo BASE_URI .'register/'?><!--" class="text-center"><i>Register a new membership</i></a>-->
    </div>
    <!-- /.login-box-body -->
</div>
<!-- /.login-box -->

<!-- jQuery 2.2.0 -->
<script src="<?php echo BASE_URI .'assets/'?>plugins/jQuery/jQuery-2.2.0.min.js"></script>
<!-- Bootstrap 3.3.5 -->
<script src="<?php echo BASE_URI .'assets/'?>bootstrap/js/bootstrap.min.js"></script>
<!-- iCheck -->
<script src="<?php echo BASE_URI .'assets/'?>plugins/iCheck/icheck.min.js"></script>
<script>
    $(function () {
        $('input').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue',
            increaseArea: '20%' // optional
        });
    });

    /* process login request */
    //get report
    var report = $('[style="color:red"] i');
    var account_type = 'Member';
    var gif_loader = $('#loader');

    /* define current server ip */
    var server = "<?php echo $_SERVER['HTTP_HOST'];?>";
    var client = "<?php echo $_SERVER['REMOTE_ADDR'];?>";
    var dail_code = "<?php echo DIAL_CODE; ?>";
    /* for closing open session, initialise loginID */
    var close_loginID = null;

    $('#login-form').submit(function(event){
        gif_loader.show();
        // prevent the default submission of the form
        event.preventDefault();
        /* determine the current form */
        var form_type = $('#form_type').val();

        //collect forms entries
        var username =  $('[type="email"]').val();
        var password =  $('[type="password"]').val();
        //performs verification
        in_progress('Processing your request');

        if (form_type == '0'){
            /* performing login operation */
            $.ajax({
                url: 'http://'+server+'/rest-api/login/request/',
                type: 'POST',
                data: JSON.stringify({
                    username: username,
                    password: password,
                    remote_address: client
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    if (result.error){
                        if (result.loginID){
                            window.setTimeout(report_error, 700, result.error, result.loginID);
                        } else {
                            window.setTimeout(report_error, 700, result.error);
                        }

                    } else {
                        /* set account type */
                        switch (parseInt(result.role)){
                            case 1:
                                account_type = 'System Administrator';
                                break;
                            case 2:
                                account_type = 'Administrator';
                                break;
                            case 4:
                                account_type = 'CustomerCare';
                                break;
                            case 5:
                                account_type = 'Partner';
                                break;
                            case 6:
                                account_type = 'Admin';
                                break;
                            default:
                                account_type = 'Member';
                                break;
                        }

                        window.setTimeout(report_success, 700, 'Authenticated. Please wait...', {
                            mode:'session_verified',
                            id: result.id,
                            name: result.name,
                            surname: result.surname,
                            phone: '0'+ result.phone.substring(dail_code.length),
                            role: result.role,
                            date_created: result.date_created,
                            type: account_type
                        });
                    }
                },
                error: function(){
                    window.setTimeout(report_error, 200, 'Error connecting to the server. Please check your computer is connected to the internet');
                }
            });
        } else {
            /* closing all sessions in order to login */
            $.ajax({
                url: 'http://'+server+'/rest-api/login/cancel/',
                type: 'POST',
                data: JSON.stringify({
                    login_id: close_loginID,
                    remote_address: client,
                    priority:'high'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    if (result.error){
                        window.setTimeout(report_error, 700, 'Enable to close your session. Please try again later');
                    } else {
                        window.setTimeout(disable_close_session, 700, 'Closing all sessions has been successfull. <br>Please login');
                    }
                },
                error: function(){
                    window.setTimeout(report_error, 200,'Oops! Enable to close your session. Please check your computer is connected to the internet');
                }
            });
        }
    });

    /* end current user session */
    var end_session = "<?php echo (isset($_GET['mode'])) ? $_GET['mode'] : '';?>";
    var login_id = "<?php echo (isset($_SESSION['id'])) ? $_SESSION['id'] : ''; session_destroy()?>";

    if (end_session == 'session_end' && login_id != ''){
        //alert('end_session = '+ end_session + ' - loginID:' + login_id);
        // log end session
        $.ajax({
            url: 'http://'+server+'/rest-api/login/cancel/',
            type: 'POST',
            data: JSON.stringify({
                login_id: login_id,
                remote_address: client
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {},
            error: function(){
                window.setTimeout(report_error, 200,'Oops! Enable to close your session. Please check your computer is connected to the internet');
            }
        });
    }

    /* start user session */
    function start_session(path, params, method) {
        method = method || "post"; // Set method to post by default if not specified.

        // The rest of this code assumes you are not using a library.
        // It can be made less wordy if you use one.
        var form = document.createElement("form");
        form.setAttribute("method", method);
        form.setAttribute("action", path);

        for(var key in params) {
            if(params.hasOwnProperty(key)) {
                var hiddenField = document.createElement("input");
                hiddenField.setAttribute("type", "hidden");
                hiddenField.setAttribute("name", key);
                hiddenField.setAttribute("value", params[key]);

                form.appendChild(hiddenField);
            }
        }
        document.body.appendChild(form);
        form.submit();
    }

    /* enable closing session */
    function enable_close_session(){
        $('#form_type').val('1');
        $('[type="submit"]').text('Close All');
    }

    /* disable closing session */
    function disable_close_session(val){
        var report = $('#report');
        report.parent().css({'color':'green'});
        report.html(val);
        $('#form_type').val('0');
        $('[type="submit"]').text('Sign In');
        gif_loader.hide();
    }


    /* in processing ... */
    function in_progress(process){
        var report = $('#report');
        report.parent().css({'color':'green'});
        report.text(process + ' ...');
    }

    function report_error(val, loginID){
        var report = $('#report');
        report.parent().css({'color':'red'});
        report.html(val);
        /* check if error related to multiple session */
        if (val.match('Multiple session is not allowed')){
            enable_close_session();
            close_loginID = loginID;
            gif_loader.hide();
        }
        gif_loader.hide();
    }

    function report_success(val, params){
        var report = $('#report');
        report.parent().css({'color':'green'});
        report.html(val);
        /* set current user session */
        window.setTimeout(start_session, 700, 'http://'+server+'/public/modules/session_logger.php', params, 'post');
        gif_loader.hide();
    }
</script>
</body>
</html>

