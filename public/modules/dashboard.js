/**
 * Created by Mecxi on 12/18/2016.
 */

/* Monitoring current session */
var current_session = window.setInterval(function(){
    keep_alive();
}, 60000);


/* process according to page being requested */
var current_page = window.location.pathname.replace('#', '');
/* remove the base url */
current_page = current_page.replace('/public/','');
switch (current_page){
    case 'subscribers/new/':
        get_dashboard_stats_log();
        process_subscribers_pages('new');
        break;
    case 'subscribers/active/':
        get_dashboard_stats_log();
        process_subscribers_pages('active');
        break;
    case 'subscribers/inactive/':
        get_dashboard_stats_log();
        process_subscribers_pages('inactive');
        break;
    case 'subscribers/members/':
        get_dashboard_stats_log();
        process_subscribers_pages('members');
        break;
    case 'users/profile/':
        load_user_profile();
        /* collapse menu by default for member */
        if (role == '4' || role == '5'){
            $('body').addClass('sidebar-collapse');
        }
        break;
    case 'manage/subscriptions/':
        /* prevent an infinite redirect */
        break;
    default :
        /* dashboard */
        get_dashboard_stats_log();
        /* collapse menu by default for member */
        if (role == '5'){
            $('body').addClass('sidebar-collapse');
        }
        break;
}


/********* Live dashboard Report ********/

/* get totals subs */
function get_dashboard_stats_log(){
    var total_box = $('[custom_box="totalSubs"] div h3');
    var new_box = $('[custom_box="total_new"] div h3');
    var unsub_box = $('[custom_box="total_unsub"] div h3');
    var member_box = $('[custom_box="total_member"] div h3');

    /* subscribers sidebar menu */
    var sidebar_subs = $('#side_bar_subs');
    var side_new = sidebar_subs.children('ul').children('li:nth-child(1)').children('small');
    var side_active = sidebar_subs.children('ul').children('li:nth-child(2)').children('small');
    var side_inactive = sidebar_subs.children('ul').children('li:nth-child(3)').children('small');
    var side_member = sidebar_subs.children('ul').children('li:nth-child(4)').children('small');

    /* get totals subscribers */
    $.ajax({
        url: 'http://'+server+'/rest-api/users/total-subs/',
        type: 'POST',
        data: JSON.stringify({
            type: 'sum'
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(response) {
            if (response.error){
                total_box.text('0');
            } else {
                total_box.text(response.result);
                side_active.text(response.result);
            }
        },
        error: function(){
            total_box.text('0');
        }
    });

    /* get newly subscribers for today */
    $.ajax({
        url: 'http://'+server+'/rest-api/users/total-subs/',
        type: 'POST',
        data: JSON.stringify({
            type: 'new'
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(response) {
            if (response.error){
                new_box.text('0');
            } else {
                new_box.text(response.result);
                side_new.text(response.result);
            }
        },
        error: function(){
            new_box.text('0');
        }
    });

    /* get totals unsubs */
    $.ajax({
        url: 'http://'+server+'/rest-api/users/total-subs/',
        type: 'POST',
        data: JSON.stringify({
            type: 'unsub'
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(response) {
            if (response.error){
                unsub_box.text('0');
            } else {
                unsub_box.text(response.result);
                side_inactive.text(response.result);
            }
        },
        error: function(){
            unsub_box.text('0');
        }
    });

    /* get totals unsubs */
    $.ajax({
        url: 'http://'+server+'/rest-api/users/total-subs/',
        type: 'POST',
        data: JSON.stringify({
            type: 'member'
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(response) {
            if (response.error){
                member_box.text('0');
            } else {
                member_box.text(response.result);
                side_member.text(response.result);
            }
        },
        error: function(){
            member_box.text('0');
        }
    });
}

/* control subscription pages  */
function process_subscribers_pages(page){
    switch (page){
        case 'new':
            $.ajax({
                url: 'http://'+server+'/rest-api/users/display-subs/',
                type: 'POST',
                data: JSON.stringify({
                    type:'new'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    $('#users_new').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "msisdn" },
                            { "data": "service_name" },
                            { "data": "date" }
                        ],
                        order: [2, ["desc"]]
                    });
                },
                error: function(){}
            });
            break;
        case 'active':
            /* recent subscribers requests */
            $.ajax({
                url: 'http://'+server+'/rest-api/users/display-subs/',
                type: 'POST',
                data: JSON.stringify({
                    type:'active'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    $('#users_total_last').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "msisdn" },
                            { "data": "service_name" },
                            { "data": "date" }
                        ],
                        order: [2, ["desc"]]
                    });
                },
                error: function(){}
            });

            /* last unique subscribers */
            $.ajax({
                url: 'http://'+server+'/rest-api/users/display-subs/',
                type: 'POST',
                data: JSON.stringify({
                    type:'active_all'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    $('#users_total_all').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "msisdn" },
                            { "data": "service_name" },
                            { "data": "date" }
                        ],
                        order: [2, ["desc"]]
                    });
                },
                error: function(){}
            });
            break;
        case 'inactive':
            /* 200 last recent unsubscribed users */
            $.ajax({
                url: 'http://'+server+'/rest-api/users/display-subs/',
                type: 'POST',
                data: JSON.stringify({
                    type:'inactive'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    $('#users_unsub').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "msisdn" },
                            { "data": "service_name" },
                            { "data": "date" },
                            { "data": "end_date" }
                        ],
                        order: [3, ["desc"]]
                    });
                },
                error: function(){}
            });
            /* 200 last unsubscribed with no service */
            $.ajax({
                url: 'http://'+server+'/rest-api/users/display-subs/',
                type: 'POST',
                data: JSON.stringify({
                    type:'inactive_all'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    $('#users_unsub_all').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "msisdn" },
                            { "data": "service_name" },
                            { "data": "date" },
                            { "data": "end_date" }
                        ],
                        order: [3, ["desc"]]
                    });
                },
                error: function(){}
            });
            break;
        case 'members':
            $.ajax({
                url: 'http://'+server+'/rest-api/users/display-subs/',
                type: 'POST',
                data: JSON.stringify({
                    type:'members'
                }),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(result) {
                    $('#users_members').DataTable({
                        "data": result.data,
                        "columns": [
                            { "data": "fullname" },
                            { "data": "email" },
                            { "data": "phone" },
                            { "data": "registered" },
                            { "data": "last_login" }
                        ],
                        order: [4, ["desc"]]
                    });
                },
                error: function(){}
            });
            break;
    }
}

