jQuery(function($) {
        $('a.modalAnchor.deleteInstance').bind('before-open.mw', function(event){
                var plugin_srl = $(this).attr('data-plugin-srl');
                if (!plugin_srl) return;

                exec_xml(
                        'epay',
                        'getEpayAdminDeletePlugin',
                        {plugin_srl:plugin_srl},
                        function(ret){
                                var tpl = ret.tpl.replace(/<enter>/g, '\n');
                                $('#deleteForm').html(tpl);
                        },
                        ['error','message','tpl']
                );
        });
});
