<?php
/**
 * Testing the upload modules
 * User: Mecxi
 * Date: 4/23/2017
 * Time: 8:59 PM
 */

require_once('../config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Glam Upload Demo</title>
    <link rel="stylesheet" href="lib/bootstrap/bootstrap.min.css">
    <style>
        #display {
            color: #C0C0C0;
        }
        .log-warning {
            color: #ffab38;
        }
        .log-error {
            color: red;
        }
        .log-info {
            color: blue;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <br><br>
    <!-- fileupload -->
    <div class="row">
        <div class="col-md-3">
            <div class="input-group">
                <label class="input-group-btn">
                    <span class="btn btn-primary">Browse... <input type="file" name="file" id="file" style="display: none;" multiple=""/></span>
                </label>
                <input type="text" class="form-control" readonly="">
            </div>
        </div>
        <div class="col-md-3"><button class="btn btn-primary rounded" role="button">Upload</button></div>
    </div>
    <br><br>
    <div class="row">
        <div class="col-md-12" id="display">
        </div>
    </div>
</div>
<script src="lib/jquery/jquery-1.11.2.min.js"></script>
<script>
    var scrapper_log = $('#display');
    /* process fileupload */
    $('button:contains("Upload")').click(function(){
        var file = $('#file')[0].files[0];
        if (file == undefined){
            scrapper_log.append(current_timestamp() + '<i class="log-error">Enable to process request - no file is attached</i><br>');
        } else {
            scrapper_log.append(current_timestamp() + 'Uploading file '+file.name+' ...<br>');
        }

        if (file != undefined){
            var formData = new FormData();
            formData.append('file', file);
            formData.append("service_id", "2");
            formData.append("load_perday", "1");
            $.ajax({
                url: 'http://192.168.8.250/mtnpromo/rest-api/tools/services/upload/',
                type: "POST",
                data: formData,
                cache: false,
                processData: false,  // tell jQuery not to process the data
                contentType: false,   // tell jQuery not to set contentType
                dataType: 'json',
                success: function(result){
                    //alert(JSON.stringify(result));
                    if (result.error){
                        scrapper_log.append(current_timestamp()+ '<i class="log-error">'+result.error+'</i><br>');
                    } else {
                        scrapper_log.append(current_timestamp()+ '<i class="log-info">'+result.result+'</i><br>');
                    }
                },
                error: function(){
                    scrapper_log.append(current_timestamp()+ '<i class="log-error">Error connecting to the server</i><br>');
                }
            });
        }
    });

    /* current timestamp */
    function current_timestamp(){
        var today = new Date();
        var Y = today.getFullYear();
        var M = today.getMonth();
        var D = today.getDate();
        var h = today.getHours();
        var m = today.getMinutes();
        var s = today.getSeconds();
        return '['+Y+'-'+M+'-'+D+' '+h+':'+m+':'+s+'] ';
    }

</script>
</body>
</html>
