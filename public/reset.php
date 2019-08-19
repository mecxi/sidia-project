<?php
session_start();
/**
 * @customised by Mecxi Musa
 * date: 2016-08-12
 */
require_once('../config.php');
if (isset($_SESSION['fullname']) && (!isset($_GET['mode']) && $_GET['mode'] != 'session_end')){
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
    <title><?php echo COMPANY_NAME; ?> | Reset</title>
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

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="<?php echo BASE_URI ?>#"><b><?php echo COMPANY_NAME; ?></b><img class="img-circle img-bordered-sm" src="<?php echo BASE_URI .'assets/dist/img/profile/'. COMPANY_LOGO; ?>" width="20%" height="20%" alt="User Image"></a>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
        <p class="login-box-msg"><i>Please reset your password below</i></p>

        <form id="reset-form" novalidate>
            <input type="hidden" id="form_type" value="0">
            <div class="form-group has-feedback">
                <input type="text" class="form-control" name="phone" placeholder="Phone Number">
                <span class="glyphicon glyphicon-phone form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="first_pass" placeholder="New Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="second_pass" placeholder="Retype New Password">
                <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback" style="display: none">
                <input type="text" class="form-control" name="token" placeholder="Enter token verification">
                <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    &nbsp;
                </div>
                <!-- /.col -->
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Reset</button>
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
<!--        <a href="--><?php //echo BASE_URI .'register/'?><!--" class="text-center"><i>Register a new membership</i></a><br>-->
        <a href="<?php echo BASE_URI .'login/'?>" class="text-center"><i>I already have a membership</i></a>
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
    /* define current server ip */
    var server = "<?php echo $_SERVER['HTTP_HOST'];?>";

    $('#reset-form').submit(function(event) {
        // prevent the default submission of the form
        event.preventDefault();

        /* initialise form elements */
        var phone = $('[name="phone"]').val();
        var first_pass = $('[name="first_pass"]').val();
        var second_pass = $('[name="second_pass"]').val();
        var token = $('[name="token"]').val();
        /* determine the current form */
        var form_type = $('#form_type').val();

        in_progress('Processing your request');

        if (form_type == '0') {
            /* submitting a reset form */
            /* validate form */
            if (validate_inputs(phone, first_pass, second_pass)) {
                $.ajax({
                    url: 'http://'+server+'/rest-api/login/reset/',
                    type: 'POST',
                    data: JSON.stringify({
                        phone: phone,
                        password: first_pass
                    }),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function (result) {
                        if (result.error) {
                            window.setTimeout(report_error, 700, result.error);
                        } else {
                            if (result.result.length != 0) {
                                window.setTimeout(report_success, 700, result.result, 0);
                            }
                        }
                    },
                    error: function () {
                        window.setTimeout(report_error, 200, 'Error connecting to the server. Please check your computer is connected to the internet');
                    }
                });
            }

        } else {
            /* submit token to validate changes */
            $.ajax({
                url: 'http://'+server+'/rest-api/login/auth-code/',
                type: 'POST',
                data: JSON.stringify({
                    phone: phone,
                    token: token
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function (result) {
                    if (result.error) {
                        window.setTimeout(report_error, 700, result.error);
                    } else {
                        if (result.result.length != 0) {
                            window.setTimeout(report_success, 700, result.result, 1);
                        }
                    }
                },
                error: function () {
                    window.setTimeout(report_error, 200, 'Error connecting to the server. Please check your computer is connected to the internet');
                }
            });
        }
    });


        /* validate forms values */
        function validate_inputs(phone_no, first_pass, second_pass){
            var error_report = 'Error processing the form. Please review:<br>';
            var is_complete = true;

            /* check phone number */
            if (isNaN(phone_no)){
                error_report += ' - Not a valid phone number - ';
                is_complete = false;
            } else {
                if (phone_no.length != 10){
                    error_report += ' - Phone must be 10 digit';
                    is_complete = false;
                }
            }

            /* check password */
            if (first_pass !== second_pass || first_pass.length == 0 || second_pass.length == 0){
                error_report += ' - Password does not match';
                is_complete = false;
            }

            if (is_complete == false){
                window.setTimeout(report_error, 700, error_report);
                return false;
            } else {
                return true;
            }
        }

        /* disable inputs to enter the token */
        function disable_inputs() {
            $('[name="phone"]').prop('disabled', true);
            $('[name="first_pass"]').prop('disabled', true);
            $('[name="second_pass"]').prop('disabled', true);
            $('[style="display: none"]').css({'display':'block'});
            $('button').text('Verify');
            /* alter the form name for token verification */
            $('#form_type').val('1');
        }
        /* disable the button */
        function disable_btn() {
            $('button').prop('disabled', true);
        }

        /* redirect to the login page */
        function redirect(){
            window.location.assign('http://'+server+'/public/login/');
        }

        /* in processing ... */
        function in_progress(process){
            var report = $('#report');
            report.parent().css({'color':'green'});
            report.text(process + ' ...');
        }

        function report_error(val){
            var report = $('#report');
            report.parent().css({'color':'red'});
            report.html(val);
        }
        function report_success(val, opt){
            var report = $('#report');
            report.parent().css({'color':'green'});
            /* customised the report to reset requests */
            var val_modified = val.replace('register', 'to confirm changes');
            report.html(val_modified);
            if (opt == 0){
                /* disable inputs for to request a token */
                disable_inputs();
            } else if (opt == 1) {
                disable_btn();
                window.setTimeout(redirect, 2000);
            }
        }

</script>
</body>
</html>

