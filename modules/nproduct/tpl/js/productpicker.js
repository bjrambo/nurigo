/**
 * product picker
 */
(function($) {
	// jquery extension for product picker
	$.fn.ProductPicker = function(opts) {
		var options = $.extend({}, $.fn.ProductPicker.defaults, opts); // overwrite default values with opts
		var itemList = null; // item list ul container, it contains HTML formatted item list
		var inputField = null; // input field, it contains JSON formatted item list

		return this.each(function() {
			inputField = $(this);
			inputField.hide();
			itemList = $('<ul id="item_list" class="item_list"></ul>');
			var btn = $('<button type="button" class="x_btn">' + options.lang.appendButton + '</button>');
			btn.on('click', function() { openSearchBox() });
			inputField.parent().append(itemList);
			inputField.parent().append(btn);
			var related_items = inputField.val();
			printItems($.parseJSON(related_items));
		});

		// add a product to list
		function addProduct(item_srl, force_purchase) {
			var list = $.parseJSON(inputField.val());
			if (!$.isArray(list)) list = new Array();
			list[list.length] = {item_srl: item_srl, force_purchase: force_purchase }
			inputField.val(JSON.stringify(list));
		}

		// add a product to list and redraw them
		function addNewProduct(item_srl) {
			addProduct(item_srl, 'N');
			printItems($.parseJSON(inputField.val()));
		}

		// 삭제 클릭시 해당 상품 제거
		function deleteProduct() {
			var targetItemSrl = $(this).parent().attr('data-item_srl');
			$(this).parent().remove();
			var list = $.parseJSON(inputField.val());
			if (!$.isArray(list)) list = new Array();
			for (var i = 0; i < list.length; i++) {
				var item = list[i];
				if (item.item_srl == targetItemSrl) {
					 list.splice(i, 1);
				}
			}
			inputField.val(JSON.stringify(list));

			return false;
		}

		// 주문필수 체크박스 클릭시
		function onClickForcePurchase() {
			var item_srl = $(this).parent().attr('data-item_srl');
			var isChecked = $(this).find('input').is(":checked");
			force_purchase = 'N';
			if (isChecked) force_purchase = 'Y';
			var list = $.parseJSON(inputField.val());
			if (!$.isArray(list)) list = new Array();
			for (var i = 0; i < list.length; i++) {
				var item = list[i];
				if (item.item_srl == item_srl) item.force_purchase = force_purchase;
			}
			inputField.val(JSON.stringify(list));
		}

		// item_srl의 해당 주문필수 값을 가져옴
		function getForcePurchase(item_srl) {
			var list = $.parseJSON(inputField.val());
			if (!$.isArray(list)) list = new Array();
			for (var i = 0; i < list.length; i++) {
				var item = list[i];
				if (item.item_srl == item_srl) return item.force_purchase;
			}
			return null;
		}

		// Dialog Box를 띄운다
		function openSearchBox() {
			$container = $.xeMsgBox.$msgBox;

			$.xeMsgBox.confirmDialog({
				sTitle : options.lang.dialogTitle,
				sText : '<div id="product_tree"></div>',
				bSmall: true,
				bDanger: true,
				fnOnOK : function(){
					$container.find('.jstree-clicked').each(function(idx, el) {
						var item_srl = jQuery(el).attr('item_srl');
						addNewProduct(item_srl);
					});
				}
			});

			$container.find('#product_tree').jstree({
				'core' : {
					'data' : {
						"url" : function(node) {
							var url = "?module=nproduct&act=getNproductCategoryListJson&node_type=1";
							if(node.li_attr && node.li_attr.is_page) url += '&is_page=1';
							return url;
						},
						'data' : function(node) {
							if (node.id == '#') return  { 'node_id' : 'root' };
							return { 'node_id' : node.id };
						},
						"dataType" : "json" // needed only if you do not supply JSON headers
					}
				}
			});
		}

		// 화면에 출력
		function printItems(items) {
			item_srls = new Array();
			if(!$.isArray(items)) return;
			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				item_srls[item_srls.length] = item.item_srl;
			}
			var params = new Array();
			params['item_srls'] = item_srls;
			var response_tags = new Array('error','message','data');
			exec_xml('nproduct', 'getNproductItems', params, function(ret_obj) {
				itemList.empty();
				if (!ret_obj['data']) return;
				var data = ret_obj['data']['item'];
				if(!Array.isArray(data)) data = Array(data);
				for (var i = 0; i < data.length; i++) {
					var item = data[i];
					var force_purchase = getForcePurchase(item.item_srl);
					var li = $('<li data-item_srl="' + item.item_srl + '"></li>');
					$('<span class="title" data-item_srl="' + item.item_srl + '">' + item.item_name + ' </span>').appendTo(li);
					var chkForcePurchase = $('<span class="check"><input type="checkbox" data-item_srl="' + item.item_srl + '" value="Y" ' + (force_purchase == 'Y' ? 'checked=checked' : '') + '/><span>'); 
					chkForcePurchase.bind('click', onClickForcePurchase);
					chkForcePurchase.appendTo(li);
					var btnDelete = $('<span class="delete"><a href="#" onclick="return false;">' + options.lang.deleteButton + '</a></span>');
					btnDelete.bind('click', deleteProduct);
					btnDelete.appendTo(li);
					li.appendTo(itemList);
				}
			}, response_tags);
		}
	};

	// default options
	$.fn.ProductPicker.defaults = {
		lang : {
			dialogTitle: "Product Picker"
			, deleteButton: "Delete"
			, appendButton: "Append"
		}
	};

	jQuery(function($) {
		jQuery(window).load(function(){
			// redefine showMsgBox from admin.js, eliminate the menu style.css
			// showMsgBox내에서 style.css을 추가 로딩하도록 되어 있어서 CSS 충돌 일어남, 그래서 재정의
			$.xeMsgBox.showMsgBox = function(htOptions){
				// sTitle, sText, fnOnOK, fnOnCancel, bSmall, bAlert, fnOnShow, fnOnHide, bDanger
				htOptions = $.xeMsgBox.htOptions = htOptions || {};
				var sTitle = htOptions.sTitle || "";
				var sText = htOptions.sText || "";
				var bDanger = htOptions.bDanger || false;
				$msgBox.find("._title") .html(sTitle);
				$msgBox.find("._text").html(sText);
				if(sText === ""){
					$msgBox.addClass('_nobody');
				}else{
					$msgBox.removeClass('_nobody');
				}
				var $confirmBtn = $msgBox.find('._ok');
				if(bDanger){
					$confirmBtn.removeClass('x_btn-inverse');
					$confirmBtn.addClass('x_btn-danger');
				}else{
					$confirmBtn.removeClass('x_btn-danger');
					$confirmBtn.addClass('x_btn-inverse');
				}
				if(htOptions.bSmall){
					$msgBox.addClass("_small");
				}else{
					$msgBox.removeClass("_small");
				}
				if(htOptions.bAlert){
					$msgBox.addClass("_type_alert");
				}else{
					$msgBox.removeClass("_type_alert");
				}
				$msgBox.show();
			};
		});
	});
})(jQuery);
