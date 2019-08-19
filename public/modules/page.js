/**
 * @author: Mecxi Musa
 */


/* initialise the gif_loader */
var gif_loader = $('#loader');
var gif_loader_body = $('#loader_body');
var service_list = [];
var draw_engine_list = [];

/* Populate menu list */
populate_menu_services_list($('#reporting'), 'services');
populate_menu_services_list($('#draw-engine'), 'draw_engine_list');
layout_broadcast_push();

if (current_page == 'manage/services/'){
    populate_services_basic_panels('services');
}

if (current_page == 'manage/draws/'){
    populate_services_basic_panels('draws');
}

/***************** dynamic menu **********************/
/* reporting service list or draw engine list */
function populate_menu_services_list(target_menu, type){
    /* get service menu list */
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/services/list/',
        type: 'POST',
        data: JSON.stringify({
            category: type,
            type: 'all',
            service_id: 0
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function(response) {
            if (response.error){
                alert_modal(response.error, 3);
            } else {
                var services = response.data;
                var menu_list = "";
                if (services != null){
                    for(var i=0; i < services.length; ++i){
                        /* drop service type Rest-Service from the reporting */
                        if (services[i].type != '4'){
                            menu_list +="<li class='active'><a href='"+ base_uri + target_menu.attr('id') + "/"+services[i].id+"'><i class='fa fa-circle-o text-aqua'></i>"+services[i].name+"</a></li>";
                        }
                        if (type == 'services'){
                            service_list.push({'id':services[i].id, 'name': services[i].name, 'type':services[i].type, 'length':services[i].length});
                        } else {
                            draw_engine_list.push({'id':services[i].id, 'name': services[i].name, 'draw_type_id': services[i].draw_type_id});
                        }
                    }
                }
                target_menu.html(menu_list);
            }
        },
        error: function(){
            alert_modal('Error populating menu '+ target_menu.attr('id')+' connecting to the server', 3);
        }
    });
}

/********* Live broadcast services layout *************/
function layout_broadcast_push(){
    var broadcast = $('#broadcast');
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
                alert_modal(response.error, 3);
            } else {
                var services = response.data;
                var layout = "";
                if (services != null){
                    for(var i=0; i < services.length; ++i){
                        /* drop Rest-Service Type from the layout */
                        if (services[i].type != '4'){
                            layout +='<div class="progress-group" id="push_'+services[i].id+'">\
                                    <span class="progress-text"><i>'+services[i].name+'</i></span>\
                                    <span class="progress-number"><b>0</b><span>/0</span></span>\
                                    <div class="progress sm">\
                                        <div class="progress-bar progress-bar-aqua" style="width:0%"></div>\
                                    </div>\
                               </div>\
                               <div class="progress-group" id="push_errors_'+services[i].id+'">\
                                    <span class="progress-text">Errors - '+services[i].name+'</span>\
                                    <span class="progress-number"><b>0</b></span>\
                                    <div class="progress sm">\
                                        <div class="progress-bar progress-bar-red" style="width:0%"></div>\
                                    </div>\
                                </div>';
                        }
                    }
                }

                if (broadcast.length > 0){
                    broadcast.append(layout);
                    /* get service push log */
                    get_push_services_log();
                }
            }
        },
        error: function(){
            alert_modal('Error populating service broadcast layout', 3);
        }
    });
}


/**** management services handlers ******/
/* editing modal */
function editing_modal(data, title){
    var modal = $('#editing');
    modal.find('.modal-body > div.row > div').html(data);
    modal.find('.modal-header h4').text((title == undefined) ? '': title);
    modal.modal('show');
    modal.off('hidden.bs.modal').on('hidden.bs.modal', function(e){
        var para = $(this).find('.modal-body > p');
        if (para.text().match('successfully') !== null){
            refresh_page();
        } else {
            para.text('');
        }
    });
}

/* load services panels */
function populate_services_basic_panels(type){
    gif_loader_body.show();
    var panel_container = $('#accordion');
    var basic_data = {data:null};
    var panel_item = 0; // control panel numbers
    if (type == 'services'){
        /* find contents related service */
        for(var key in service_list){
            if (service_list.hasOwnProperty(key)){
                get_service_basic_settings(service_list[key].id, basic_data, 'data_basic');
                if (basic_data.data){
                    ++panel_item;
                    initialise_service_panel(panel_container, panel_item, basic_data.data.br_allow, 'service', service_list[key]);
                    set_service_title(panel_container, service_list[key], panel_item);
                    set_service_info(panel_container, basic_data.data, panel_item, 'service', service_list[key]);
                    set_service_messages(panel_container, basic_data.data.service_messages, service_list[key], panel_item);
                }
            }
        }
        /* get service content related */
        populate_service_contents();

    } else {
        /* find campaign draw related */
        for(var i in draw_engine_list){
            if (draw_engine_list.hasOwnProperty(i)){
                get_service_basic_settings(draw_engine_list[i].id, basic_data, 'draw_data_basic');
                if (basic_data.data){
                    ++panel_item;
                    initialise_service_panel(panel_container, panel_item, basic_data.data.active, 'draw', draw_engine_list[key]);
                    set_service_title(panel_container, draw_engine_list[i], panel_item);
                    set_service_info(panel_container, basic_data.data, panel_item, 'draw', draw_engine_list[key]);
                }
            }
        }
    }
    gif_loader_body.hide();
}

/* load services contents related */
function populate_service_contents(){
    var panel_container = $('#accordion');
    var content_data = {data:null};
    var panel_item = 0; // control panel numbers
    /* find contents related service */
    for(var key in service_list){
        if (service_list.hasOwnProperty(key)){
            get_service_basic_settings(service_list[key].id, content_data, 'data_content');
            if (content_data.data){
                ++panel_item;
                set_service_contents(panel_container, content_data.data.service_contents, service_list[key], panel_item);
            }
        }
    }
    gif_loader_body.hide();
}