/* services entries update schedule */
var services_request = $('#services_request').DataTable({
    "ajax":{
        url: 'http://'+server+'/rest-api/report/entries/',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json'
    },
    "processing": true,
    "columns": [
        { "data": "msisdn" },
        { "data": "request" },
        { "data": "service" },
        { "data": "bill_type" },
        { "data": "trial" },
        { "data": "state" },
        { "data": "reason" },
        { "data": "count" },
        { "data": "date" }
    ],
    order: [8, ["desc"]]
});

/* payment entries update schedule */
var payment_request = $('#payment_request').DataTable({
    "ajax":{
        url: 'http://'+server+'/rest-api/report/payment/',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json'
    },
    "processing": true,
    "columns": [
        { "data": "msisdn" },
        { "data": "request" },
        { "data": "service" },
        { "data": "amount" },
        { "data": "state" },
        { "data": "details" },
        { "data": "transactionid" },
        { "data": "author" },
        { "data": "date" }
    ],
    order: [8, ["desc"]]
});

/* billing monitor update schedule */
var billing_request = $('#services_billing').DataTable({
    "ajax":{
        url: 'http://'+server+'/rest-api/report/billings/',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json'
    },
    "processing": true,
    "columns": [
        { "data": "service_name" },
        { "data": "total_new_day" },
        { "data": "total_unsubs_day" },
        { "data": "total_susp_day" },
        { "data": "total_play_rate"},
        { "data": "total_subs" },
        { "data": "total_bills_day" },
        { "data": "total_bills" }
    ]
});

