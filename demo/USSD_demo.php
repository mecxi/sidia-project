<?php
session_start();
require_once('../config.php');

?>
<html>
    <head><title>Service Interactive Demo</title></head>
<body>
<span>&copy; DevApps</span>
<h2>Service Interactive Demo</h2>
<div id="display_int" style="border: 1px solid #C0C0C0; width:500px; min-height:200px; background-color: black; color:white;">


</div>
<br><br>
<label>Select a Service: </label>
<select id="services"><option></option></select>
<br><br>
    <label>Select a User: </label>
    <select id="msisdn_trivia"></select>
    <br><br>
    <label>Reply: </label><input type="text" id="answer"><br><br>
    <label>Session</label>
    <select id="session">
        <option value="start">start</option>
        <option value="continue">continue</option>
        <option value="end">End</option>
    </select><br><br>
    <button id="process_trivia"> SEND </button>
    <hr>
<h2>USSD Service Subscription Interactive</h2>
<div id="display_sub" style="border: 1px solid #C0C0C0; width:500px; min-height:200px; background-color: black; color:white;">
</div><br><br>
<span>Reply 0 to End the session</span><br><br>
<label>MSISDN: </label>
<select id="msisdn_subscription"></select>
<br><br>
<label>Reply: </label><input type="text" id="answer_sub"><br><br>
<button id="process_subscription"> SEND </button>

<script src="lib/jquery/jquery-1.11.2.min.js"></script>
<script>
    var server = "<?php echo $_SERVER['HTTP_HOST'];?>";

    /* handle ussd Glan Squad Trivia interaction */
    $('#process_trivia').click(function(){
        //alert($('#session option:selected').val());
        var answer = $('#answer');
        var session = $('#session option:selected').val();
        var msisdn = $('#msisdn_trivia option:selected').val();
        var request_type = 'trivia';
        var service_id = $('#services').find('option:selected').val();
        $.ajax({
            type: "POST",
            url: "ussd_model.php",
            data: "msisdn="+ msisdn+"&answer="+ answer.val() +"&session="+ session+"&request_type="+request_type+"&service_id="+service_id,
            success: function(response){
               $('#display_int').html(response);
            }
        });
        /* clear the answer box */
        answer.val('');
    });

    /* handle USSD subscription interaction */
    $('#process_subscription').click(function(){
        //alert($('#session option:selected').val());
        var answer = $('#answer_sub').val();
        var msisdn = $('#msisdn_subscription option:selected').val();
        var request_type = 'subscription';
        $.ajax({
            type: "POST",
            url: "ussd_model.php",
            data: "msisdn="+ msisdn+"&answer="+ answer+"&request_type="+request_type,
            success: function(response){
                $('#display_sub').html(response);
            }
        });
        /* clear the answer box */
        $('#answer_sub').val('');
    });

    function populate_services(){
        var services_box = $('#services');
        /* get totals subscribers */
        $.ajax({
            url: 'http://'+server+'/rest-api/tools/services/list/',
            type: 'POST',
            data: JSON.stringify({
                category: 'services',
                type: 'all',
                service_id: 0
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(response) {
                if (response.error){
                    alert(response.error);
                } else {
                    var options = response.data;
                    var services_list = '<option></option>';
                    for(var i=0; i < options.length; ++i){
                        services_list +='<option value="'+options[i].id+'">'+ options[i].name+'</option>'
                    }
                    services_box.html(services_list);
                }
            },
            error: function(){
                alert('Populate services - Error connecting to the server');
            }
        });
    }

    function populate_users(service_id){
        var users_box = $('#msisdn_trivia');
        /* get totals subscribers */
        $.ajax({
            url: 'http://'+server+'/rest-api/tools/services/list/',
            type: 'POST',
            data: JSON.stringify({
                category: 'users',
                type: 2,
                service_id: service_id
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(response) {
                if (response.data != null){
                    var options = response.data;
                    var msisdn = '<option></option>';
                    for(var i=0; i < options.length; ++i){
                        msisdn += '<option value="'+options[i].msisdn+'">ID:'+ options[i].id + ' | '+options[i].msisdn+'</option>';
                    }
                    users_box.html(msisdn);
                }
            },
            error: function(){
                alert('Populate users - Error connecting to the server');
            }
        });
    }

    /* populate services */
    populate_services();

    /* populate users */
    $('#services').on('change', function(){
        var service_id = $(this).find('option:selected').val();
        if (service_id.length > 0){
            $('#msisdn_trivia').html('<option></option>');
            populate_users(service_id);
        }
    })

</script>
</body>
</html>

