(function($) {
	jQuery(function($) {
            $('a.modalAnchor.deleteOrders').bind('before-open.mw', function(event){
			// get checked items.
				var a = [];
                        var $checked_list = jQuery('input[name=cart\\[\\]]:checked');
                        $checked_list.each(function() { a.push(jQuery(this).val()); });
                        var order_srl = a.join(',');

			// get delete form.
                        exec_xml(
                                'nstore',
                                'getNstoreAdminDeleteOrders',
                                {order_srl:order_srl},
                                function(ret){
                                        var tpl = ret.tpl.replace(/<enter>/g, '\n');
                                        $('#deleteForm').html(tpl);
                                },
                                ['error','message','tpl']
                        );

            });
	});
}) (jQuery);