/* start live monitoring on dashboard only */
if (current_page.length == 0){
    /* live entries settings */
    var refresh_settings_entries = setInterval( function () {
        services_request.ajax.reload(null, false);
        get_dashboard_stats_log();
        // default: 1 Min
    }, 60000 );

    /* live payment settings */
    var refresh_settings_payment = setInterval( function () {
        payment_request.ajax.reload(null, false);
        // default: 1 Min
    }, 60000 );

    /* live billing settings */
    var refresh_settings_billing = setInterval(function(){
        billing_request.ajax.reload(null, false);
    }, 150000);

    /* live push settings */
    var refresh_settings_push = setInterval(function () {
        //get_push_services_log();
    }, 60000);  // default 1m
}


/* refresh alter settings */
function update_refresh_set_billing(value_set){
    var icon_b = $('[box_line="l_icon_billing"]');
    switch (value_set){
        case '0': /* refresh now */
            billing_request.ajax.reload(null, false);
            break;
        case '1': /* 1min */
            window.clearInterval(refresh_settings_billing);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_billing = setInterval( function () {
                billing_request.ajax.reload(null, false);
            }, 60000 );
            break;
        case '5': /* 5min */
            window.clearInterval(refresh_settings_billing);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_billing = setInterval( function () {
                billing_request.ajax.reload(null, false);
            }, 300000 );
            break;
        case '30': /* 30min */
            window.clearInterval(refresh_settings_billing);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_billing = setInterval( function () {
                billing_request.ajax.reload(null, false);
            }, 1800000 );
            break;
        default : /* disable */
            window.clearInterval(refresh_settings_billing);
            icon_b.removeClass('box-success').addClass('box-danger');
            break;
    }
}

/* refresh alter settings */
function update_refresh_set_entries(value_set){
    var icon_b = $('[box_line="l_icon_entries"]');
    switch (value_set){
        case '0': /* refresh now */
            services_request.ajax.reload(null, false);
            get_dashboard_stats_log();
            break;
        case '1': /* 1min */
            window.clearInterval(refresh_settings_entries);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_entries = setInterval( function () {
                services_request.ajax.reload(null, false);
                get_dashboard_stats_log();
                // user paging is not reset on reload
            }, 60000 );
            break;
        case '5': /* 5min */
            window.clearInterval(refresh_settings_entries);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_entries = setInterval( function () {
                services_request.ajax.reload(null, false);
                get_dashboard_stats_log();
                // user paging is not reset on reload
            }, 300000 );
            break;
        case '30': /* 30min */
            window.clearInterval(refresh_settings_entries);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_entries = setInterval( function () {
                services_request.ajax.reload(null, false);
                get_dashboard_stats_log();
                // user paging is not reset on reload
            }, 1800000 );
            break;
        default : /* disable */
            window.clearInterval(refresh_settings_entries);
            icon_b.removeClass('box-success').addClass('box-danger');
            break;
    }
}

/* refresh alter settings for payment requests */
function update_refresh_set_payment(value_set){
    var icon_b = $('[box_line="l_icon_payment"]');
    switch (value_set){
        case '0': /* refresh now */
            payment_request.ajax.reload(null, false);
            break;
        case '1': /* 1min */
            window.clearInterval(refresh_settings_payment);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_payment = setInterval( function () {
                payment_request.ajax.reload(null, false);
                // user paging is not reset on reload
            }, 60000 );
            break;
        case '5': /* 5min */
            window.clearInterval(refresh_settings_payment);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_payment = setInterval( function () {
                payment_request.ajax.reload(null, false);
                // user paging is not reset on reload
            }, 300000 );
            break;
        case '30': /* 30min */
            window.clearInterval(refresh_settings_payment);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_payment = setInterval( function () {
                payment_request.ajax.reload(null, false);
                // user paging is not reset on reload
            }, 1800000 );
            break;
        default : /* disable */
            window.clearInterval(refresh_settings_payment);
            icon_b.removeClass('box-success').addClass('box-danger');
            break;
    }
}

