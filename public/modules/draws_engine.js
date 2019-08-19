/**
 * Created by Mecxi on 9/7/2017.
 * draw engine controller
 */

/* determine the current draw service */
var uri_array = current_page.split('/');
var service_draw_id = (typeof uri_array == 'object') ? uri_array.slice(-1).pop() : null;
var draw_table = null;
var winner_table = null;

/* preview player service draw */
get_current_date_range('current', current_draw_type_id(service_draw_id));
get_players_preview_service_winners('current');

/* initialise select date event */
$('[name="service_draw_range_date"]').daterangepicker();
$('[name="service_draw_range_date"]').on('apply.daterangepicker', {id: service_draw_id}, function(e, picker){
    /*update the service draw range */
    get_current_date_range(picker.startDate.format('YYYY-MM-DD') + '|'+  picker.endDate.format('YYYY-MM-DD'), current_draw_type_id(e.data.id));
    setTimeout(function(){ get_players_preview_service_winners(picker.startDate.format('YYYY-MM-DD') + '|'+  picker.endDate.format('YYYY-MM-DD'), true);}, 500);
});


/* initialise current service draw players preview */
function redraw_player_preview(data){
    draw_table.clear().draw();
    draw_table.rows.add(data); // Add new data
    draw_table.columns.adjust().draw(); // Redraw the DataTable
}

/* initialise current winners players preview */
function redraw_winners_perview(data){
    winner_table.clear().draw();
    winner_table.rows.add(data); // Add new data
    winner_table.columns.adjust().draw(); // Redraw the DataTable
}

/* Preview current player pool for the current service draw */
function get_players_preview_service_winners(date_range, redraw){
    loader_body_notif('Processing your request. Please wait ...');
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/draw-engine/preview/',
        type: 'POST',
        data: JSON.stringify({
            service_draw_id: service_draw_id,
            date_range: date_range == undefined ? 'current' : date_range
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function(result) {
            if (redraw == undefined){
                draw_table = $('#player_preview').DataTable({
                    "data": result.data,
                    "columns": [
                        { "data": "msisdn" },
                        { "data": "service" },
                        { "data": "score" },
                        { "data": "entries" }
                    ],
                    order: [2, ["desc"]]
                });
            } else {
                if (result.data) {
                    redraw_player_preview(result.data);
                } else {
                    redraw_player_preview([]);
                }
            }
        },
        error: function(){
            alert_modal('Enable to Service Draw data players. Please make sure your computer is connected to the internet or the service is unavailable', 3);
        }
    });

    /* get service Winners related for the given date */
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/winners/',
        type: 'POST',
        data: JSON.stringify({
            service_draw_id: service_draw_id,
            date_range: date_range == undefined ? 'current' : date_range
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async : false,
        success: function(result) {
            if (redraw == undefined){
                winner_table = $('#players_winners').DataTable({
                    "data": result.data,
                    "columns": [
                        { "data": "msisdn" },
                        { "data": "score" },
                        { "data": "entry" },
                        { "data": "selected" },
                        { "data": "by" },
                        { "data": "date" },
                        { "data": "notify" }


                    ],
                    order: [5, ["desc"]]
                });
            } else {
                if (result.data) {
                    redraw_winners_perview(result.data);
                } else {
                    redraw_winners_perview([]);
                }
            }
        },
        error: function(){
            alert_modal('Enable to fetch Draw winners. Your computer is not connected to the internet or the server is unavailable', 3);
        }
    });
    setTimeout(function(){
        loader_body_notif();
    }, 1000);
}

/* get current draw_type_id for the given service_draw_id */
function current_draw_type_id(service_draw_id){
    for(var key in draw_engine_list){
        if (draw_engine_list.hasOwnProperty(key)){
            if (draw_engine_list[key].id == service_draw_id){
                return draw_engine_list[key].draw_type_id;
            }
        }
    }
    return 0;
}

