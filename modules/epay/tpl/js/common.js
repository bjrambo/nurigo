function copy_form(src_frm_name, dest_frm_name)
{
	$_f1 = jQuery('#'+dest_frm_name);
	jQuery(':input[isacopy]',$_f1).remove();

	// copy text, hidden, passwords from src_frm to dest_frm
	jQuery('input:text,input:hidden,input:password,input:checked,select option:selected,textarea','#'+src_frm_name).each(function() {
		var tag = this.nodeName.toLowerCase();
		var name = jQuery(this).attr('name');
		if (tag=='option') var name = jQuery(this).parent().attr('name');
		if (name == 'undefined') return;
		var size = jQuery('#'+src_frm_name).find('[name="'+name+'"]').size();
		if ($_f1.find('input[name="'+name+'"]').size()!=size) {
			var val = jQuery(this).val();
			jQuery('<input type="hidden" name="'+name+'" value="'+val+'" isacopy="y" />').appendTo($_f1);
		}
	});
}