/* live push settings */
function update_refresh_set_push(value_set){
    var icon_b = $('[box_line="l_icon_push"]');
    switch (value_set){
        case '0': /* refresh now */
            get_push_services_log();
            break;
        case '1': /* 1sec */
            window.clearInterval(refresh_settings_push);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_push = setInterval( function () {
                get_push_services_log();
                // user paging is not reset on reload
            }, 1000 );
            break;
        case '5': /* 5sec */
            window.clearInterval(refresh_settings_push);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_push = setInterval( function () {
                get_push_services_log();
                // user paging is not reset on reload
            }, 5000 );
            break;
        case '30': /* 30sec */
            window.clearInterval(refresh_settings_push);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_push = setInterval( function () {
                get_push_services_log();
                // user paging is not reset on reload
            }, 30000 );
            break;
        case '60': /* 1min */
            window.clearInterval(refresh_settings_push);
            icon_b.removeClass('box-danger').addClass('box-success');
            refresh_settings_push = setInterval( function () {
                get_push_services_log();
                // user paging is not reset on reload
            }, 60000 );
            break;
        default : /* disable */
            window.clearInterval(refresh_settings_push);
            icon_b.removeClass('box-success').addClass('box-danger');
            break;
    }
}


/* Daily Push Services Report - Live Update */
function get_push_services_log() {
    /* initialise service variables */
    var service_ids = [];
    $.ajax({
        url: 'http://' + server + '/rest-api/tools/services/list/',
        type: 'POST',
        data: JSON.stringify({
            category: 'services',
            type: 'all',
            service_id: 0
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function (response) {
            if (response.data) {
                var services = response.data;
                for (key in services) {
                    if (services.hasOwnProperty(key)) {
                        service_ids.push(services[key].id);
                    }
                }
            }
        },
        error: function () {
            alert_modal('Error getting broadcast push services list', 3);
        }
    });

    if (service_ids.length > 0) {
        for (var i = 0; i < service_ids.length; ++i) {
            /* drop Rest-Service Type from the layout */
            if (service_ids[i].type != '4'){
                (function(_i) {
                    var push_box = $('#push_' + service_ids[_i]+'');
                    var error_box = $('#push_errors_' + service_ids[_i]);
                    /* get live push stats for related services */
                    $.ajax({
                        url: 'http://' + server + '/rest-api/report/broadcast/',
                        type: 'POST',
                        data: JSON.stringify({
                            service_id: service_ids[_i]
                        }),
                        contentType: 'application/json; charset=utf-8',
                        dataType: 'json',
                        success: function (result) {
                            if (result.data) {
                                /* update service rate */
                                push_box.children('span:nth-child(2)').children('b').text(result.data.push);
                                push_box.children('span:nth-child(2)').children('b').next().text('/' + result.data.total);
                                error_box.children('span:nth-child(2)').children('b').text(result.data.error);
                                /* update percentage rate */
                                var rate = average_data(result.data);
                                push_box.children('div').children('div').css('width', rate.success + '%');
                                error_box.children('div').children('div').css('width', rate.errors + '%');
                            }
                        },
                        error: function () {
                        }
                    });

                })(i);
            }
        }
    }
}

function average_data(obj){
    return {
        'success': Math.round((obj.push * 100) / obj.total),
        'errors': Math.round((obj.error * 100) / obj.total)
    };
}

/* Display Clock */
function startTime() {
    var clock = $('[custom="clock"]');
    var today = new Date();
    //var Y = today.getFullYear();
    //var M = today.getMonth();
    //var D = today.getDate();
    var h = today.getHours();
    var m = today.getMinutes();
    var s = today.getSeconds();
    m = checkTime(m);
    s = checkTime(s);
    clock.text(h + ":" + m + ":" + s);
    var t = setTimeout(startTime, 500);
}
function checkTime(i) {
    if (i < 10) {i = "0" + i}  // add zero in front of numbers < 10
    return i;
}

/******* Keep-Alive Session **********/
function keep_alive(){
    var session_icon = $('#keep-alive');
    $.ajax({
        url: 'http://'+server+'/rest-api/login/keep-alive/',
        type: 'POST',
        data: JSON.stringify({
            loginID: loginID,
            remote_address: client
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result) {
            /* check if session is alive */
            if (result.error){
                if (session_icon.find('i').hasClass('text-success')){
                    session_icon.find('i').removeClass('text-success').addClass('text-danger');
                    session_icon.find('span').text('Offline');
                    update_refresh_set_entries('disable');
                    update_refresh_set_push('disable');
                    alert_modal('Your computer is not connected to the internet or the server is unavailable', 3);
                }
            } else {
                /* check a session closed due to multiple login */
                if (result.result){
                    if (session_icon.find('i').hasClass('text-danger')){
                        session_icon.find('i').removeClass('text-danger').addClass('text-success');
                        session_icon.find('span').text('Online');
                        /* only on dashboard */
                        if (current_page.length == 0){
                            update_refresh_set_entries('1');
                            update_refresh_set_push('1');
                        }
                    }
                } else {
                    //alert(typeof result.result);
                    /* close current session */
                    session_icon.find('i').removeClass('text-success').addClass('text-danger');
                    session_icon.find('span').text('Offline');
                    update_refresh_set_entries('disable');
                    update_refresh_set_push('disable');
                    alert_modal('Your current session has been closed due to multiple login', 3);
                    /* brute logout current user */
                    window.clearInterval(current_session);
                    brute_logout();
                }
            }
        },
        error: function(){
            if (session_icon.find('i').hasClass('text-success')){
                session_icon.find('i').removeClass('text-success').addClass('text-danger');
                session_icon.find('span').text('Offline');
                update_refresh_set_entries('disable');
                update_refresh_set_push('disable');
                alert_modal('Your computer is not connected to the internet or the server is unavailable', 3);
            }
        }
    });
}

/* force a logout due to multiple session */
function brute_logout(){
    setTimeout(function(){
        window.location.assign('http://'+server+'/public/?logout');
    }, 10000);
}


/* alert modal @type:0: info,  1: success, 2: warning, 3: error */
function alert_modal(data, type){
    var modal = $('#alert');
    switch (type){
        case 0:
            modal.removeClass('modal-success modal-warning modal-danger').addClass('modal-info');
            modal.find('.modal-title').html('<i class="icon fa fa-info"></i> Info!');
            break;
        case 1:
            modal.removeClass('modal-info modal-warning modal-danger').addClass('modal-success');
            modal.find('.modal-title').html('<i class="icon fa fa-check"></i> Success!');
            break;
        case 2:
            modal.removeClass('modal-info modal-success modal-danger').addClass('modal-warning');
            modal.find('.modal-title').html('<i class="icon fa fa-warning"></i> Warning!');
            break;
        case 3:
            modal.removeClass('modal-info modal-success modal-warning').addClass('modal-warning').addClass('modal-danger');
            modal.find('.modal-title').html('<i class="icon fa fa-ban"></i> Error!');
    }
    modal.find('.modal-body p i').text(data);
    modal.modal('show');
}



/******** User Profile *************/

/* initialise profile settings and functions */
if (current_page == 'users/profile/'){
    /* enable closing post */
    $('.post div span a:nth-child(2) i').click(function(){
        $(this).parents('.post').fadeOut('slow');
    });

    /* initialise fetch post interval */
    var fetch_post = window.setInterval(function(){
        load_user_profile();
    }, 5000);

}


/* get user profile service stats */
function load_user_profile(){
    var profile_number = $('#profile_number').text();
    /* load 10 recent profile activities */
    query_service_activies(profile_number);
    user_profile(profile_number);

}

function user_profile(msisdn){
    var profile_box = $('#profile-stat');
    /* load current profile stats */
    $.ajax({
        url: 'http://'+server+'/rest-api/users/profile/stats/',
        type: 'POST',
        data: JSON.stringify({
            msisdn: msisdn
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result) {
            if (result.result){
                var profile = result.result;
                var profile_display = '';
                if (profile.services != null){
                    var services = profile.services;
                    for (key in services){
                        if (services.hasOwnProperty(key)){
                            profile_display += '<hr>\
                                            <strong><i class="fa fa-book margin-r-5"></i> '+ services[key].name+'</strong>\
                                            <p class="text-muted">\
                                                Status: <i class="'+ (services[key].status == 'Subscribed') ? 'active':'inactive' +'">'+services[key].status+'</i>\
                                                | Cc Pts: <i>'+services[key].today_points+'</i> | Total Pts: <i>'+services[key].points+'</i> | Last Play: <i>'+services[key].last_played+'</i>\
                                            </p>';
                        }
                    }
                }
                /* add last login */
                profile_display += '<hr><p class="text-muted">Last Login : '+ profile.last_login+'</p>';
                profile_box.html(profile_display);
            }
        },
        error: function(){
            alert_modal('Enable to load current profile. Your computer is not connected to the internet or the service is unavailable', 3);
        }
    });
}

/* post new glam post */
function fetch_trivia_post(data, service_name){
    var rendered_post = '';
    for(var i=0; i < data.length; ++i){
        if (data[i].correct == 1){
            rendered_post += '<div class="post post_hidden">\
                <div class="user-block">\
                    <img class="img-circle img-bordered-sm" src="http://'+server+'/public/assets/dist/img/profile/glam_squad.png" alt="user image">\
                    <span class="username">\
                        <a href="#">'+service_name+'</a>\
                        <a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>\
                    </span>\
                    <span class="description">Played '+service_name+' - '+ data[i].date +'</span>\
                </div>\
                <p>\
                '+ data[i].content +'\
                <br><span class="label label-success">'+ data[i].answer +'</span> &nbsp; <i class="fa fa-check"></i><a class="pull-right"><span class="badge bg-green">'+ data[i].earned +'</span></a>\
                </p>\
            </div>'
        } else {
            rendered_post += '<div class="post post_hidden">\
                <div class="user-block">\
                    <img class="img-circle img-bordered-sm" src="http://'+server+'/public/assets/dist/img/profile/glam_squad.png" alt="user image">\
                    <span class="username">\
                        <a href="#">'+service_name+'</a>\
                        <a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>\
                    </span>\
                    <span class="description">Played '+service_name+' - '+ data[i].date +'</span>\
                </div>\
                <p>\
                '+ data[i].content +'\
                <br><span class="label label-danger">'+ data[i].answer +'</span> &nbsp; <i class="fa fa-close"></i> &nbsp; '+ data[i].correct_answer +' <a class="pull-right"><span class="badge bg-red">'+ data[i].earned +'</span></a>\
                </p>\
            </div>'
        }

    }
    return rendered_post;
}

/* post new Beauty tips */
function fetch_content_post(data, service_name){
    var rendered_post = '';
    for(var i=0; i < data.length; ++i){
        rendered_post += '<div class="post post_hidden">\
                <div class="user-block">\
                    <img class="img-circle img-bordered-sm" src="http://'+server+'/public/assets/dist/img/profile/tips.png" alt="User Image">\
                    <span class="username">\
                        <a href="#">'+service_name+'</a>\
                        <a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>\
                    </span>\
                    <span class="description">Received '+service_name+' - '+ data[i].date +'</span>\
                </div>\
                <p>\
                '+ data[i].content +'.\
                </p>\
            </div>';
    }
    return rendered_post;
}

/* post new Beauty tips */
function fetch_notify_post(data){
    var rendered_post = '';
    for(var i=0; i < data.length; ++i){
        rendered_post += '<div class="post post_hidden">\
                <div class="user-block">\
                    <img class="img-circle img-bordered-sm" src="http://'+server+'/public/assets/dist/img/profile/notify.jpg" alt="User Image">\
                    <span class="username">\
                        <a href="#">'+ data[i].title +'</a>\
                        <a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>\
                    </span>\
                    <span class="description">Received '+ data[i].date +'</span>\
                </div>\
                <p>\
                '+ data[i].content +'.\
                </p>\
            </div>';
    }
    return rendered_post;
}

/* post new users activity */
function append_profile_activity(content){
    window.setTimeout(function(){
        var load_post = $('#default');
        if (!load_post.hasClass('post_default_hidden')){
            load_post.addClass('post_default_hidden');
        }
        $('#activity').prepend(content);
        /* enable closing post */
        $('.post div span a:nth-child(2) i').click(function(){
            $(this).parents('.post').fadeOut('slow');
        });
    }, 2000);

    /* render added post */
    window.setTimeout(function(){
        $('.post_hidden').each(function(){
            $(this).fadeIn('slow');
        });
    }, 4000);
}


/******** Subscription Request Query *************/
/* initialise variables */
var request_report = $('#sub_report');

$('.box-request div span button:contains(" Search ")').on('click', function(){
    /* reset the request_report */
    request_report.text('');
    $('.box-request #query_display').html('');
    $('#activity').html('<br><br>');
    /* get request input */
    var request_input = $('#sub_request').val();
    /* validate input */
    if (isNaN(request_input)){
        alert_modal('The phone number provided is not valid. Please review', 2);
    } else {
        if (request_input.length != 10){
            alert_modal('The phone number must be 10 digit. Please review', 2);
        } else {
            request_report.text('Processing your request ... Please be patient.');
            $('#loader_body').show();
            /* process services subscription query */
            window.setTimeout(function(){
                process_services_query(request_input);
            }, 500);
        }
    }
});

/* process services subscription query */
function process_services_query(msisdn){
    /* clear the display result */
    $('.box-request #query_display').html('');
    $('#activity').html('<br><br>');
    $.ajax({
        url: 'http://' + server + '/rest-api/users/query/services-history/',
        type: 'POST',
        data: JSON.stringify({
            msisdn: msisdn
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function (result) {
            //alert(JSON.stringify(result));
            if (result.error){
                alert_modal(result.error, 3);
                request_report.text('');
            } else {
                query_result_post(result.data);
                query_service_activies(msisdn);
            }
            $('#loader_body').hide();
        },
        error: function () {
            alert_modal('Error processing your request. Current service request is unavailable', 3);
            $('#loader_body').hide();
        }
    });
}

/* post service query result */
function query_result_post(data){
    var rendered_post = '';
    var phone = $('#sub_request').val();
    for (var key in data){
        if (data.hasOwnProperty(key)){
            var button_request = (data[key].status == 'Subscribed') ?
                '<button class="btn btn-danger btn-sm btn-request">Request Cancellation</button>':'<button class="btn btn-primary btn-sm btn-request">&nbsp;&nbsp;Request Activation&nbsp;&nbsp;</button>';
            var status_color = (data[key].status == 'Subscribed') ? 'active' : 'inactive';
            rendered_post += '<strong><i class="fa fa-book margin-r-5"></i> '+data[key].name+'</strong>\
                                <p class="text-muted">\
                                    Status: <i class="'+status_color+'">'+data[key].status+'</i> | Date: <i>'+data[key].date+'</i> | Cc Pts: <i>'+data[key].today_points+'</i>\
                                     | Total Pts: <i>'+data[key].points+'</i> | Last Play: <i>'+data[key].last_played+'</i>\
                                    <b phone="'+phone+'">&nbsp;</b><a class="pull-right" serviceid="'+data[key].id+'" >'+button_request+'</a>\
                                </p>\
                                <hr>';
        }
    }
    /* post result */
    $('.box-request #query_display').append(rendered_post);
    /* initialise service request events */
    $('.btn-request').each(function(){
        $(this).on('click', function(){
            if ($(this).text() == 'Request Cancellation'){
                //alert("Request service ID = "+ $(this).parent().attr('serviceid') + ' Phone: '+ $(this).parent().prev().attr('phone'));
                process_service_request(
                    $(this).parent().prev().attr('phone'),
                    'cancellation',
                    $(this).parent().attr('serviceid')
                );
            } else {
                //alert("Request service ID = "+ $(this).parent().attr('serviceid'));
                process_service_request(
                    $(this).parent().prev().attr('phone'),
                    'activation',
                    $(this).parent().attr('serviceid')
                );
            }
        });
    });

    //request_report.text('Request completed successfully! ');
}

/* process service request */
function process_service_request(msisdn, request, serviceID){
    $('#loader_body').show();
    request_report.text('Processing your '+ request + ' request. Please wait ...');
    $.ajax({
        url: 'http://' + server + '/rest-api/users/request/services-related/',
        type: 'POST',
        data: JSON.stringify({
            msisdn: msisdn,
            request: request,
            serviceID: serviceID
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function (result) {
            //alert(JSON.stringify(result));
            if (result.error){
                request_report.text(result.error);
            } else {
                request_report.text(result.result);
                /* reload display */
                process_services_query(msisdn);
            }
            $('#loader_body').hide();
        },
        error: function () {
            alert_modal('Error processing your request. Current service request is unavailable', 3);
            $('#loader_body').hide();
        }
    });
}

/* get user profile service on query search */
function query_service_activies(msisdn){
    /* load 10 recent profile activities */
    $.ajax({
        url: 'http://'+server+'/rest-api/users/profile/activity/',
        type: 'POST',
        data: JSON.stringify({
            msisdn: msisdn
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(result) {
            /* process relating to services */
            //alert(JSON.stringify(result));
            if (result.error){
                //alert_modal(result.error, 2);
            } else {
                var services = result.data;
                for (key in services){
                    if (services.hasOwnProperty(key)){
                        if (services[key].type == 1 && services[key].posts != null){
                            append_profile_activity(fetch_content_post(services[key].posts, services[key].name));
                        } else if (services[key].type == 2 && services[key].posts != null) {
                            append_profile_activity(fetch_trivia_post(services[key].posts, services[key].name));
                        }
                    }
                }
            }
        },
        error: function(){
            alert_modal('Enable to update your profile. Your computer is not connected to the internet or the service is unavailable', 3);
        }
    });

}

/* Process printing request */
function request_printing_CSV(job_request, service_id, data){
    var form = document.createElement("form");
    form.setAttribute("method", 'POST');
    form.setAttribute("action", 'http://'+server +'/public/modules/job_processor.php');
    var hiddenField = document.createElement("input");
    hiddenField.setAttribute("type", "hidden");
    hiddenField.setAttribute("name", 'job');
    hiddenField.setAttribute("value", job_request);

    var hiddenFieldOpt = document.createElement("input");
    hiddenFieldOpt.setAttribute("type", "hidden");
    hiddenFieldOpt.setAttribute("name", 'serviceID');
    hiddenFieldOpt.setAttribute("value", service_id);

    var hiddenFieldData = document.createElement("input");
    hiddenFieldData.setAttribute("type", "hidden");
    hiddenFieldData.setAttribute("name", 'data');
    hiddenFieldData.setAttribute("value", data);


    form.appendChild(hiddenField);
    form.appendChild(hiddenFieldOpt);
    form.appendChild(hiddenFieldData);

    document.body.appendChild(form);
    form.submit();
}


/* reload on refresh */
function refresh_page(dashboard){
    if (dashboard == undefined){
        window.location.assign('http://'+server+window.location.pathname);
    } else {
        window.location.assign('http://'+server+'/public/');
    }

}

/*********** mo_traffic interactive ********/

/*
 * Flot Interactive Chart
 * -----------------------
 */

function getMOdata() {
    var traffics = null;
    /* load current profile stats */
    $.ajax({
        url: 'http://'+server+'/rest-api/report/traffic/',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function(result) {
            if (result.data){
                traffics = result.data;
            } else {
                alert_modal(result.error, 3);
            }
        },
        error: function(){
            alert_modal('Enable to get MO traffic. Your computer is not connected to the internet or the service is unavailable', 3);
        }
    });

    return traffics;
}

/* render mo_data */
function render_mo_data(){
    var mo_data = getMOdata();
    var rendered = [];
    if (mo_data != null){
        for(var i=0; i < mo_data.length; ++i){
            rendered.push([i, mo_data[i].data]);
        }
    }
    return rendered;
}

//alert(JSON.stringify(render_mo_data()));
//var interactive_plot = $.plot("#mo_interactive", [render_mo_data()], {
//    grid: {
//        borderColor: "#f3f3f3",
//        borderWidth: 1,
//        tickColor: "#f3f3f3"
//    },
//    series: {
//        shadowSize: 0, // Drawing is faster without shadows
//        color: "#3c8dbc"
//    },
//    lines: {
//        fill: true, //Converts the line chart to area chart
//        color: "#3c8dbc"
//    },
//    yaxis: {
//        min: 0,
//        max: 100,
//        show: true
//    },
//    xaxis: {
//        show: true
//    }
//});

//var updateInterval = 2000; //Fetch data ever x milliseconds
//var realtime = "on"; //If == to on then fetch data every x seconds. else stop fetching
//function update() {
//
//    interactive_plot.setData([render_mo_data()]);
//
//    // Since the axes don't change, we don't need to call plot.setupGrid()
//    interactive_plot.draw();
//    if (realtime === "on")
//        setTimeout(update, updateInterval);
//}
//
////INITIALIZE REALTIME DATA FETCHING
//if (realtime === "on") {
//    update();
//}
////REALTIME TOGGLE
//$("#realtime .btn").click(function () {
//    if ($(this).data("toggle") === "on") {
//        realtime = "on";
//    }
//    else {
//        realtime = "off";
//    }
//    update();
//});






