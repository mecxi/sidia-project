/**
 * Billing report
 * Created by Mecxi on 3/21/2017.
 */

/* determine the service report request */
var uri_array = current_page.split('/');
var service_id = (typeof uri_array == 'object') ? uri_array.slice(-1).pop() : null;

/* initialise required variables */
var new_today = $('[custom_box="total_new_day"] > div > h3');
var total_subs = $('[custom_box="total_subs"] > div > h3');
var unsub_today = $('[custom_box="total_unsubs_day"] > div > h3');
var unsub_total = $('[custom_box="total_unsubs"] > div > h3');
var bill_today = $('[custom_box="total_bills_day"] > div > h3');
var bill_totals = $('[custom_box="total_bills"] > div > h3');

/* get services billing report */
function get_billing_factory(){

    if (service_id == null){
        alert_modal('An error has occurred fetching billing report. Please report error to the system administrator');
    } else {
        /* process dashboard log */
        $.ajax({
            url: 'http://'+server+'/rest-api/report/billing/',
            type: 'POST',
            data: JSON.stringify({
                type: 'dashboard',
                service: service_id
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                //alert(JSON.stringify(result));
                if (result.error){
                    alert_modal(result.error, 3);
                } else {
                    new_today.text(result.total_new_day);
                    total_subs.text(result.total_subs);
                    unsub_today.text(result.total_unsubs_day);
                    unsub_total.text(result.total_unsubs);
                    bill_today.text(currency+' '+result.total_bills_day);
                    bill_totals.text(currency+' '+result.total_bills);
                }
            },
            error: function(){}
        });

        /* process table billing report */
        $.ajax({
            url: 'http://'+server+'/rest-api/report/billing/',
            type: 'POST',
            data: JSON.stringify({
                type: 'table',
                service: service_id
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                //alert(JSON.stringify(result));
                if (result.error){
                    alert_modal(result.error, 3);
                } else {
                 $('#billing_report').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "day" },
                            { "data": "date" },
                            { "data": "total_new_day" },
                            { "data": "total_unsubs_day" },
                            { "data": "total_play_rate"},
                            { "data": "total_subs" },
                            { "data": "total_day_bills" },
                            { "data": "target_day_bills" },
                            { "data": "rate_day_bills" },
                            { "data": "repeat_bills" },
                            { "data": "total_overall_bills" }
                        ],
                     order: [1, ["desc"]]
                    });
                }
            },
            error: function(){}
        });
    }
}

get_billing_factory();

/* Process printing request */
function request_printing_report(format, service_id){
    var form = document.createElement("form");
    form.setAttribute("method", 'POST');
    form.setAttribute("action", 'http://'+server +'/public/modules/job_processor.php');
    var hiddenField = document.createElement("input");
    hiddenField.setAttribute("type", "hidden");
    hiddenField.setAttribute("name", 'job');
    hiddenField.setAttribute("value", format);

    var hiddenFieldOpt = document.createElement("input");
    hiddenFieldOpt.setAttribute("type", "hidden");
    hiddenFieldOpt.setAttribute("name", 'serviceID');
    hiddenFieldOpt.setAttribute("value", service_id);

    form.appendChild(hiddenField);
    form.appendChild(hiddenFieldOpt);

    document.body.appendChild(form);
    form.submit();
}







