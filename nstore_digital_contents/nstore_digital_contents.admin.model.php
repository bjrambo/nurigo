<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital_contentsAdminModel
 * @author hosy(hosy@nurigo.net)
 * @brief  nstore_digital_contentsAdminModel
 */ 
class nstore_digital_contentsAdminModel extends nstore_digital_contents
{
	function getNstore_digital_contentsAdminContentInfo()
	{
        $args->file_srl = Context::get('file_srl');
        $output = executeQuery('nstore_digital_contents.getContent', $args);
        if(!$output->toBool()) return $output;
        $this->add('data', $output->data);
	}
}
/* End of file nstore_digital_contents.admin.model.php */
/* Location: ./modules/nstore_digital_contents/nstore_digital_contents.admin.model.php */