/* get basic settings for the current service */
function get_service_basic_settings(service_id, service_data, category){

    $.ajax({
        url: 'http://'+server+'/rest-api/tools/services/list/',
        type: 'POST',
        data: JSON.stringify({
            category: category,
            type: 0,
            service_id: service_id
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function(response) {
            if (response.error){
                alert_modal(response.error, 3);
            } else {
                service_data.data = response.data;
            }
        },
        error: function(){
            alert_modal('Error getting service basic - connecting to the server', 3);
        }
    });
}

/* initialise service panel */
function initialise_service_panel(panel_container, count, active, type, current_service){
    var panel_bar_color = (active == 1) ? 'box-success' : 'box-danger';
    if (type == 'service'){
        /* drop the upload button on a web service type */
        var upload_row = current_service.type != 4 ? '<div class="row">\
                        <div class="col-xs-6">\
                            <p class="lead">Service Contents: <a href="#" class="btn btn-primary content-upload" title="upload contents"><i class="fa fa-cloud-upload"></i> Upload</a></p>\
                        </div>\
                    </div>' : '';
        var msg_reference_row = current_service.type != 4  ? '<div class="row">\
                        <div class="col-xs-6">\
                            <p class="lead">Service Messages References:</p>\
                        </div>\
                    </div>' : '';

        panel_container.append(
            '<div class="panel box '+panel_bar_color+'">\
            <div class="box-header with-border">\
                <h4 class="box-title">\
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse'+count+'"></a>\
                <span class="hidden"></span>\
                </h4>\
                <ul><li class="pull-right"><a href="#" class="text-muted"><i class="fa fa-gear"></i></a></li></ul>\
            </div>\
            <div id="collapse'+count+'" class="panel-collapse collapse">\
                <div class="box-body">\
                    <!-- info row -->\
                    <div class="row service-info">\
                    <!-- /.col -->\
                    </div>\
                    <!-- /.row -->\
                <!-- Table service messages references -->'+msg_reference_row+'\
                    <div class="row service-reference">\
                    <!-- /.col -->\
                    </div>\
                    <!-- /.row -->\
                    <!-- Table service messages references -->'+upload_row+'\
                    <div class="row service-contents">\
                    <!-- /.col -->\
                    </div>\
                    <!-- /.row -->\
                </div>\
            </div>\
        </div>'
        );
    } else {
        panel_container.append(
            '<div class="panel box '+panel_bar_color+'">\
            <div class="box-header with-border">\
                <h4 class="box-title">\
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse'+count+'"></a>\
                <span class="hidden"></span>\
                </h4>\
                <ul><li class="pull-right"><a href="#" class="text-muted"><i class="fa fa-gear"></i></a></li></ul>\
            </div>\
            <div id="collapse'+count+'" class="panel-collapse collapse">\
                <div class="box-body">\
                    <!-- info row -->\
                    <div class="row service-info">\
                    <!-- /.col -->\
                    </div>\
                    <!-- /.row -->\
                </div>\
            </div>\
        </div>'
        );
    }

}

/* set service title */
function set_service_title(el, data, index){
    var panel = el.find('.panel:nth-child('+ index +')');
    panel.find('.box-title a').text(data.name);
    panel.find('.box-title a').next().text(data.id);
    panel.find('.box-title a').attr('title', 'Click to open '+ data.name+' details');
}
/* set service info */
function set_service_info(el, data, index, type, service){
    var panel = el.find('.panel:nth-child('+ index +')');
    var info = panel.find('.service-info');

    if (type == 'service'){
        /* add basic data */
        var cross_sell_ref = data.type_id != 4 ? (
            (data.cross_sell_list != null) ?
                (
                    (in_array([1, 3, 4], data.type_id)) ? '' : 'Cross Sell References: <i class="crossell">'+data.cross_sell_list.join()+'</i> &nbsp; &nbsp;<i class="fa fa-edit basic" title="Click to edit current crossell reference"></i><br>'
                ) :
                (
                    (in_array([1, 3, 4], data.type_id)) ? '' : 'Cross Sell References: <i class="crossell"></i> &nbsp; &nbsp;<i class="fa fa-edit basic" title="Click to edit current crossell reference"></i><br>'
                )
        ) : '';

        var promo_date = role == '1' ? '<input type="text" class="daterange_promo" value="'+data.promo_date+'" />': '<input type="text" class="daterange_promo" value="'+data.promo_date+'" disabled/>';
        /* check web-service type to drop keyword */
        var keyword_layout = data.type_id != 4 ? 'Service Keywords: <i class="keywords">'+ (data.keywords !== null ? data.keywords.join() : '') +'</i> &nbsp; &nbsp;<i class="fa fa-edit basic" title="Click to edit current keywords"></i><br>' : '';

        info.append(
            '<div class="col-sm-4 invoice-col">\
              <strong>BASIC</strong>\
              <address>\
              Date Created : <i>'+data.date_created+'</i><br>\
        Description: <i>'+data.desc+'</i><br>\
        Service Type: <i>'+data.type_name+'</i><br>'+keyword_layout+'\
        '+cross_sell_ref+'\
        Promotion Date: <i>'+promo_date+'</i><br>\
        </address>\
      </div>'
        );

        /* add broadcast data */
        var br_enabled = role == '1' ? ((data.br_allow == 1) ?
            ( service.type == '3' ? 'Enable: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast" data-size="mini" checked disabled><br>' : 'Enable: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast" data-size="mini" checked><br>'):
            (service.type == '3' ? 'Enable: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast" data-size="mini" disabled><br>' : 'Enable: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast" data-size="mini"><br>')
        ) : '';

        var br_type = role == '1' ? ( (data.br_type == 1) ?
            (service.type == '3' ? 'Type: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_type" data-size="mini" data-on-text="Startup" data-off-text="Today" checked disabled><br>' : 'Type: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_type" data-size="mini" data-on-text="Startup" data-off-text="Today" checked><br>' ):
            (service.type == '3' ? 'Type: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_type" data-size="mini" data-on-text="Startup" data-off-text="Today" disabled><br>' : 'Type: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_type" data-size="mini" data-on-text="Startup" data-off-text="Today"><br>')
            ): '';
        var br_length = role == '1' ? ((data.type_id == 1) ?
            (service.type == '3'  ? 'Length: <div class="input-group input-group-sm col-xs-2"><input type="text" class="form-control broadcast_length" value="1" disabled/></div>' : 'Length: <div class="input-group input-group-sm col-xs-2"><input type="text" class="form-control broadcast_length" value="1" /></div>'):
            (service.type == '3'  ? 'Length: <div class="input-group input-group-sm col-xs-2"><input type="text" class="form-control broadcast_length" value="'+data.br_length+'" disabled/></div>' : 'Length: <div class="input-group input-group-sm col-xs-2"><input type="text" class="form-control broadcast_length" value="'+data.br_length+'"/></div>')
        ): '';
        var cross_sell = role == '1' ? ( (in_array([1, 3, 4], data.type_id)) ?
            'Cross Sell:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell" data-size="mini" disabled/><br>':
            (
                (data.br_cross_sell_set == 1) ?
                    'Cross Sell:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell" data-size="mini" checked><br>':
                    'Cross Sell:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell" data-size="mini"><br>'
            )): '';

        var cross_sell_sub = role == '1' ? ((in_array([1, 3, 4], data.type_id)) ?
            'Cross Sell Upon Subscription: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell_sub" data-size="mini" disabled/><br>':
            (
                (data.br_cross_sell_sub_set == 1) ?
                    'Cross Sell Upon Subscription:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell_sub" data-size="mini" checked><br>':
                    'Cross Sell Upon Subscription:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell_sub" data-size="mini"><br>'
            )): '';
        var cross_sell_first = (in_array([1, 3, 4], data.type_id)) ?
            'Up-sell-first: &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell_sub_first" data-size="mini" disabled/><br>':
            (
                (data.br_cross_sell_first_set == 1) ?
                    'Up-sell-first:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell_sub_first" data-size="mini" checked><br>':
                    'Up-sell-first:  &nbsp; &nbsp; &nbsp;<input type="checkbox" class="broadcast_crossell_sub_first" data-size="mini"><br>'
            );
        var br_opening = role == '1' ? ((data.br_opening == 1) ?
            'Service Intro: &nbsp; &nbsp; &nbsp;<input type="checkbox" name="br_opening" data-size="mini" checked><br>':
            'Service Intro: &nbsp; &nbsp; &nbsp;<input type="checkbox" name="br_opening" data-size="mini"><br>'): '';
        var cross_text = role == '1' ? '<i>A welcome message sent to the user upon subscription. If not required unchecked this option</i><br/>\
        TPS Allocated: <div class="input-group input-group-sm col-xs-2"><input type="text" class="form-control broadcast_tps" value="'+data.total_threads+'"/></div>' : '';


            /* drop broadcast setting for web service */
            if (data.type_id != 4){
                info.append(
                    '<div class="col-sm-4 invoice-col">\
                      <strong>BROADCAST</strong>\
                      <address>\
                      '+br_enabled+'\
        '+br_type+'\
        '+br_length+'\
        '+cross_sell+'\
        '+cross_sell_sub+'\
        '+cross_sell_first+'\
        '+br_opening+cross_text+'\
        </address>\
      </div>'
                );
            }

        if (role == '1'){
            /* drop free period for web-service type */
            var free_period = data.type_id != 4 ? 'Free Period: * for a free service set to the duration of the campaign. <div class="input-group input-group-sm col-xs-2"><input type="text" class="form-control sdp_free_period" value="'+data.sp_free_period+'"/></div>' : '';
            var authcode = data.type_id == 4 ? 'AccessCode: <i>'+data.authcode+'</i><br><button class="reset-code">Reset Code</button> &nbsp; &nbsp; &nbsp;<button class="payment-request">Request a payment</button>' : '';


            info.append(
                '<div class="col-sm-4 invoice-col">\
                  <strong>SDP Config</strong>\
                  <address>\
                  SP ID: <i>'+data.sp_id+'</i><br>\
        SP PASSWORD: <i>'+data.sp_password+'</i><br>\
        Service ID: <i>'+data.sp_service_id+'</i><br>\
        ShortCode: <i>'+data.sp_shortcode+'</i><br>'+free_period+''+authcode+'\
        </address>\
       </div>'
            );
        }


    } else {
        /* service draw */
        var service_draw_promo = '<input type="text" name="draw_range_date" value="'+data.draw_date_range+'" />';
        var service_draw_winner = '<input type="text" name="draw_num" value="'+data.draw_num+'"/>';
        //row 1
        info.append(
            '<div class="col-sm-4 invoice-col">\
            <strong>&nbsp;</strong>\
            <address>\
            Date Created : <i>'+data.date_created+'</i><br>\
            Description: <i>'+data.desc+'</i><br>\
            Promotion Date: <i>'+service_draw_promo+'</i><br>\
            Notify: <i><textarea rows="2" cols="30" name="notify">'+ data.notify+'</textarea></i><br/>\
            Winners per Draw : <div class="input-group input-group-sm col-xs-2">'+service_draw_winner+'</div>\
            </address>\
          </div>'
        );
        /* layout the linked services */
        var draw_linked_layout = '';
        for(var key in service_list){
            if (service_list.hasOwnProperty(key)){
                if (in_array(data.services_draw_linked, service_list[key].id)){
                    draw_linked_layout += role == '1' ?
                    '<input type="checkbox" name="services_draws_linked" value="'+service_list[key].id+'" checked> '+ service_list[key].name+ ' &nbsp;':
                    '<input type="checkbox" name="services_draws_linked" value="'+service_list[key].id+'" checked disabled> '+ service_list[key].name+ ' &nbsp;';
                } else {
                    draw_linked_layout += role == '1' ?
                    '<input type="checkbox" name="services_draws_linked" value="'+service_list[key].id+'"> '+ service_list[key].name+ ' &nbsp;':
                    '<input type="checkbox" name="services_draws_linked" value="'+service_list[key].id+'" disabled> '+ service_list[key].name+ ' &nbsp;';
                }
            }
        }
        /* layout draw type */
        var dr_type_obj = {'Daily': 1, 'Weekly': 2, 'Monthly': 3};
        var draw_type_opt = '';
        for(var i in dr_type_obj){
            if(dr_type_obj.hasOwnProperty(i)){
                draw_type_opt += (data.draw_type_id == dr_type_obj[i]) ?
                '<option value="'+ dr_type_obj[i]+'" selected>'+ i+ '</option>':
                '<option value="'+ dr_type_obj[i]+'">'+ i+ '</option>';
            }
        }
        // Row 2
        info.append(
            '<div class="col-sm-4 invoice-col">\
              <strong>&nbsp;</strong>\
              <address>\
              <label>Draw associated services:</label><br/>\
              <span id="services_draws_linked">'+draw_linked_layout+'</span><br/>\
              *Add services that the draw will be linked to<br/>\
              <label>Raffle Draw Type:</label><br/>\
              <select class="form-control" name="draw_type" disabled>\
              '+draw_type_opt+'\
              </select>\
            </address>\
            </div>'
        );

        var draw_engine_type = (data.draw_engine_type == 1) ? '<input type="checkbox" name="draw_engine_type" data-size="mini" data-on-text="Auto" data-off-text="Manual" checked disabled>':
            '<input type="checkbox" name="draw_engine_type" data-size="mini" data-on-text="Auto" data-off-text="Manual" disabled>';
        var draw_win_rollout = (data.draw_win_rollout == 1) ? ( role == '1' ? '<input type="checkbox" name="draw_win_rollout" data-size="mini" data-on-text="Once" data-off-text="Always" checked>' : '<input type="checkbox" name="draw_win_rollout" data-size="mini" data-on-text="Once" data-off-text="Always" checked disabled>'):
            ( role == '1' ? '<input type="checkbox" name="draw_win_rollout" data-size="mini" data-on-text="Once" data-off-text="Always">' : '<input type="checkbox" name="draw_win_rollout" data-size="mini" data-on-text="Once" data-off-text="Always" disabled>');
        // Row 3
        info.append(
            '<div class="col-sm-4 invoice-col">\
              <strong>&nbsp;</strong>\
              <address>\
              <label>Draw Engine Type:</label><br/>\
              '+draw_engine_type+'<br\>\
            <i>*Select whether the system will automatically run the draw or manually done by an admin.</i><br/>\
            <label>Winners Rollout:</label><br/>\
              '+draw_win_rollout+'<br\>\
            <i>\
            </i>\
            </address>\
            </div>'
        );
    }

    gif_loader_body.hide();
}

/* set service message */
function set_service_messages(el, data, current_service, index){
    var panel = el.find('.panel:nth-child('+ index +')');
    var references = panel.find('.service-reference');
    /* compile service messages */
    var data_messages = '';
    if (current_service.type == 1){
        data_messages = '<tr>\
                            <td>Cross Sell</td>\
                            <td>'+current_service.name+'</td>\
                            <td>'+data.message+'</td>\
                            <td>'+data.correct+'</td>\
                            <td>'+data.incorrect+'</td>\
                            <td><i class="fa fa-edit reference" title="Click to edit current reference"></i></td>\
                         </tr>';
    } else {
        for(var key in data){
            if (data.hasOwnProperty(key)){
                data_messages += '<tr>\
                                    <td>'+key+'</td>\
                                    <td>'+current_service.name+'</td>\
                                    <td>'+ (data[key] == null ? '' : data[key].message) +'</td>\
                                    <td>&nbsp;</td>\
                                    <td>&nbsp;</td>\
                                    <td><i class="fa fa-edit reference" title="Click to edit current reference"></i></td>\
                                </tr>';
            }
        }
    }

    if (current_service.type != 4){
        references.append(
            '<div class="col-xs-12 table-responsive">\
                <table class="table table-striped">\
                    <thead>\
                        <tr>\
                            <th>Type</th>\
                            <th>Service</th>\
                            <th>Message</th>\
                            <th>Correct Response</th>\
                            <th>Incorrect Response</th>\
                            <th>Action</th>\
                        </tr>\
                    </thead>\
                    <tbody>\
                    '+data_messages+'\
                </tbody>\
            </table>\
        </div>'
        );
    }
}

/* set service content related */
function set_service_contents(el, data, current_service, index){
    var panel = el.find('.panel:nth-child('+ index +')');
    var contents = panel.find('.service-contents');

    if (current_service.type == 1){
        var table_data = '';
        if (data != null){
            for(var i=0; i < data.length; ++i){
                table_data += '<tr content_id="'+data[i].id+'">\
                                <td>'+data[i].message+'</td>\
                                <td>'+data[i].date_created+'</td>\
                                <td><i class="fa fa-edit service-content"></i></td>\
                            </tr>';
            }
        }
        table_data = (table_data.length > 0) ? table_data : '<tr content_id="0">\
                                                                <td>No content available. Click on Upload button to load contents</td>\
                                                                <td>&nbsp;</td>\
                                                                <td><i class="fa fa-edit service-content"></i></td>\
                                                            </tr>';
        contents.append(
            '<div class="col-xs-12 table-responsive">\
                <table class="table table-striped">\
                    <thead>\
                        <tr>\
                        <th>Message</th>\
                        <th>Date</th>\
                        <th>Action</th>\
                        </tr>\
                    </thead>\
                    '+table_data+'\
                    <tbody>\
                    </tbody>\
                </table>\
            </div>'
        );
    } else {
        var table_data = '';
        if (data != null){
            for(var i=0; i < data.length; ++i){
                table_data += '<tr content_id="'+data[i].id+'">\
                                <td>'+data[i].question+'</td>\
                                <td>'+data[i].answer+'</td>\
                                <td>'+data[i].correct+'</td>\
                                <td>'+data[i].incorrect+'</td>\
                                <td>'+data[i].score+'</td>\
                                <td>'+data[i].date_created+'</td>\
                                <td><i class="fa fa-edit service-content-trivia"></i></td>\
                            </tr>';
            }
        }
        table_data = (table_data.length > 0) ? table_data : '<tr content_id="0">\
                                                                <td>No content available. Click on Upload button to load contents</td>\
                                                                <td>&nbsp;</td>\
                                                                <td>&nbsp;</td>\
                                                                <td>&nbsp;</td>\
                                                                <td>&nbsp;</td>\
                                                                <td>&nbsp;</td>\
                                                                <td><i class="fa fa-edit service-content"></i></td>\
                                                            </tr>';
        if (current_service.type != 4){
            contents.append(
                '<div class="col-xs-12 table-responsive">\
                    <table class="table table-striped">\
                        <thead>\
                            <tr>\
                            <th>Question</th>\
                            <th>Answer</th>\
                            <th>Correct Response</th>\
                            <th>Incorrect Response</th>\
                            <th>Score</th>\
                            <th>Date</th>\
                            <th>Action</th>\
                            </tr>\
                        </thead>\
                        <tbody>\
                            '+table_data+'\
                    </tbody>\
                </table>\
            </div>'
            );
        }
    }
}



/* initialise services panel events and behaviours handlers */
var global_panel = $('#accordion').find('.panel');
$(function(){
    global_panel.each(function(){
        /* initialise names */
        var service_name = $(this).find('.box-title a').text();
        var service_id = $(this).find('.box-title a').next().text();

        /* Basic: editing */
        $(this).find('.basic').off('click').on('click', {id:service_id, name:service_name}, function(e){
            /* keywords */
            if ($(this).prev().hasClass('keywords')){
                if (role == '1'){
                    editing_modal(keywords_controller($(this).prev().text()), e.data.name + ': Keywords');
                    controller_action('keywords', e.data.id);
                }
            }
            /* cross sell reference */
            if ($(this).prev().hasClass('crossell')){
                if (role == '1'){
                    editing_modal(crossell_controller($(this).prev().text()), e.data.name + ': Crossell Service Reference');
                    controller_action('crossell', e.data.id);
                }
            }
        });

        /* service message editing */
        $(this).find('.reference').off('click').on('click', {id:service_id, name:service_name}, function(e){
            var parent = $(this).parent().parent();
            var references = [];
            parent.children().each(function(){
                references.push($(this).text());
            });
            if (references.length > 0){
                editing_modal( message_controller(references), e.data.name + ': Service Messages');
                controller_action('messages_reference', e.data.id, {type: references[0], target_service: references[1]});
            }
        });

        /* service contents editing */
        $(this).find('.service-contents div table').DataTable();
        $(this).find('.service-content-trivia').off('click').on('click', {id:service_id, name:service_name}, function(e){
            var parent = $(this).parent().parent();
            var references = [];
            parent.children().each(function(){
                references.push($(this).text());
            });
            /* add content_id */
            references.push(parent.attr('content_id'));
            if (references.length > 0){
                editing_modal( content_controller_trivia(references), e.data.name + ': Service Content Trivia');
                controller_action('messages_content_trivia', e.data.id);
            }
        });

        $(this).find('.service-content').off('click').on('click', {id:service_id, name:service_name}, function(e){
            var parent = $(this).parent().parent();
            var references = [];
            parent.children().each(function(){
                references.push($(this).text());
            });
            /* add content_id */
            references.push(parent.attr('content_id'));
            if (references.length > 0){
                editing_modal( content_controller(references), e.data.name + ': Service Content');
                controller_action('messages_content', e.data.id);
            }
        });

        /* upload contents */
        $(this).find('.content-upload').off('click').on('click', {id:service_id, name:service_name}, function(e){
            editing_modal( content_upload_controller(service_id), e.data.name + ': Content Upload');
            controller_action('content_upload', e.data.id);
        });
    });
});



/* service keywords controllers */
function keywords_controller(data){
    var keywords = (data == undefined)  ? [] : data.split(',');
    var keyword_list = '';

    for(var i=0; i < keywords.length; ++i){
        keyword_list += '<p><input type="checkbox" value="'+keywords[i]+'"/> '+keywords[i]+'</p>';
    }

    return '<div class="row" id="keywords">\
                <div class="col-md-6">\
                '+ keyword_list +'\
                </div>\
                <div class="col-md-6">\
                    <label for="keywords">Add keywords:</label>\
                    <input type="text" class="form-control" placeholder="Separate multiple keywords with a comma"/>\
                </div>\
             </div>';
}

/* service crossell controller */
function crossell_controller(data){
    var crossell = (data == undefined)  ? [] : data.split(',');
    var crossell_list = '';

    for(var i=0; i < crossell.length; ++i){
        crossell_list += '<p><input type="checkbox" value="'+crossell[i]+'"/> '+crossell[i]+'</p>';
    }
    /* prepare menu list of related type */
    var options = '<option></option>';
    for(var key in service_list){
        if (service_list.hasOwnProperty(key)){
            if (service_list[key].type == 1){
                if (!in_array(crossell, service_list[key].name)){
                    options += '<option value="'+service_list[key].id+'"> '+service_list[key].name+'</option>';
                }
            }
        }
    }

    return '<div class="row" id="crossell">\
                <div class="col-md-6">\
                '+ crossell_list +'\
                </div>\
                <div class="col-md-6">\
                    <label>Select a content service to cross reference:</label>\
                    <select class="form-control">'+options+'</select>\
                </div>\
             </div>';
}

/* service message controller */
function message_controller(data){

    return '<div class="row">\
                <div class="col-md-12">\
                    <b>You are currently editing message reference type : '+data[0]+'</b>\
                </div>\
                <div class="col-md-12" id="references">\
                    <label for="message">Message:</label>\
                    <input type="text" class="form-control" name="message" value="'+data[2]+'"/>\
                    <label for="message">Correct Message:</label>\
                    <input type="text" class="form-control" name="correct" value="'+data[3]+'"/>\
                    <label for="message">Incorrect Message:</label>\
                    <input type="text" class="form-control" name="incorrect" value="'+data[4]+'"/>\
                </div>\
              </div>';
}
/* service content controller */
function content_controller_trivia(data){

    return '<div class="row">\
                <div class="col-md-12" id="service_content">\
                    <input type="hidden" name="content_id" value="'+data[7]+'"/>\
                    <label for="message">Question:</label>\
                    <input type="text" class="form-control" name="question" value="'+data[0]+'"/>\
                    <label for="message">Answer:</label>\
                    <input type="text" class="form-control" name="answer" value="'+data[1]+'"/>\
                    <label for="message">correct Response:</label>\
                    <input type="text" class="form-control" name="correct" value="'+data[2]+'"/>\
                    <label for="message">incorrect Response:</label>\
                    <input type="text" class="form-control" name="incorrect" value="'+data[3]+'"/>\
                    <label for="message">Score:</label>\
                    <input type="text" class="form-control" name="score" value="'+data[4]+'"/>\
                    <label for="message">Date:</label>\
                    <input type="text" class="form-control" name="date" value="'+data[5]+'" disabled/>\
                </div>\
              </div>';
}
function content_controller(data){

    return '<div class="row">\
                <div class="col-md-12" id="service_content">\
                    <input type="hidden" name="content_id" value="'+data[3]+'"/>\
                    <label for="message">Message:</label>\
                    <input type="text" class="form-control" name="content_message" value="'+data[0]+'"/>\
                    <label for="message">Date:</label>\
                    <input type="text" class="form-control" name="date" value="'+data[1]+'" disabled/>\
                </div>\
              </div>';
}

/* content upload controller */
function content_upload_controller(service_id){

    /* determine load value as per the service given */
    var broadcast_length = 0;
    //alert(JSON.stringify(service_list) + '| ID:'+service_id);

    for(var key in service_list){
        if (service_list.hasOwnProperty(key)){
            if (service_list[key].type == 1){
                if (service_id == service_list[key].id) {
                    broadcast_length = service_list[key].length;
                    break;
                }
            } else {
                if (service_id == service_list[key].id) {
                    broadcast_length = service_list[key].length;
                    break;
                }
            }
        }
    }

    return '<div class="row">\
                <div class="col-md-10" id="content_upload">\
                    <div class="form-group">\
                        <label>Upload a CSV file</label>\
                        <div class="input-group">\
                            <span class="input-group-btn">\
                                <span class="btn btn-default btn-file">Browse... <input type="file" id="upload-file"></span>\
                            </span>\
                            <input type="text" class="form-control" readonly>\
                        </div>\
                    </div>\
                </div>\
                <div class="col-md-2">\
                    <label>Broadcast Length:</label>\
                    <input type="text" class="form-control" name="load" value="'+ broadcast_length+'" disabled/>\
                </div>\
            </div>';
}

/* payment request controller */
function content_payment_request_controller(service_id){
    /* locate the current service accesscode */
    var basic_data = {data:null};
    get_service_basic_settings(service_id, basic_data, 'data_basic');
    //alert (basic_data.data.authcode);
    return '<div class="row" id="content_webservice">\
                <div class="col-md-3">\
                    <label>MSISDN:</label>\
                    <input type="text" class="form-control" name="msisdn" placeholder="e.g.: 066269100"/>\
                    <input type="hidden" name="authcode" value="'+ basic_data.data.authcode+'"/>\
                </div>\
                <div class="col-md-3">\
                    <label>Product no:</label>\
                    <input type="text" class="form-control" name="productno" placeholder="e.g.: 101, 203"/>\
                </div>\
                <div class="col-md-3">\
                    <label>Amount:</label>\
                    <input type="text" class="form-control" name="amount" placeholder="e.g.: 100"/>\
                </div>\
                <div class="col-md-3">\
                    <label>Select Request Type:</label><br/>\
                    <input type="radio" name="type" value="1" checked/>&nbsp; &nbsp; Payment &nbsp;&nbsp; <input type="radio" name="type" value="2"/>&nbsp;Deposit\
                </div>\
            </div>';
}

/* initialise related controller action */
function controller_action(type, service_id, params) {
    var server_report = null;
    var modal = $('#editing');
    var save_btn = $('button:contains("Save changes")');
    var delete_btn = $('button:contains("Delete")');
    var display_report = modal.find('.modal-body > p');
    switch (type) {
        case 'keywords':
            var keywords = $('#keywords');
            /* update request */
            save_btn.off('click').on('click', function () {
                gif_loader.show();
                var words_list = is_valid_inputs(keywords.find(':text').val(), 'keywords');

                /* check words are valid */
                if (typeof  words_list == 'object') {
                    /* check object type */
                    var data_found = false;
                    for (key in words_list) {
                        if (words_list.hasOwnProperty(key)) {
                            if (key == 'error') {
                                display_report.removeClass('success_editing').addClass('error_editing').text(words_list.error);
                                gif_loader.hide();
                            } else {
                                data_found = true;
                            }
                        }
                    }
                    if (data_found) {
                        server_report = add_service_related_features(words_list, service_id, 'keywords', 'update', 'service');
                        if (server_report.error) {
                            display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                        } else {
                            display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                        }

                        gif_loader.hide();
                    }
                } else {
                    server_report = add_service_related_features(words_list, service_id, 'keywords', 'update', 'service');
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                    gif_loader.hide();
                }
            });

            /* delete request */
            delete_btn.off('click').on('click', function () {
                gif_loader.show();
                var checked_keywords = [];
                keywords.find('input:checked').each(function () {
                    checked_keywords.push($(this).val());
                });
                if (checked_keywords.length > 0) {
                    server_report = add_service_related_features(checked_keywords, service_id, 'keywords', 'delete', 'service');
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                    gif_loader.hide();
                } else {
                    display_report.removeClass('success_editing').addClass('error_editing').text('Please select keywords to delete');
                    gif_loader.hide();
                }
            });
            break;
        case 'crossell':
            var crossell = $('#crossell').find('select');
            /* update request */
            save_btn.off('click').on('click', function () {
                gif_loader.show();
                var selected_service_id = crossell.find('option:selected').val();
                if (selected_service_id.length > 0) {
                    server_report = add_service_related_features(selected_service_id, service_id, 'crossell', 'update', 'service');
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                } else {
                    display_report.removeClass('success_editing').addClass('error_editing').text('Please select a service in the list to perform this request');
                }
                gif_loader.hide();
            });

            /* delete request */
            delete_btn.off('click').on('click', function () {
                gif_loader.show();
                var service_ids = [];
                $('#crossell').find('input:checked').each(function () {
                    service_ids.push($(this).val());
                });
                if (service_ids.length > 0) {
                    server_report = add_service_related_features(service_ids, service_id, 'crossell', 'delete', 'service');
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                    gif_loader.hide();
                } else {
                    display_report.removeClass('success_editing').addClass('error_editing').text('Please select a service to remove from cross sell reference');
                    gif_loader.hide();
                }
            });
            break;
        case 'messages_reference':
            var reference = $('#references');
            /* update request */
            save_btn.off('click').on('click', function () {
                var messages = is_valid_inputs(reference.find('[name="message"]').val(), 'messages');
                var type_list = ['Welcome', 'Good Score', 'Poor Score', 'Last Play', 'Never Play', 'Excellent Score'];
                var correct = (in_array(type_list, params.type)) ? reference.find('[name="correct"]').val() : is_valid_inputs(reference.find('[name="correct"]').val(), 'messages');
                var incorrect = (in_array(type_list, params.type)) ? reference.find('[name="incorrect"]').val() : is_valid_inputs(reference.find('[name="incorrect"]').val(), 'messages');

                gif_loader.show();
                /* check valid data */
                if (typeof messages == 'object') {
                    display_report.removeClass('success_editing').addClass('error_editing').text(messages.error);
                } else if (typeof correct == 'object') {
                    display_report.removeClass('success_editing').addClass('error_editing').text(correct.error);
                } else if (typeof incorrect == 'object') {
                    display_report.removeClass('success_editing').addClass('error_editing').text(incorrect.error);
                } else {
                    server_report = add_service_related_features(
                        {
                            'message': messages,
                            'correct': correct,
                            'incorrect': incorrect
                        },
                        service_id,
                        'messages_reference',
                        params,
                        'service'
                    );
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                }
                gif_loader.hide();
            });
            /* delete request */
            delete_btn.off('click').on('click', function () {
                display_report.removeClass('success_editing').addClass('error_editing').text('Deleting Service Message is not allowed!');
            });
            break;
        case 'messages_content_trivia':
            var message_content_trivia = $('#service_content');
            /* update request */
            save_btn.off('click').on('click', function () {
                var question = is_valid_inputs(message_content_trivia.find('[name="question"]').val(), 'messages');
                var answer = is_valid_inputs(message_content_trivia.find('[name="answer"]').val(), 'response_trivia');
                var correct = is_valid_inputs(message_content_trivia.find('[name="correct"]').val(), 'messages');
                var incorrect = is_valid_inputs(message_content_trivia.find('[name="incorrect"]').val(), 'messages');
                var score = is_valid_inputs(message_content_trivia.find('[name="score"]').val(), 'score_trivia');
                var date = message_content_trivia.find('[name="date"]').val();
                var content_id = message_content_trivia.find('[name="content_id"]').val();
                gif_loader.show();
                /* check valid data */
                if (typeof question == 'object'){
                    display_report.removeClass('success_editing').addClass('error_editing').text(question.error);
                } else if (typeof answer == 'object'){
                    display_report.removeClass('success_editing').addClass('error_editing').text(answer.error);
                } else if (typeof  correct == 'object'){
                    display_report.removeClass('success_editing').addClass('error_editing').text(correct.error);
                } else if (typeof  incorrect == 'object'){
                    display_report.removeClass('success_editing').addClass('error_editing').text(incorrect.error);
                } else if (typeof  score == 'object'){
                    display_report.removeClass('success_editing').addClass('error_editing').text(score.error);
                } else {
                    server_report = add_service_related_features(
                        {
                            'question': question,
                            'answer': answer,
                            'correct': correct,
                            'incorrect': incorrect,
                            'score': score,
                            'date': date,
                            'content_id' : content_id
                        },
                        service_id,
                        'messages_content',
                        'update',
                        'service'
                    );
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                }
                gif_loader.hide();
            });

            /* delete request */
            delete_btn.off('click').on('click', function () {
                display_report.removeClass('success_editing').addClass('error_editing').text('Deleting Service content is not allowed!');
            });
            break;
        case 'messages_content':
            var message_content = $('#service_content');
            /* update request */
            save_btn.off('click').on('click', function () {
                var message = is_valid_inputs(message_content.find('[name="content_message"]').val(), 'messages');
                var date = message_content.find('[name="date"]').val();
                var content_id = message_content.find('[name="content_id"]').val();
                gif_loader.show();
                /* check valid data */
                if (typeof message == 'object'){
                    display_report.removeClass('success_editing').addClass('error_editing').text(message.error);
                } else {
                    server_report = add_service_related_features(
                        {
                            'message': message,
                            'date': date,
                            'content_id' : content_id
                        },
                        service_id,
                        'messages_content',
                        'update',
                        'service'
                    );
                    if (server_report.error) {
                        display_report.removeClass('success_editing').addClass('error_editing').text(server_report.error);
                    } else {
                        display_report.removeClass('error_editing').addClass('success_editing').text(server_report.success);
                    }
                }
                gif_loader.hide();
            });

            /* delete request */
            delete_btn.off('click').on('click', function () {
                display_report.removeClass('success_editing').addClass('error_editing').text('Deleting Service content is not allowed!');
            });
            break;
        case 'content_upload':
            var file_input = $('.btn-file :file');
            file_input.off('change').on('change', function(){
                var input = $(this),
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [label]);
            });

            file_input.off('fileselect').on('fileselect', function(event, label) {
                var input = $(this).parents('.input-group').find(':text');
                input.val(label);
            });

            /* process upload */
            save_btn.off('click').on('click', function () {
                var file = $('#upload-file')[0].files[0];
                var broadcast_length = $('#content_upload').next().find('[name="load"]').val();

                if (file == undefined){
                    display_report.removeClass('success_editing').addClass('error_editing').text('Enable to process request - no file is attached');
                } else {
                    display_report.removeClass('error_editing').addClass('success_editing').text('Uploading file '+file.name+' ...');
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append("service_id", service_id);
                    formData.append("load_perday", broadcast_length);
                    $.ajax({
                        url: 'http://'+server+'/rest-api/tools/services/upload/',
                        type: "POST",
                        data: formData,
                        cache: false,
                        processData: false,  // tell jQuery not to process the data
                        contentType: false,   // tell jQuery not to set contentType
                        dataType: 'json',
                        success: function(result){
                            //alert(JSON.stringify(result));
                            if (result.error){
                                display_report.removeClass('success_editing').addClass('error_editing').text(result.error);
                            } else {
                                /* check an error encountered during uploading process */
                                if (result.fail){
                                    var details = result.fail;
                                    var list = '<textarea rows="4" cols="80" style="color:black;">';
                                    for(key in details){
                                        if (details.hasOwnProperty(key)){
                                            list += 'content ID:'+ details[key].id+ ' - '+details[key].detail+'\n';
                                        }
                                    }
                                    list += '</textarea><br/><button style="color:black;">Save</button>';
                                    display_report.removeClass('error_editing').addClass('success_editing').html(
                                        '<span>'+result.result+'</span><br>'+list
                                    );
                                    /* initialise save textarea btn */
                                    display_report.find('button').on('click', {error: display_report.find('textarea').html()}, function(e){
                                        request_printing_CSV('MSIC', null, e.data.error);
                                    });

                                } else {
                                    display_report.removeClass('error_editing').addClass('success_editing').text(result.result);

                                }
                            }
                        },
                        error: function(){
                            display_report.removeClass('success_editing').addClass('error_editing').text('Error connecting to the server');
                        }
                    });
                }
            });
            break;
        case 'process_webservice':
            save_btn.off('click').on('click', function () {
                var webservice = $('#content_webservice');
                var request_type = webservice.find('[name="type"]:checked').val();
                var msisdn = webservice.find('[name="msisdn"]').val();
                var productno = webservice.find('[name="productno"]').val();
                var amount = webservice.find('[name="amount"]').val();
                var authcode = webservice.find('[name="authcode"]').val();
                var allow_process = false;

                if (request_type == '2'){
                    var r = window.confirm('You are about to request deposit payment to  ' + msisdn +'. Are you sure about this?');
                    if (r == true){
                        allow_process = true;
                    }
                } else {
                    allow_process = true;
                }

                if (allow_process){
                    /* Process request type */
                    display_report.removeClass('error_editing').addClass('success_editing').text('Processing your request. Please wait ...');

                    $.ajax({
                        url: 'http://'+server+'/gateway/payment/',
                        type: 'POST',
                        data: JSON.stringify({
                            type:  request_type == '1' ? 'paymentRequest' : 'depositRequest',
                            accesscode: authcode,
                            appname: loginID,
                            parameters: {
                                accno: '1000',
                                msisdn: msisdn,
                                productno: productno,
                                amount: amount
                            }
                        }),
                        contentType: 'application/json; charset=utf-8',
                        dataType: 'json',
                        success: function(result) {
                            // do something with the result
                            //alert(JSON.stringify(result));
                            if (result.error){
                                display_report.removeClass('success_editing').addClass('error_editing').text(JSON.stringify(result.error));
                            } else {
                                display_report.removeClass('error_editing').addClass('success_editing').text(JSON.stringify(result.success));
                            }
                        },
                        error: function(){
                            // Can't reach the resource
                            display_report.removeClass('success_editing').addClass('error_editing').text('Error connecting to the server. Please check your computer is properly connected to the internet or if the problem persists contact dev');
                        }
                    });
                }
            });
            break;
    }
    /* clear display_report */
    $('button:contains("Close")').off('click').on('click', function () {
        display_report.text('');
        /* reload the page on success update */
        if (typeof server_report == 'object' && server_report.success.match('successfully') !== null) {
            refresh_page();
        }
    });
}


/* add related service features */
function add_service_related_features(data, service_id, category, process, type){
    var report = null;
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/services/update/',
        type: 'POST',
        data: JSON.stringify({
            category: category,
            data: data,
            service_id: service_id,
            process: process,
            type : type
        }),
        beforeSend: function() {
            if (is_modal(category)){
                gif_loader.show();
            } else {
                gif_loader_body.show()
            }
        },
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        async: false,
        success: function(response) {
            if (is_modal(category)){
                report = (response.error) ? {'error': response.error} : {'success': response.data};
            } else {
                if (response.error){
                    alert_modal(response.error, 3);
                    gif_loader_body.hide();
                    report = false;
                } else {
                    alert_modal(response.data, 1);
                    gif_loader_body.hide();
                    report = true;
                }
            }
        },
        error:function(){
            if (is_modal(category)){
                report = {'error':'Error updating your request. The server is unavailable. Please check you are connected to the internet.'};
            } else {
                alert_modal('Error updating your request. The server is unavailable. Please check you are connected to the internet.', 3);
                gif_loader_body.hide();
                report = false;
            }
        }
    });
    return report;
}


