<?php
/**
 * Created by PhpStorm.
 * User: Mecxi
 * Date: 12/4/2017
 * Time: 9:28 PM
 * processing public payment requests
 *
 */

require_once('../config.php');

/* fetch query string parameters */
$query_params = $_GET;

//echo '<pre>'. print_r($query_params, true). '</pre>';

/* check for required parameters */
if (isset($query_params) && isset($query_params['request']) && isset($query_params['accesscode'])){
    $accesscode = isset($query_params['accesscode']) ? $query_params['accesscode'] : null;
    $appname = 'public';
    $msisdn = isset($query_params['msisdn']) ? $query_params['msisdn'] : null;
    $productno = isset($query_params['productno']) ? $query_params['productno'] : null;
    $amount = isset($query_params['amount']) ? $query_params['amount'] : null;
    $type = $query_params['request'] == 'payment' ? '1' : ($query_params['request'] == 'deposit' ? '2' : null);
} else {
    echo json_encode(array('error'=>restapi::custom_message_app(0)), true);
}

?>

<script src="<?php echo BASE_URI .'assets/';?>plugins/jQuery/jQuery-2.2.0.min.js"></script>
<!-- initialise custom variables -->
<!-- Custom Script -->
<script>
    var server = "<?php echo $_SERVER['HTTP_HOST'];?>";
    var request_type = "<?php echo $type; ?>";
    var authcode = "<?php echo $accesscode; ?>";
    var appname = "<?php echo $appname; ?>";
    var msisdn = "<?php echo $msisdn; ?>";
    var productno = "<?php echo $productno; ?>";
    var amount = "<?php echo $amount; ?>";

    $.ajax({
        url: 'http://'+server+'/gateway/payment/',
        type: 'POST',
        data: JSON.stringify({
            type:  request_type == '1' ? 'paymentRequest' : 'depositRequest',
            accesscode: authcode,
            appname: appname,
            parameters: {
                accno: '1000',
                msisdn: msisdn,
                productno: productno,
                amount: amount
            }
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function(result) {
            // do something with the result
            //alert(JSON.stringify(result));
            if (result.error){
                document.write(JSON.stringify(result.error));
            } else {
                document.write(JSON.stringify(result.success));
            }
        },
        error: function(){
            // Can't reach the resource
            document.write('Error connecting to the server. Please check your computer is properly connected to the internet or if the problem persists contact dev');
        }
    });

</script>
