
(function($) {
	jQuery(function($) {
                $('a.modalAnchor.deletePeriods').bind('before-open.mw', function(event){
			// get checked items.
			var a = [];
                        var $checked_list = jQuery('input[name=cart\\[\\]]:checked');
                        $checked_list.each(function() { a.push(jQuery(this).val()); });
                        var period_srl = a.join(',');
			// get delete form.
                        exec_xml(
                                'nstore_digital',
                                'getNstore_digitalAdminDeletePeriods',
                                {period_srl:period_srl},
                                function(ret){
									console.log(ret);
                                        var tpl = ret.tpl.replace(/<enter>/g, '\n');
                                        $('#deleteForm').html(tpl);
                                },
                                ['error','message','tpl']
                        );

                });
	});
}) (jQuery);