/* validate user inputs */
function is_valid_inputs(data, type){
    var is_number = false;
    switch (type){
        case 'keywords':
            if (data.trim().length > 0){
                if (data.match(',') !== null){
                    var l = data.split(',');
                    for(var i=0; i < l.length; ++i){
                        if (!isNaN(l[i].trim())){ is_number = true;}
                    }
                    if (is_number){ return {'error':'Please note that numbers are not supported as keywords. Please review'};}
                    else {
                        return l;
                    }
                } else {
                    if (!isNaN(data.trim())){ is_number = true;}
                    if (is_number){ return {'error':'Please note that numbers are not supported as keywords. Please review'};}
                    else {
                        return data;
                    }
                }
            } else {
                return {'error':'Please enter keywords before saving changes'};
            }
            break;
        case 'messages':
            if (data.trim().length > 0){
                if (!isNaN(data.trim())){ is_number = true;}
                if (is_number){ return {'error':'Please note that numbers are not supported as content. Please review'};}
                else {
                    return data;
                }
            } else {
                return {'error':'Your input is empty. Please review.'};
            }
            break;
        case 'response_trivia':
            if (data.trim().length > 0){
                if (data.trim().length < 2){
                    if (!isNaN(data.trim())){ is_number = true;}
                    if (is_number){ return {'error':'Please note that numbers are not supported as content. Please review'};}
                    else {
                        return data;
                    }
                } else {
                    return {'error':'Answer input can only take one character. Please review.'};
                }
            } else {
                return {'error':'Your input is empty. Please review.'};
            }
            break;
        case 'score_trivia':
            if (data.trim().length > 0){
                if (!isNaN(data.trim())){ is_number = true;}
                if (is_number) {
                    if (parseInt(data) < 11){
                        return data;
                    } else {
                        return {'error':'Please give a score of 0 to 10.'};
                    }
                } else {
                    return {'error':'Please note that word as score is unsupported. Please review'};
                }
            } else {
                return {'error':'Your input is empty. Please review.'};
            }
            break;
        case 'broadcast_length':
        case 'broadcast_tps':
        case 'sdp_bill_rate':
        case 'sdp_free_period':
            if (data.trim().length > 0){
                if (data.match(',') !== null){
                    return {'error':'Incorrect number format. Please enter numeric value.'};
                } else {
                    if (isNaN(data.trim())){ is_number = true;}
                    if (is_number){ return {'error':'Incorrect number format. Please enter numeric value.'};}
                    else {
                        return data;
                    }
                }
            } else {
                return {'error':'Your input is empty. Please review.'};
            }
            break;
    }
}

