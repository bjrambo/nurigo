/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf8: 
 **/

/* 관리자 아이디 등록/ 제거 */
function doInsertAdmin(id_list) {
    var list_obj = document.getElementById(id_list);
    var sel_obj = document.getElementById('sel_' + id_list);
    var input_obj = document.getElementById(id_list + '_input');
    var admin_id = input_obj.value;
    if(!admin_id) return;

    var opt = new Option(admin_id,admin_id,true,true);
    sel_obj.options[sel_obj.options.length] = opt;

    input_obj.value = '';
    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;

    var members = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        members[members.length] = sel_obj.options[i].value;
        
    }
    list_obj.value = members.join(',');

    input_obj.focus();
}

function doDeleteAdmin(id_list) {
    var list_obj = document.getElementById(id_list);
    var sel_obj = document.getElementById('sel_' + id_list);
    sel_obj.remove(sel_obj.selectedIndex);

    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;

    var members = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        members[members.length] = sel_obj.options[i].value;
        
    }
    list_obj.value = members.join(',');
}

// join group srls
function joinGroupSrls() {
    var groupSrls = new Array();
    jQuery('input:checkbox[name^=group_srl_list_]:checked').each(function() {
        groupSrls[groupSrls.length] = jQuery(this).val();
    });
    jQuery('input[name=group_srl_list]').val(groupSrls.join(','));
}

// join change group srls
function joinChangeGroupSrls() {
    var groupSrls = new Array();
    jQuery('input:checkbox[name^=change_group_srl_list_]:checked').each(function() {
        groupSrls[groupSrls.length] = jQuery(this).val();
    });
    jQuery('input[name=change_group_srl_list]').val(groupSrls.join(','));
}


// 위젯의 대상 모듈 입력기 (단일 선택)
function insertSelectedModule(id, module_srl, mid, browser_title) {
    var obj= xGetElementById('_'+id);
    var sObj = xGetElementById(id);
    sObj.value = module_srl;
    obj.value = browser_title+' ('+mid+')';

}

// 위젯의 대상 모듈 입력기 (다중 선택)
function insertSelectedModules(id, module_srl, mid, browser_title) {
    var sel_obj = xGetElementById('_'+id);
    for(var i=0;i<sel_obj.options.length;i++) if(sel_obj.options[i].value==module_srl) return;
    var opt = new Option(browser_title+' ('+mid+')', module_srl, false, false);
    sel_obj.options[sel_obj.options.length] = opt;
    if(sel_obj.options.length>8) sel_obj.size = sel_obj.options.length;

    syncMid(id);
}

function midMoveUp(id) {
    var sel_obj = xGetElementById('_'+id);
    if(sel_obj.selectedIndex<0) return;
    var idx = sel_obj.selectedIndex;

    if(idx < 1) return;

    var s_obj = sel_obj.options[idx];
    var t_obj = sel_obj.options[idx-1];
    var value = s_obj.value;
    var text = s_obj.text;
    s_obj.value = t_obj.value;
    s_obj.text = t_obj.text;
    t_obj.value = value;
    t_obj.text = text;
    sel_obj.selectedIndex = idx-1;

    syncMid(id);
}

function midMoveDown(id) {
    var sel_obj = xGetElementById('_'+id);
    if(sel_obj.selectedIndex<0) return;
    var idx = sel_obj.selectedIndex;

    if(idx == sel_obj.options.length-1) return;

    var s_obj = sel_obj.options[idx];
    var t_obj = sel_obj.options[idx+1];
    var value = s_obj.value;
    var text = s_obj.text;
    s_obj.value = t_obj.value;
    s_obj.text = t_obj.text;
    t_obj.value = value;
    t_obj.text = text;
    sel_obj.selectedIndex = idx+1;

    syncMid(id);
}

function midRemove(id) {
    var sel_obj = xGetElementById('_'+id);
    if(sel_obj.selectedIndex<0) return;
    var idx = sel_obj.selectedIndex;
    sel_obj.remove(idx);
    idx = idx-1;
    if(idx < 0) idx = 0;
    if(sel_obj.options.length) sel_obj.selectedIndex = idx;

    syncMid(id);
}

function syncMid(id) {
    var sel_obj = xGetElementById('_'+id);
    var valueArray = new Array();
    for(var i=0;i<sel_obj.options.length;i++) valueArray[valueArray.length] = sel_obj.options[i].value;
    xGetElementById(id).value = valueArray.join(',');
}

function getModuleSrlList(id) {
    var obj = xGetElementById(id);
    if(!obj.value) return;
    var value = obj.value;
    var params = new Array();
    params["module_srls"] = obj.value;
    params["id"] = id;

    var response_tags = new Array("error","message","module_list","id");
    exec_xml("module", "getModuleAdminModuleList", params, completeGetModuleSrlList, response_tags, params);
}

function completeGetModuleSrlList(ret_obj, response_tags) {
    var id = ret_obj['id'];
    var sel_obj = xGetElementById('_'+id);
    if(!sel_obj) return;

    var module_list = ret_obj['module_list'];
    if(!module_list) return;
    var item = module_list['item'];
    if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);

    for(var i=0;i<item.length;i++) {
        var module_srl = item[i].module_srl;
        var mid = item[i].mid;
        var browser_title = item[i].browser_title;
        var opt = new Option(browser_title+' ('+mid+')', module_srl);
        sel_obj.options.add(opt);
    }
}

