function p_show_waiting_message() {
    var waiting_obj = jQuery('#waitingforserverresponse');
    if(waiting_obj.length) {
        var d = jQuery(document);
        waiting_obj.html('잠시만 기다려주세요.').css({
            'top'  : (d.scrollTop()+20)+'px',
            'left' : (d.scrollLeft()+20)+'px',
            'visibility' : 'visible'
        });
    }
}

function p_hide_waiting_message() {
    var waiting_obj = jQuery('#waitingforserverresponse');
    waiting_obj.css('visibility','hidden');
}

function pop_update_category(data) {
	var node_id = data.node_id;
	var category_name = data.category_name;
	var regdate = data.regdate;
	var node_route_text = data.node_route_text;
	jQuery('#fo_update').show();
	jQuery('#fo_insert').hide();
	$f = jQuery('#fo_update');
	jQuery('input[name=category_name]',$f).val(category_name);
	jQuery('.route',$f).text(node_route_text);
}

function pb_load_list(node) {
    if (typeof(node)=='undefined') {
        var selected_folders = jQuery(init_tree.tree_id).jstree('get_selected');
        if (selected_folders.length > 0) {
            node = jQuery(selected_folders[0]);
        }
    }

    var req_node_id = '';
    if (typeof(node)=='string') {
        req_node_id = node;
        node = jQuery('#'+req_node_id);
    } else {
        req_node_id = node.attr('node_id');
    }

	selected_category_srl = req_node_id;
	if (selected_category_srl == 'f.') selected_category_srl = 0;

	jQuery('#tabs0-itemlist').empty();

	var params = new Array();
	params['category_srl'] = selected_category_srl;
	var response_tags = ['error','message','data'];
	exec_xml('nstore', 'getNstoreDisplayItems', params, function(ret_obj) {
		if (ret_obj['data']) {
			var data = ret_obj['data']['item'];
			if (!jQuery.isArray(data)) {
				data = new Array(data);
			}
			if (data) {
				for (var i = 0; i < data.length; i++) {
					jQuery('#tabs0-itemlist').append('<li id="record_'+data[i].item_srl+'"><span class="iconMoveTo"></span><span>'+data[i].item_name+'</span><a href="#" class="delete" onclick="delete_display_item('+selected_category_srl+','+data[i].item_srl+'); return false;">삭제</a></li>');
				}
			}
		}
	}, response_tags);
}