/* process draw selection */
function process_draw_selection(type){
    /* alert for a draw top triggered */
    if (type == 1){
        var r = window.confirm('You are about to select winners as a top scorer, Do you want to continue?');
        if (r == true){
            loader_body_notif('Processing your request. Please wait ...');
            $.ajax({
                url: 'http://'+server+'/rest-api/tools/draw-engine/raffle/',
                type: 'POST',
                data: JSON.stringify({
                    service_draw_id: service_draw_id,
                    date_range: $('[custom="range"]').text(),
                    type: type,
                    loginID: loginID,
                    remote_address: client
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    if (result.result){
                        alert_modal(result.result, 1);
                        //force_page_refresh();
                    } else {
                        alert_modal(result.error, 2);
                    }
                },
                error: function(){
                    alert_modal('Enable to process your request. Your computer is not connected to the internet or the server is unavailable', 3);
                }
            });
            setTimeout(function(){
                loader_body_notif();
            }, 1000);
        }
    } else {
        loader_body_notif('Processing your request. Please wait ...');
        $.ajax({
            url: 'http://'+server+'/rest-api/tools/draw-engine/raffle/',
            type: 'POST',
            data: JSON.stringify({
                service_draw_id: service_draw_id,
                date_range: $('[custom="range"]').text(),
                type: type,
                loginID: loginID,
                remote_address: client
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                if (result.result){
                    alert_modal(result.result, 1);
                    //force_page_refresh();
                } else {
                    alert_modal(result.error, 2);
                }
            },
            error: function(){
                alert_modal('Enable to process your request. Your computer is not connected to the internet or the server is unavailable', 3);
            }
        });
        setTimeout(function(){
            loader_body_notif();
        }, 1000);
    }
}

/* return current week range */
function get_current_date_range(date_range, draw_type_id, display){
    var obj = {'start_date': null, 'end_date':null};
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/draw-engine/range/',
        type: 'POST',
        data: JSON.stringify({
            date_range : date_range,
            draw_type_id : draw_type_id
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async : false,
        success: function(result) {
            if (result.error){
                alert_modal(result.error, 2);
            } else {
                obj.start_date = result.format1.start_date;
                obj.end_date = result.format1.end_date;
            }

            if (display == undefined){
                $('[custom="range"]').text(draw_type_id == 1 ? obj.start_date : obj.start_date + ' | ' + obj.end_date);
                $('[name="service_draw_range_date"]').val(result.format2.start_date + ' - ' + result.format2.end_date);
                return false;
            }
        },
        error: function(){
            alert_modal('Enable to process your request. Your computer is not connected to the internet or the server is unavailable', 3);
        }
    });

    return obj;
}

/* reset current winner selected */
function reset_current_winner(){
    var date_range = $('[custom="range"]').text();
    date_range = date_range.search('|') > 0 ? date_range : (current_date() == date_range ? 'current' : date_range);
    /* get user action confirmation */
    var r=confirm("Are you sure to reset current winners ?");

    if (r == true){
        $.ajax({
            url: 'http://'+server+'/rest-api/tools/draw-engine/reset/',
            type: 'POST',
            data: JSON.stringify({
                service_draw_id: service_draw_id,
                date_range : date_range,
                loginID: loginID,
                remote_address: client
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                if (result.result){
                    if (result.result){
                        alert_modal(result.result, 1);
                        force_page_refresh();
                    } else {
                        alert_modal(result.error, 2);
                    }
                } else {
                    alert_modal(result.error, 2);
                }
            },
            error: function(){
                alert_modal('Enable to process your request. Your computer is not connected to the internet or the server is unavailable', 3);
            }
        });
    }
}


/* exporting task handlers */
function request_export_engine(type){
    var date_range = $('[custom="range"]').text();
    date_range = date_range.search('|') > 0 ? date_range : (current_date() == date_range ? 'current' : date_range);
    request_printing_CSV(type, service_draw_id, date_range);
}

/* notify winners on target date */
function request_notify_winners(){
    var date_range = $('[custom="range"]').text();
    date_range = date_range.search('|') > 0 ? date_range : (current_date() == date_range ? 'current' : date_range);
    /* get user action confirmation */
    var r=confirm("Are you sure to notify winners? Once notified current draw cannot be reset");

    if (r == true){
        $.ajax({
            url: 'http://'+server+'/rest-api/tools/draw-engine/notify/',
            type: 'POST',
            data: JSON.stringify({
                service_draw_id: service_draw_id,
                date_range : date_range
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                if (result.result){
                    if (result.result){
                        alert_modal(result.result, 1);
                        force_page_refresh();
                    } else {
                        alert_modal(result.error, 2);
                    }
                } else {
                    alert_modal(result.error, 2);
                }
            },
            error: function(){
                alert_modal('Enable to process your request. Your computer is not connected to the internet or the server is unavailable', 3);
            }
        });
    }

}


/* force page refresh */
function force_page_refresh(param){
    if (param == undefined){
        setTimeout(function(){
            window.location.assign('http://'+server+window.location.pathname);
        }, 5000);
    } else {
        window.location.assign('http://'+server+window.location.pathname);
    }
}

function current_date(){
    var date = new Date();
    return date.getFullYear() + '-' +checkTime(date.getMonth() + 1) + '-'+checkTime(date.getDate());
}