function getModuleSrl(id) {
    var obj = xGetElementById(id);
    if(!obj.value) return;
    var value = obj.value;
    var params = new Array();
    params["module_srls"] = obj.value;
    params["id"] = id;

    var response_tags = new Array("error","message","module_list","id");
    exec_xml("module", "getModuleAdminModuleList", params, completeGetModuleSrl, response_tags, params);
}

function completeGetModuleSrl(ret_obj, response_tags) {
    var id = ret_obj['id'];
    var obj = xGetElementById('_'+id);
    var sObj = xGetElementById(id);
    if(!sObj || !obj) return;

    var module_list = ret_obj['module_list'];
    if(!module_list) return;
    var item = module_list['item'];
    if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);

    sObj.value = item[0].module_srl;
    obj.value = item[0].browser_title+' ('+item[0].mid+')';
}

var windowLoadEventLoader = new Array();
function doAddWindowLoadEventLoader(func) {
    windowLoadEventLoader.push(func);
}
function excuteWindowLoadEvent() {
    for(var i=0;i<windowLoadEventLoader.length;i++) {
        windowLoadEventLoader[i]();
    }
}
xAddEventListener(window,'load',excuteWindowLoadEvent);


function selectWidget(val){
    var url =current_url.setQuery('selected_widget', val);
    document.location.href = url;
}

function widgetstyle_extra_image_upload(f){
    f.act.value='procWidgetStyleExtraImageUpload';
    f.submit();
}




function MultiOrderSet(id){
    var selectedObj = jQuery("[name='selected_"+id+"']").get(0);

    var value = [];
    for(i=0;i<selectedObj.options.length;i++){
        value.push(selectedObj.options[i].value);
    }
    jQuery("[name='"+id+"']").val(value.join(','));
}


function MultiOrderAdd(id){
    var showObj = jQuery("[name='show_"+id+"']").get(0);
    var selectedObj = jQuery("[name='selected_"+id+"']").get(0);
    var defaultObj = jQuery("[name='default_"+id+"']").val().split(',');

    if(showObj.selectedIndex<0) return;
    var idx = showObj.selectedIndex;
    var svalue = showObj.options[idx].value;


    for(i=0;i<selectedObj.options.length;i++){
        if(selectedObj.options[i].value == svalue) return;
    }
    selectedObj.options.add(new Option(svalue, svalue, false, false));

    MultiOrderSet(id);
}


function MultiOrderDelete(id){
    var showObj = jQuery("[name='show_"+id+"']").get(0);
    var selectedObj = jQuery("[name='selected_"+id+"']").get(0);
    var defaultObj = jQuery("[name='default_"+id+"']").val().split(',');

    var idx = selectedObj.selectedIndex;
    if(idx<0) return;
    for(i=0;i<defaultObj.length;i++){
        if(jQuery.inArray(selectedObj.options[idx].value, defaultObj) > -1) return;
    }

    selectedObj.remove(idx);
    idx = idx-1;
    if(idx < 0) idx = 0;
    if(selectedObj.options.length) selectedObj.selectedIndex = idx;

    MultiOrderSet(id);
}

function MultiOrderUp(id){
    var selectedObj = jQuery("[name='selected_"+id+"']").get(0);
    if(selectedObj.selectedIndex<0) return;
    var idx = selectedObj.selectedIndex;

    if(idx < 1) return;

    var s_obj = selectedObj.options[idx];
    var t_obj = selectedObj.options[idx-1];
    var value = s_obj.value;
    var text = s_obj.text;
    s_obj.value = t_obj.value;
    s_obj.text = t_obj.text;
    t_obj.value = value;
    t_obj.text = text;
    selectedObj.selectedIndex = idx-1;

    MultiOrderSet(id);
}


function MultiOrderDown(id){
    var selectedObj = jQuery("[name='selected_"+id+"']").get(0);
    if(selectedObj.selectedIndex<0) return;
    var idx = selectedObj.selectedIndex;

    if(idx == selectedObj.options.length-1) return;

    var s_obj = selectedObj.options[idx];
    var t_obj = selectedObj.options[idx+1];
    var value = s_obj.value;
    var text = s_obj.text;
    s_obj.value = t_obj.value;
    s_obj.text = t_obj.text;
    t_obj.value = value;
    t_obj.text = text;
    selectedObj.selectedIndex = idx+1;

    MultiOrderSet(id);
}

function initMultiOrder(id){
    var selectedObj = jQuery("[name='selected_"+id+"']").get(0);
    var init_value = jQuery("[name='init_"+id+"']").val();
    var save_value = jQuery("[name='"+id+"']").val();
    if(save_value){
        var arr_save_value = save_value.split(',');
        for(i=0;i<arr_save_value.length;i++){
            if(arr_save_value[i].length>0){
                var opt = new Option(arr_save_value[i], arr_save_value[i]);
                selectedObj.options.add(opt);
            }
        }
    }else{
        var arr_init_value = init_value.split(',');
        for(i=0;i<arr_init_value.length;i++){
            if(arr_init_value[i].length>0){
                var opt = new Option(arr_init_value[i], arr_init_value[i]);
                selectedObj.options.add(opt);
            }
        }

    }
    MultiOrderSet(id);
}