function init_tree(module_srl, tree_id, img_base) {
	init_tree.module_srl = module_srl;
	init_tree.tree_id = tree_id;
    init_tree.img_base = img_base;
    jQuery(tree_id).jstree({
        // the list of plugins to include
        "plugins" : [ "themes", "json_data", "ui", "crrm", "cookies", "dnd", "search", "types", "hotkeys" ],
        // Plugin configuration

        // I usually configure the plugin that handles the data first - in this case JSON as it is most common
        "json_data" : { 
            // I chose an ajax enabled tree - again - as this is most common, and maybe a bit more complex
            // All the options are the same as jQuery's except for `data` which CAN (not should) be a function
            "ajax" : {
                contentType: "application/json; charset=utf-8",
                // the URL to fetch the data
                "url" : "./",
                // this function is executed in the instance's scope (this refers to the tree instance)
                // the parameter is the node being loaded (may be -1, 0, or undefined when loading the root nodes)
                "data" : function (n) { 
                    p_show_waiting_message();
                    if (typeof(init_tree.initial)=='undefined') {
                        init_tree.initial = 1;
                        node_id = 'root';
                    }
                    if (typeof(n.attr) != 'undefined') {
                        node_id = n.attr('node_id');
                    }
                    // the result is fed to the AJAX request `data` option
                    return { 
                        module : "nstore"
                        , act : "getNstoreCategoryList"
                        , node_id : node_id
                        , node_type : "1"
                        , module_srl : init_tree.module_srl
                    }; 
                },
                "success" : function(d) { 
                    p_hide_waiting_message();
                    if (d.error == -1) {
                        jQuery(tree_id).html(d.message);
                        return;
                    }
                    if (typeof(d.data)=='undefined' || d.data.length == 0) {
                        return d.data;
                    }
                    for(i = 0; i < d.data.length; i++) {
                        if (d.data[i].attr.shared > 0) {
                            d.data[i].data = "[" + d.data[i].attr.shared + "]" + d.data[i].data;
                        }
                    }
                    return d.data; 
                }
            }
        },
        // we dont use this because cannot support json_data.
        "search" : {
            "ajax" : {
                contentType: "application/json; charset=utf-8",
                "url" : "./",
                "data" : function (str) {
                    return { 
                        module : "mobilemessage"
                        , act : "getNstorePurplebookSearchFolder"
                        , search : str
                    }; 
                },
                "success" : function(d) { 
                    for(i = 0; i < d.data.length; i++) {
                        d.data[i] = '#node_'+d.data[i];
                    }
                    return d.data;
                }
            }
        },
        // Using types - most of the time this is an overkill
        // Still meny people use them - here is how
        "types" : {
            // I set both options to -2, as I do not need depth and children count checking
            // Those two checks may slow jstree a lot, so use only when needed
            "max_depth" : 4,
            "max_children" : -2,
            // I want only `drive` nodes to be root nodes 
            // This will prevent moving or creating any other type as a root node
            "valid_children" : [ "root","shared","trashcan" ],
            "types" : {
                "default" : {
                    // I want this type to have no children (so only leaf nodes)
                    // In my case - those are files
                    "valid_children" : "none",
                    // If we specify an icon for the default type it WILL OVERRIDE the theme icons
                    "icon" : {
                        "image" : img_base + "file.png"
                    }
                },
                "folder" : {
                    // can have files and other folders inside of it, but NOT `drive` nodes
                    "valid_children" : [ "folder","shared_folder" ],
                    "icon" : {
                        "image" : img_base + "folder.png"
                    }
                },
                "shared_folder" : {
                    "valid_children" : "none",
                    "icon" : {
                        "image" : img_base + "shared_folder.png"
                    }
                },
                "root" : {
                    "valid_children" : [ "folder","shared_folder" ],
                    "icon" : {
                        "image" : img_base + "root.png"
                    },
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                },
                "trashcan" : {
                    "valid_children" : [ "folder" ],
                    "icon" : {
                        "image" : img_base + "trashcan.png"
                    },
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                },
                "shared" : {
                    "valid_children" : [ "folder" ],
                    "icon" : {
                        "image" : img_base + "folder_public.png"
                    },
                    "start_drag" : false,
                    "move_node" : false,
                    "delete_node" : false,
                    "remove" : false
                }
            }
        },
        // For UI & core - the nodes to initially select and open will be overwritten by the cookie plugin

        // the UI plugin - it handles selecting/deselecting/hovering nodes
        "ui" : {
            // this makes the node with ID node_4 selected onload
            "initially_select" : [ "f." ]
        },
        // the core plugin - not many options here
        "core" : { 
            "html_titles" : "html"
            ,"strings" : { loading : "로딩중 ...", new_node : "새폴더" }
			,"initially_open" : [ "f." ] 
        },
        "dnd" : {
            "drag_check" : function() {
                return {
                    after : false
                    , before : false
                    , inside : true
                };
            }
            , "drag_finish" : function(data) {
                $o = jQuery(data.o);
                $r = jQuery(data.r);
                if (!$o.hasClass('jstree-draggable')) {
                    $o = $o.parent();
                }
                purplebook_move_node($o.attr('node_id'), $r.attr('node_id'));
            }
            , "drop_check" : function(data) {
                return true;
            }
            , "drop_finish" : function() {
                return true;
            }
        },
        "contextmenu" : {
            "items" : {
				"create" : {
					"separator_before"	: false,
					"separator_after"	: true,
					"label"				: "만들기",
					"action"			: function (obj) { this.create(obj); }
				},
				"remove" : {
					"separator_before"	: false,
					"icon"				: false,
					"separator_after"	: false,
					"label"				: "삭제",
					"action"			: function (obj) { this.remove(obj); }
				},
                "cut" : {
                    "separator_before"	: true,
                    "separator_after"	: false,
                    "label"				: "잘라내기",
                    "action"			: function (obj) { this.cut(obj); }
                },
                "paste" : {
                    "separator_before"	: false,
                    "icon"				: false,
                    "separator_after"	: false,
                    "label"				: "붙여넣기",
                    "action"			: function (obj) { this.paste(obj); }
                },
                "properties" : {
                    "separator_before"	: false,
                    "icon"				: false,
                    "separator_after"	: false,
                    "label"				: "속성",
                    "action"			: function (obj) { properties(obj); }
                }
            }
        }
    })
/*
    .bind("create.jstree", function (e, data) {
        parent_node = data.rslt.parent.attr("node_id");

        var module_srl = jQuery(this).attr('module_srl');
        $obj = jQuery('#layer_create','#smsPurplebook');
        $obj.attr('parent_node', parent_node);
        $obj.attr('node_name', data.rslt.name);
        jstree_data = data;
        show_and_hide($obj);
    })
*/
    .bind("remove.jstree", function (e, data) {

        data.rslt.obj.each(function () {
            jQuery.ajax({
                type: 'POST',
                dataType: "json",
                contentType: "application/json; charset=utf-8",
                async : false,
                url: "./",
                data : { 
                    module : "nstore"
                    , act : "procCourseMoveNode"
                    , node_id : this.id.replace("node_","")
                    , parent_id : 't.'
                }, 
                success : function (r) {
                    if (r.error == -1) {
                        alert(r.message);
                    } else {
                        // do nothing
                    }
                }
            });
        });
    })
    .bind("rename.jstree", function (e, data) {
        var node_id = data.rslt.obj.attr("node_id");
        var node_name = data.rslt.new_name;

        jQuery.ajax({
            type: "POST",
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            url : "./", 
            data : { 
                module : "mobilemessage"
                , act : "procNstorePurplebookRenameNode"
                , node_id : node_id
                , node_name : node_name
            }, 
            success : function(r) {
                if(r.error == -1) {
                    jQuery.jstree.rollback(data.rlbk);
                    alert(r.message);
                }
            }
        });
    })
	.bind("move_node.jstree", function (e, data) {
		if (!confirm('정말 이동하시겠습니까?')) return;
		data.rslt.o.each(function (i) {
			var node_id = jQuery(this).attr("node_id");
			var parent_id = data.rslt.np.attr("node_id");
			var target_id = data.rslt.or.attr("node_id");
			var position = 'next';
			if (!target_id) {
				target_id = data.rslt.r.attr("node_id");
				position = 'prev';
			}
			//console.log(data.rslt);

			jQuery.ajax({
				type: 'POST',
				dataType: "json",
				contentType: "application/json; charset=utf-8",
				async : false,
				url: "./",
				data : {
					module : "nstore"
					, act : "procNstoreMoveCategory"
					, node_id : node_id
					, parent_id : parent_id
					, target_id : target_id
					, position : position
				},
				success : function (r) {
					if(r.error == -1) {
						jQuery.jstree.rollback(data.rlbk);
					} else {
						//console.log(data.rslt);
						jQuery(data.rslt.oc).attr("id", "node_" + r.id);
						if(data.rslt.cy && jQuery(data.rslt.oc).children("UL").length) {
							data.inst.refresh(data.inst._get_parent(data.rslt.oc));
						}
					}
				}
			});
		});
	})
    .bind("select_node.jstree", function(e, data) {
        var node = data.rslt.obj;
        pb_load_list(node);
    })
    ;
}

function completeInsertCategory(ret_obj) {
	alert(ret_obj['message']);
	var node = document.getElementById(ret_obj['parent_node']);

	// open node and refresh.
	jQuery(init_tree.tree_id).jstree('open_node', node, function() {
		jQuery(init_tree.tree_id).jstree('refresh',node);
		// focus on input field.
		jQuery('input[name=category_name]', '#fo_insert').select();
	});

}
function completeUpdateCategory(ret_obj) {
	alert(ret_obj['message']);
	var node = document.getElementById(ret_obj['node_id']);
	var p = jQuery.jstree._reference(node)._get_parent(node);
    jQuery(init_tree.tree_id).jstree('refresh',p);
}