/* check if it's an array */
function in_array(arg_array, data){
    if (arg_array.length > 0){
        for(var i=0; i < arg_array.length; ++i){
            if (
                (typeof  arg_array[i] == 'number' ? arg_array[i] : arg_array[i].trim()) == ( typeof data == 'number' ? data: data.trim())
            ){return true;}
        }
    }
    return false;
}

/* check if is modal category request */
function is_modal(category){
    var list = ['crossell', 'keywords','messages_reference', 'messages_content'];
    for(var i =0; i < list.length; ++i){
        if (category == list[i]){
            return true;
        }
    }
    return false;
}


/* extra panel triggers */
$(function(){
    global_panel.each(function(){
        var service_id = $(this).find('.box-title a').next().text();
        var service_name = $(this).find('.box-title a').text();
        var panel = $(this);
        /*** service promo date range ***/
        panel.find('.daterange_promo').daterangepicker();
       /* trigger an action event */
        panel.find('.daterange_promo').on('apply.daterangepicker', {id: service_id}, function(e, picker){
            /*update the promo date range */
            add_service_related_features(picker.startDate.format('YYYY-MM-DD') + '|'+  picker.endDate.format('YYYY-MM-DD'), e.data.id, 'date_promo', 'update', 'service');
        });

        /*** service broadcast switch ***/
        panel.find('.broadcast').bootstrapSwitch();
        /* trigger an action event */
        panel.find('.broadcast').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state) {
            if (state){
                if (add_service_related_features(1, e.data.id, 'broadcast', 'update', 'service')){
                    panel.removeClass('box-danger').addClass('box-success');
                }
            } else {
                if (add_service_related_features(0, e.data.id, 'broadcast', 'update', 'service')){
                    panel.removeClass('box-success').addClass('box-danger');
                }
            }
        });
        /*** service broadcast type ***/
        panel.find('.broadcast_type').bootstrapSwitch();
        panel.find('.broadcast_type').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state) {
            if (state){
                add_service_related_features(1, e.data.id, 'broadcast_type', 'update', 'service');
            } else {
                add_service_related_features(2, e.data.id, 'broadcast_type', 'update', 'service');
            }
        });

        /*** service broadcast length ***/
        panel.find('.broadcast_length').on('change', {id: service_id}, function(e){
            var result = is_valid_inputs($(this).val(), 'broadcast_length');
            if (typeof result == 'object'){
                alert_modal(result.error, 3);
            } else {
                add_service_related_features($(this).val(), e.data.id, 'broadcast_length', 'update', 'service');
            }
        });

        /*** service broadcast crossell ***/
        panel.find('.broadcast_crossell').bootstrapSwitch();
        panel.find('.broadcast_crossell').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state) {
            if (state){
                add_service_related_features(1, e.data.id, 'broadcast_crossell', 'update', 'service');
            } else {
                add_service_related_features(0, e.data.id, 'broadcast_crossell', 'update', 'service');
            }
        });

        /*** service broadcast crossell sub ***/
        panel.find('.broadcast_crossell_sub').bootstrapSwitch();
        panel.find('.broadcast_crossell_sub').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state) {
            if (state){
                add_service_related_features(1, e.data.id, 'broadcast_crossell_sub', 'update', 'service');
            } else {
                add_service_related_features(0, e.data.id, 'broadcast_crossell_sub', 'update', 'service');
            }
        });

        /*** service broadcast crossell first on service interaction ***/
        panel.find('.broadcast_crossell_sub_first').bootstrapSwitch();
        panel.find('.broadcast_crossell_sub_first').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state) {
            if (state){
                add_service_related_features(1, e.data.id, 'broadcast_crossell_sub_first', 'update', 'service');
            } else {
                add_service_related_features(0, e.data.id, 'broadcast_crossell_sub_first', 'update', 'service');
            }
        });

        /*** service broadcast tps ***/
        panel.find('.broadcast_tps').on('change', {id: service_id}, function(e){
            var result = is_valid_inputs($(this).val(), 'broadcast_tps');
            if (typeof result == 'object'){
                alert_modal(result.error, 3);
            } else {
                add_service_related_features($(this).val(), e.data.id, 'broadcast_tps', 'update', 'service');
            }
        });

        /*** service broadcast opening ***/
        panel.find('[name="br_opening"]').bootstrapSwitch();
        panel.find('[name="br_opening"]').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state){
            if (state){
                add_service_related_features(1, e.data.id, 'broadcast_opening', 'update', 'service');
            } else {
                add_service_related_features(0, e.data.id, 'broadcast_opening', 'update', 'service');
            }
        });

        /*** service sdp bill rate ***/
        panel.find('.sdp_bill_rate').on('change', {id: service_id}, function(e){
            var result = is_valid_inputs($(this).val(), 'sdp_bill_rate');
            if (typeof result == 'object'){
                alert_modal(result.error, 3);
            } else {
                if (!add_service_related_features($(this).val(), e.data.id, 'sdp_bill_rate', 'update', 'service')){
                    window.setTimeout(function(){
                        refresh_page();
                    }, 2000);
                }
            }
        });
        /*** service sdp free period ***/
        panel.find('.sdp_free_period').on('change', {id: service_id}, function(e){
            var result = is_valid_inputs($(this).val(), 'sdp_free_period');
            if (typeof result == 'object'){
                alert_modal(result.error, 3);
            } else {
                if (!add_service_related_features($(this).val(), e.data.id, 'sdp_free_period', 'update', 'service')){
                    window.setTimeout(function(){
                        refresh_page();
                    }, 2000);
                }
            }
        });

        /*** web service reset code ***/
        panel.find('.reset-code').on('click', {id:service_id, name:service_name}, function(e){
            var r = window.confirm('You are about to reset ' + e.data.name+' - accesscode. Are you sure about this?'+
                ' \n Resetting accesscode will prevent integrated app to access the current service. Only reset if the web service has been compromised,');
            if (r == true){
                if (!add_service_related_features(1, e.data.id, 'accesscode', 'update', 'service')){
                    window.setTimeout(function(){
                        refresh_page();
                    }, 2000);
                }
            }
        });

        /*** web service payment request ***/
        panel.find('.payment-request').on('click', {id:service_id, name:service_name}, function(e){
            editing_modal( content_payment_request_controller(e.data.id), e.data.name + ': Web Service Requests');
            controller_action('process_webservice', e.data.id);
        });


        /*** service draw engine ***/
        panel.find('[name="draw_range_date"]').daterangepicker();
        panel.find('[name="draw_range_date"]').on('apply.daterangepicker', {id: service_id}, function(e, picker){
            /*update the promo date range */
            add_service_related_features(picker.startDate.format('YYYY-MM-DD') + '|'+  picker.endDate.format('YYYY-MM-DD'), e.data.id, 'draw_range_date', 'update', 'draw');
        });

        panel.find('[name="draw_type"]').on('change', {id: service_id}, function(e){
            add_service_related_features($(this).find('option:selected').val(), e.data.id, 'draw_type', 'update', 'draw');
        });

        panel.find('[name="draw_num"]').on('change', {id:service_id}, function(e){
            var result = is_valid_inputs($(this).val(), 'broadcast_length');
            if (typeof result == 'object'){
                alert_modal(result.error, 3);
            } else {
                add_service_related_features($(this).val(), e.data.id, 'draw_num', 'update', 'draw');
            }
        });

        panel.find('[name="draw_engine_type"]').bootstrapSwitch();
        panel.find('[name="draw_engine_type"]').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state){
            if (state){
                add_service_related_features(1, e.data.id, 'draw_engine_type', 'update', 'draw');
            } else {
                add_service_related_features(0, e.data.id, 'draw_engine_type', 'update', 'draw');
            }
        });

        panel.find('[name="draw_win_rollout"]').bootstrapSwitch();
        panel.find('[name="draw_win_rollout"]').on('switchChange.bootstrapSwitch', {id: service_id}, function(e, state){
            if (state){
                add_service_related_features(1, e.data.id, 'draw_win_rollout', 'update', 'draw');
            } else {
                add_service_related_features(0, e.data.id, 'draw_win_rollout', 'update', 'draw');
            }
        });


        panel.find('[name="services_draws_linked"]').on('change', {id: service_id}, function(e){
            var parent = $(this).parent();
            var service_data_draw = {state: false, data:[]}; process_service_selection(parent.find('[name="services_draws_linked"]'), service_data_draw);
            var services_draw_linked = (service_data_draw.state) ? service_data_draw.data : null;

            if (services_draw_linked.length > 0){
                add_service_related_features(services_draw_linked, e.data.id, 'draw_linked', 'update', 'draw');
            } else {
                alert_modal('You cannot unlinked all services. At least one service is required', 3);
                setTimeout(function(){
                    refresh_page();
                }, 3000);

            }
        });

        panel.find('[name="notify"]').on('change', {id: service_id}, function(e){
            var result = is_valid_inputs($(this).val(), 'messages');
            if (typeof result == 'object'){
                alert_modal(result.error, 3);
            } else {
                add_service_related_features($(this).val(), e.data.id, 'draw_notify', 'update', 'draw');
            }
        });

    });
});


