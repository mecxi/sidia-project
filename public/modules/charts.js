/**
 * Chart report
 * Created by Mecxi on 6/5/2017.
 */

/* get subscribers, billing charts */
get_subscribers_charts();

/* get service monthly billing charts */
get_services_monthly_chart();

function get_subscribers_charts(){
    if (service_id == null){
        alert_modal('An error has occurred rendering subscribers chart. Please report to the system administrator');
    } else {

        /* fetch subscribers report */
        /* process table billing report */
        $.ajax({
            url: 'http://'+server+'/rest-api/report/billing/',
            type: 'POST',
            data: JSON.stringify({
                type: 'chart',
                service: service_id
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                //alert(JSON.stringify(result));
                if (result.error){
                    alert_modal(result.error, 3);
                } else {
                    if (result.data == null){
                        alert_modal('No data found to draw chart. Please try again tomorrow. If the problem persists, contact System Admin', 2);
                    } else {
                        process_rendering_charts(result.data, 'users');
                        process_rendering_charts(result.data, 'total');
                        if (in_array([1, 2], role)){
                            process_rendering_charts(result.data, 'revenue_daily');
                            process_rendering_charts(result.data, 'total_revenue');
                        }
                        process_rendering_charts(result.data, 'play_rate');
                    }
                }
            },
            error: function(){}
        });


    }
}

function get_services_monthly_chart(){
    if (service_id == null){
        alert_modal('An error has occurred rendering subscribers chart. Please report to the system administrator');
    } else {

        /* fetch subscribers report */
        /* process table billing report */
        $.ajax({
            url: 'http://'+server+'/rest-api/report/billing/',
            type: 'POST',
            data: JSON.stringify({
                type: 'chart_month',
                service: service_id
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(result) {
                //alert(JSON.stringify(result));
                if (result.error){
                    alert_modal(result.error, 3);
                } else {
                    if (result.data == null){
                        alert_modal('No monthly data chart found to draw chart. Please try again tomorrow. If the problem persists, contact System Admin', 2);
                    } else {
                        process_rendering_charts(result.data, 'monthly');
                    }
                }
            },
            error: function(){}
        });


    }
}

function process_rendering_charts(data, type){
    switch (type){
        case 'users':
            var bar_users = new Morris.Bar({
                element: 'chart_new_cancel_users',
                resize: true,
                barColors: ['#00a65a', '#f56954', '#5497FF'],
                xkey: 'y',
                ykeys: ['a', 'b', 'c'],
                labels: ['NEW', 'CANCEL', 'REPEAT'],
                hideHover: 'auto'
            });
            /* render data */
            var rendered_users = [];
            for(var i = (data.length - 1); i > -1; --i){
                rendered_users.push({y:data[i].date, a:data[i].total_new_day, b:data[i].total_unsubs_day, c:data[i].repeat_bills})
            }
            /* render chart */
            bar_users.setData(rendered_users);
            break;
        case 'total':
            var bar_users_total = new Morris.Bar({
                element: 'chart_total_users',
                resize: true,
                barColors: ['#5497FF'],
                xkey: 'y',
                ykeys: ['a'],
                labels: ['TOTAL USERS'],
                hideHover: 'auto'
            });
            /* render data */
            var rendered_total = [];
            for(var j = (data.length - 1); j > -1; --j){
                rendered_total.push({y:data[j].date, a:data[j].total_subs})
            }
            /* render chart */
            bar_users_total.setData(rendered_total);
            break;
        case 'revenue_daily':
            var bar_daily_revenue = new Morris.Bar({
                element: 'chart_daily_revenue',
                resize: true,
                barColors: ['#1FFFEE'],
                xkey: 'y',
                ykeys: ['a'],
                labels: ['DAILY REVENUE'],
                hideHover: 'auto'
            });
            /* render data */
            var daily_revenue = [];
            for(var r = (data.length - 1); r > -1; --r){
                daily_revenue.push({y:data[r].date, a:data[r].total_day_bills})
            }
            /* render chart */
            bar_daily_revenue.setData(daily_revenue);
            break;
        case 'total_revenue':
            var bar_total_revenue = new Morris.Bar({
                element: 'chart_total_revenue',
                resize: true,
                barColors: ['#00a65a'],
                xkey: 'y',
                ykeys: ['a'],
                labels: ['TOTAL REVENUE'],
                hideHover: 'auto'
            });
            /* render data */
            var total_revenue = [];
            for(var t = (data.length - 1); t > -1; --t){
                total_revenue.push({y:data[t].date, a:data[t].total_overall_bills})
            }
            /* render chart */
            bar_total_revenue.setData(total_revenue);
            break;
        case 'play_rate':
            var bar_play_rate = new Morris.Bar({
                element: 'chart_play_rate',
                resize: true,
                barColors: ['#1FFFEE'],
                xkey: 'y',
                ykeys: ['a'],
                labels: ['DAILY PLAY-RATE'],
                hideHover: 'auto'
            });
            /* render data */
            var play_rate = [];
            for(var p = (data.length -1); p > -1; --p){
                play_rate.push({y:data[p].date, a:data[p].total_play_rate})
            }
            /* render chart */
            bar_play_rate.setData(play_rate);
            break;
        case 'monthly':
            var bar_monthly_bill = new Morris.Bar({
                element: 'chart_monthly_bill',
                resize: true,
                barColors: ['#1FFFEE'],
                xkey: 'y',
                ykeys: ['a'],
                labels: ['MONTHLY REVENUE'],
                hideHover: 'auto'
            });
            /* render data */
            var monthly_revenue = [];
            for(var m = 0; m < data.length; ++m){
                monthly_revenue.push({y:data[m].date, a:data[m].total})
            }
            /* render chart */
            bar_monthly_bill.setData(monthly_revenue);
            break;
    }
}