/* get the service type for the given service id */
function get_service_type(service_id){
    if (service_list.length > 0){
        for(var key in service_list){
            if (service_list.hasOwnProperty(key)){
                if (service_list[key].id == service_id){
                    return service_list[key].type;
                }
            }
        }
    }
    return false;
}



/******* service/draw creation ****/

/* initialise storyboard click events action */
$(function(){
    /* create a service campaign */
    $('#storyboard').children().eq(2).off('click').on('click', function(){
        $(this).find('.info-box-content > span').each(function(){
            if ($(this).text() == 'Campaign Service'){
                window.location.assign('http://'+server+'/public/manage/addservice/');
            }

            if ($(this).text() == 'Campaign Draw Service'){
                window.location.assign('http://'+server+'/public/manage/addDrawEngine/');
            }
        });

    });
});

/* populate service creation list */
$(function(){

    /* service creation */
    if (current_page == 'manage/addservice/'){
        /* basic - service type */
        get_service_type_list($('[name="type"]'), 'service_type');
        /* basic - cross sell list */
        get_service_cross_list($('#services_crossell'), 'cross_sell_service');
        /* basic - date range */
        $('.create-basic').find('.daterange_promo').daterangepicker();
        /* br - type */
        get_service_type_list($('[name="br_type"]'), 'br_type');
        /* br - cross_sell */
        $('[name="br_crossell"]').bootstrapSwitch();
        /* br - cross_sell sub */
        $('[name="br_crossell_sub"]').bootstrapSwitch();
        /* br - enable-opening */
        $('[name="br_opening"]').bootstrapSwitch();

        /* initialise service type events behaviours */
        $('[name="type"]').off('change').on('change', function(){
            /* set on content type selected */
            if ($(this).find('option:selected').val() == '1'){
                /* disable cross_sell check options */
                $('#services_crossell').children().each(function(){
                    $(this).prop('disabled', true);
                });
                /* set default content value for broadcast length and prevent changes */
                $('[name="br_length"]').val('1').prop('disabled', true);
                /* disable broadcast crossell */
                $('[name="br_crossell"]').bootstrapSwitch('disabled', true);
                /* disable broadcast crossell sub */
                $('[name="br_crossell_sub"]').bootstrapSwitch('disabled', true);
                /* hide sevice-message box */
                $('.service-messages-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* hide exclusive message intro */
                $('.exclusive-message-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* show cross sell msg box */
                $('.message-cross-sell-box').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* hide br_opening option */
                $('[name="br_opening"]').parent().parent().parent().addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* enable sdp config elts */
                $('button:contains("Product")').prop('disabled', false);
                $('[name="sp_shortcode"]').val('').prop('disabled', false);
                $('[name="sp_free_period"]').val('').prop('disabled', false);
                $('div .products').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                $('[name="keywords"]').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
            } else if ($(this).find('option:selected').val() == '2') {
                /* set on trivia type selected */
                /* enable cross_sell check options */
                $('#services_crossell').children().each(function(){
                    $(this).prop('disabled', false);
                });
                $('[name="br_length"]').prop('disabled', false);
                $('[name="br_crossell"]').bootstrapSwitch('disabled', false);
                $('[name="br_crossell_sub"]').bootstrapSwitch('disabled', false);
                $('.service-messages-box').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                $('.message-cross-sell-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                $('.exclusive-message-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                $('[name="br_opening"]').parent().parent().parent().addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* enable sdp config elts */
                $('button:contains("Product")').prop('disabled', false);
                $('[name="sp_shortcode"]').val('').prop('disabled', false);
                $('[name="sp_free_period"]').val('').prop('disabled', false);
                $('div .products').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                $('[name="keywords"]').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
            } else if ($(this).find('option:selected').val() == '3'){
                /* set on exclusive type selected */
                /* disable cross_sell check options */
                $('#services_crossell').children().each(function(){
                    $(this).prop('disabled', true);
                });
                /* set default content value for broadcast length and prevent changes */
                $('[name="br_length"]').val('1').prop('disabled', true);
                /* disable broadcast crossell */
                $('[name="br_crossell"]').bootstrapSwitch('disabled', true);
                /* disable broadcast crossell sub */
                $('[name="br_crossell_sub"]').bootstrapSwitch('disabled', true);
                /* hide service-message box */
                $('.service-messages-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* hide cross sell msg box */
                $('.message-cross-sell-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* show intro message box */
                $('.exclusive-message-box').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* show br_opening option */
                $('[name="br_opening"]').parent().parent().parent().removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* enable sdp config elts */
                $('button:contains("Product")').prop('disabled', false);
                $('[name="sp_shortcode"]').val('').prop('disabled', false);
                $('[name="sp_free_period"]').val('').prop('disabled', false);
                $('div .products').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
                $('[name="keywords"]').removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
            } else {
                /* set for app service selected */
                /* disable cross_sell check options */
                $('#services_crossell').children().each(function(){
                    $(this).prop('disabled', true);
                });
                /* set default content value for broadcast length and prevent changes */
                $('[name="br_length"]').val('0').prop('disabled', true);
                /* disable broadcast crossell */
                $('[name="br_crossell"]').bootstrapSwitch('disabled', true);
                /* disable broadcast crossell sub */
                $('[name="br_crossell_sub"]').bootstrapSwitch('disabled', true);
                /* hide service-message box */
                $('.service-messages-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* hide cross sell msg box */
                $('.message-cross-sell-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* show intro message box */
                $('.exclusive-message-box').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* hide the keywords box */
                $('[name="keywords"]').addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* hide br_opening option */
                $('[name="br_opening"]').parent().parent().parent().addClass('hidden-xs hidden-sm hidden-md hidden-lg');
                /* disable sdp config elts */
                $('button:contains("Product")').prop('disabled', true);
                $('[name="sp_shortcode"]').val('0000').prop('disabled', true);
                $('[name="sp_free_period"]').val('0').prop('disabled', true);
                $('div .products').addClass('hidden-xs hidden-sm hidden-md hidden-lg');

            }
        });
    }

    /* service draw engine creation */
    if (current_page == 'manage/addDrawEngine/'){
        /* basic - draw type */
        get_service_type_list($('[name="draw_type"]'), 'draw_type');
        /* basic - draw selection */
        $('[name="draw_engine_type"]').bootstrapSwitch();
        /* basic - draw winner rollout */
        $('[name="draw_win_rollout"]').bootstrapSwitch();
        /* basic - service draw linked list */
        get_service_cross_list($('#services_draws_linked'), 'services_draws_linked');
        /* basic - date range */
        $('.create-basic').find('[name="draw_range_date"]').daterangepicker();
    }

});

//iCheck for checkbox and radio inputs
$('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
    checkboxClass: 'icheckbox_minimal-blue',
    radioClass: 'iradio_minimal-blue'
});
//Red color scheme for iCheck
$('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
    checkboxClass: 'icheckbox_minimal-red',
    radioClass: 'iradio_minimal-red'
});
//Flat red color scheme for iCheck
$('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
    checkboxClass: 'icheckbox_flat-green',
    radioClass: 'iradio_flat-green'
});

var allowed_products = 0;
/* process service/draw creation */
$(function(){
    /* create a service campaign */
    $('button:contains("Create Service")').off('click').on('click', function(){
        process_service_creation('service');
    });

    /* create a campaign draw service */
    $('button:contains("Create Draw Engine")').off('click').on('click', function(){
        process_service_creation('draw');
    });

    /* create additional product */
    $('button:contains("Product")').off('click').on('click', function(){
        if (allowed_products < 2){
            var parent = $(this).parent().parent();
            /* get next container to fill els */
            parent.append('<div class="row products">' + $(this).parent().next().html() +'</div>');
            ++allowed_products;
        }
    });
});

function process_service_creation(type){
    var service_data = is_service_created_valid(type);
    if (service_data !== false){
        $.ajax({
            url: 'http://'+server+'/rest-api/tools/services/add/',
            type: 'POST',
            data: JSON.stringify({
                data: service_data,
                type: type
            }),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: function(response) {
                if (response.error){
                    alert_modal(response.error, 3);
                } else {
                    alert_modal(response.data, 1);
                    window.setTimeout(function(){
                        refresh_page(true);
                    }, 2000);
                }
            },
            error: function(){
                alert_modal('Enable to process your request - the service is unavailable or your computer is not connected to internet ', 3);
            }
        });
    }
}

/* validate service inputs and selection */
function is_service_created_valid(type){

    var service_name = $('[name="name"]').val();
    var service_desc = $('[name="description"]').val();

    if (type == 'service'){
        /* basic settings */
        var service_keywords = $('[name="keywords"]').val();
        var service_type_id = $('[name="type"]').find('option:selected').val();
        var service_data = {state: false, data:[]}; process_service_selection($('[name="cross_sell_service"]'), service_data);
        var service_cross_sell_list = (service_data.state) ? service_data.data : null;
        var service_promotion_date = $('[name="promotion_date"]').val();

        /* service message related */
        var msg_welcome = $('[name="msg_welcome"]').val();
        var msg_good_score = $('[name="msg_good_score"]').val();
        var msg_poor_score = $('[name="msg_poor_score"]').val();
        var msg_last_play = $('[name="msg_last_play"]').val();
        var msg_never_play = $('[name="msg_never_play"]').val();
        var msg_excel_score = $('[name="msg_excel_score"]').val();
        var msg_closing = $('[name="msg_closing"]').val();
        var msg_cross_sell = $('[name="msg_cross_sell"]').val();
        var msg_cross_sell_correct = $('[name="msg_cross_sell_correct"]').val();
        var msg_cross_sell_incorrect = $('[name="msg_cross_sell_incorrect"]').val();
        var msg_service_intro = $('[name="msg_intro"]').val();

        /* broadcast settings */
        var service_br_type = $('[name="br_type"]').find('option:selected').val();
        var service_br_length = $('[name="br_length"]').val();
        var service_br_crossell = ($('[name="br_crossell"]').is(':disabled')) ? 0 : ( ($('[name="br_crossell"]').is(':checked')) ? 1 : 0);
        var service_br_crossell_sub = ($('[name="br_crossell_sub"]').is(':disabled')) ? 0 : ( ($('[name="br_crossell_sub"]').is(':checked')) ? 1 : 0);
        var service_br_opening = $('[name="br_opening"]').is(':checked') ? 1 : (service_type_id == '3' ? 0: 1);

        /* sdp settings */
        var sp_id = $('[name="sp_id"]').val();
        var sp_password = $('[name="sp_password"]').val();
        var sp_service_id = $('[name="sp_service_id"]').val();
        var sp_shortcode = $('[name="sp_shortcode"]').val();
        var sp_free_period = $('[name="sp_free_period"]').val();

        var product_data = {state:false, data:[]}; process_product_creation($('.products'), product_data);
        var product_data_list = product_data.state ? product_data.data : null;

        if (!isNaN(service_name)){
            alert_modal('Error creating service - Invalid service name. Please review', 3);
            return false;
        }

        if (!isNaN(service_desc)){
            alert_modal('Error creating service - Invalid service description. Please review', 3);
            return false;
        }

        if (!isNaN(service_keywords) && service_type_id != '4'){
            alert_modal('Error creating service - Invalid service keywords. Please review', 3);
            return false;
        }


        /* check service messages related */
        if (service_type_id == '1'){
            /* content related */
            if (!isNaN(msg_cross_sell)){
                alert_modal('Error creating service - Invalid service cross sell message. Please review', 3);
                return false;
            }

            if (!isNaN(msg_cross_sell_correct)){
                alert_modal('Error creating service - Invalid service cross sell message. Please review', 3);
                return false;
            }

            if (!isNaN(msg_cross_sell_incorrect)){
                alert_modal('Error creating service - Invalid service cross sell message. Please review', 3);
                return false;
            }

        } else if (service_type_id == '2'){
            if (!isNaN(msg_welcome)){
                alert_modal('Error creating service - Invalid service welcome message. Please review', 3);
                return false;
            }
            if (!isNaN(msg_good_score)){
                alert_modal('Error creating service - Invalid service good score message. Please review', 3);
                return false;
            }
            if (!isNaN(msg_poor_score)){
                alert_modal('Error creating service - Invalid service poor score message. Please review', 3);
                return false;
            }
            if (!isNaN(msg_last_play)){
                alert_modal('Error creating service - Invalid service last play message. Please review', 3);
                return false;
            }
            if (!isNaN(msg_never_play)){
                alert_modal('Error creating service - Invalid service never play message. Please review', 3);
                return false;
            }
            if (!isNaN(msg_excel_score)){
                alert_modal('Error creating service - Invalid service excel score message. Please review', 3);
                return false;
            }
            if (!isNaN(msg_closing)){
                alert_modal('Error creating service - Invalid service closing message. Please review', 3);
                return false;
            }
        } else {
            if (service_br_opening == 1 && service_type_id != '4'){
                if (!isNaN(msg_service_intro)){
                    alert_modal('Error creating service - Invalid service welcome message. Please review', 3);
                    return false;
                }
            }
        }

        if (isNaN(service_br_length)){
            alert_modal('Error creating service - Invalid service broadcast length. Please review', 3);
            return false;
        }

        if (isNaN(sp_id)){
            alert_modal('Error creating service - Invalid service partner ID. Please review', 3);
            return false;
        } else {
            if (sp_id.length < 1 || sp_id.length > 20){
                alert_modal('Error creating service - Invalid service partner ID. Maximum 20 character long allowed. Please review', 3);
                return false;
            }
        }

        if (isNaN(sp_service_id)){
            alert_modal('Error creating service - Invalid service ID. Please review', 3);
            return false;
        } else {
            if (sp_service_id.length < 1 || sp_service_id.length > 20){
                alert_modal('Error creating service - Invalid service ID. Maximum 20 character long allowed. Please review', 3);
                return false;
            }
        }


        if (isNaN(sp_shortcode)){
            alert_modal('Error creating service - Invalid service shortcode. Please review', 3);
            return false;
        }

        if (isNaN(sp_free_period)){
            alert_modal('Error creating service - Invalid service free period. Please review', 3);
            return false;
        }

        /* check br_type selected option */
        if (service_type_id == '3' && service_br_type != '3'){
            alert_modal('Error creating service - Invalid service broadcast type option. Exclusive type only applied to service type exclusive. Please review', 3);
            return false;
        } else if (service_type_id != '3' && service_br_type == '3'){
            alert_modal('Error creating service - Invalid service broadcast type option. Exclusive type only applied to service type exclusive. Please review', 3);
            return false;
        }

    } else {
        /* campaign draw service */
        var draw_notify = $('[name="notify"]').val();
        var draw_type_id = $('[name="draw_type"]').find('option:selected').val();
        var draw_num =  $('[name="draw_winner_num"]').val();
        var draw_engine_type =  $('[name="draw_engine_type"]').is(':checked') ? 1 : 0;
        var draw_date_range = $('[name="draw_range_date"]').val();
        var service_data_draw = {state: false, data:[]}; process_service_selection($('[name="services_draws_linked"]'), service_data_draw);
        var services_draw_linked = (service_data_draw.state) ? service_data_draw.data : null;
        var services_draw_win_rollout = $('[name="draw_win_rollout"]').is(':checked') ? 1 : 0;

        /* validate inputs */
        if (!isNaN(service_name)){
            alert_modal('Error creating campaign draw - Invalid campaign draw name. Please review', 3);
            return false;
        }

        if (!isNaN(service_desc)){
            alert_modal('Error creating campaign draw - Invalid campaign draw description. Please review', 3);
            return false;
        }

        if (!isNaN(draw_notify)){
            alert_modal('Error creating campaign draw - Invalid campaign draw notify. Please review', 3);
            return false;
        }

        if (draw_type_id.length == 0){
            alert_modal('Error creating campaign draw - Please select the raffle draw type', 3);
            return false;
        }

        if (draw_num.length == 0 || isNaN(draw_num)){
            alert_modal('Error creating campaign draw - Invalid number\'s winner per draw. Please review', 3);
            return false;
        }

        if (services_draw_linked.length == 0){
            alert_modal('Error creating campaign draw - Please select services to associate current campaign draw', 3);
            return false;
        }
    }

    return type == 'service' ? {
        name : service_name,
        desc : service_desc,
        keywords :  ( service_keywords.match(',') != null) ? service_keywords.split(',') : service_keywords,
        type_id : service_type_id,
        cross_sell_list: service_cross_sell_list,
        promo_date : service_promotion_date,

        service_messages: service_type_id == '1' ?
        {
            'crossell': msg_cross_sell,
            'correct': msg_cross_sell_correct,
            'incorrect': msg_cross_sell_incorrect
        } : (
            service_type_id == '2' ?
            {
                'welcome': msg_welcome,
                'good_score': msg_good_score,
                'poor_score': msg_poor_score,
                'last_play': msg_last_play,
                'never_play': msg_never_play,
                'excel_score': msg_excel_score,
                'closing': msg_closing
            } :
            {
                'welcome': msg_service_intro
            }
        ),
        br_type : service_br_type,
        br_length: service_br_length,
        br_cross_sell_set: service_br_crossell,
        br_cross_sell_sub_set : service_br_crossell_sub,
        br_opening : service_br_opening,
        sp_id : sp_id,
        sp_password : sp_password,
        sp_service_id : sp_service_id,
        sp_product_data : product_data_list,
        sp_shortcode : sp_shortcode,
        sp_free_period : sp_free_period
    } : {
        name : service_name,
        desc : service_desc,
        notify: draw_notify,
        draw_type_id : draw_type_id,
        draw_num : draw_num,
        draw_engine_type : draw_engine_type,
        draw_date_range : draw_date_range,
        services_draw_linked : services_draw_linked,
        services_draw_win_rollout : services_draw_win_rollout
    }
}

/* process service option selection */
function process_service_selection(el, obj){
    el.each(function(){
        if (typeof obj == 'object'){
            if ($(this).is(":disabled")){
                obj.state = false;
            } else {
                obj.state = true;
                if ($(this).is(":checked")){
                    obj.data.push($(this).val());
                }
            }
        }
    });
}

/* process product creation */
function process_product_creation(el, obj){
    if (typeof obj == 'object'){
        obj.state = true;
        el.each(function(){
            var list = {product_id:null, bill_rate: null, bill_cycle:null};
            /* current list */
            list.product_id = $(this).find('[name="sp_product_id"]').val();
            list.bill_rate = $(this).find('[name="sp_bill_rate"]').val();
            var bl_cycle = $(this).find('[name="bl_cycle"]');
            list.bill_cycle = bl_cycle.find('option:selected').val();
            /* push list to data */
            obj.data.push(list);
        });
    }
}



/* populate related service type */
function get_service_type_list(el, type){
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/services/list/',
        type: 'POST',
        data: JSON.stringify({
            category: type,
            type: 2,
            service_id: 0
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(response) {
            if (response.error){
                alert_modal(response.error, 3);
            } else {
                var options = response.data;
                var list = '<option></option>';
                for(var i=0; i < options.length; ++i){
                    list += '<option value="'+options[i].id+'">'+options[i].type+'</option>';
                }
                el.html(list);
            }
        },
        error: function(){
            alert_modal('Populate '+ type +' list - Error connecting to the server', 3);
        }
    });
}

function get_service_cross_list(el, type){
    $.ajax({
        url: 'http://'+server+'/rest-api/tools/services/list/',
        type: 'POST',
        data: JSON.stringify({
            category: type == 'services_draws_linked' ? 'services' : type,
            type: 'all',
            service_id: 0
        }),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        success: function(response) {
            if (response.error){
                alert_modal(response.error, 3);
            } else {
                var result = response.data;
                var list = '';
                if (result != null){
                    for(var i=0; i < result.length; ++i){
                        list += '<input type="checkbox" name="'+type+'" value="'+result[i].id+'"> '+ result[i].name+ ' &nbsp;';
                    }
                }
                el.html(list);
            }
        },
        error: function(){
            alert_modal('Populate service type list - Error connecting to the server', 3);
        }
    });
}


/* loader body fn */
function loader_body_notif(msg){
    if (msg == undefined){
        gif_loader_body.hide().find('.center').text('');
    } else {
        gif_loader_body.show().find('.center').text(msg);
    }
}




