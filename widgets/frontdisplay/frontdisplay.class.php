<?php
	/**
	* @author NURIGO (contact@nurigo.net)
	**/
	class frontdisplay extends WidgetHandler {
		function proc($widget_info) {
			$oModuleModel = &getModel('module');
			$widget_info->option_view_arr = explode(',',$widget_info->option_view);

			// default
			if (!$widget_info->thumbnail_type) $widget_info->thumbnail_type = 'crop';
			if (!$widget_info->thumbnail_width) $widget_info->thumbnail_width = 150;
			if (!$widget_info->thumbnail_height) $widget_info->thumbnail_height = 0;
			if (!$widget_info->num_columns) $widget_info->num_columns = 5;
			if (!$widget_info->num_rows) $widget_info->num_rows = 2;

			// change into an array
			$module_srls = explode(',',$widget_info->module_srls);

			$display_categories = array();
			foreach($module_srls as $module_srl)
			{
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
				if(!$module_info || $module_info->module != 'nproduct') continue;
				$oStoreModel = &getModel($module_info->module);
				if(!$oStoreModel) continue;
				if ($widget_info->category_type=='M') {
					if(method_exists($oStoreModel,'getFrontDisplayItems')) $data = $oStoreModel->getFrontDisplayItems($module_info->module_srl, $widget_info->category_srl, $widget_info->num_columns*$widget_info->num_rows);
				} else {
					if(method_exists($oStoreModel,'getDisplayItems')) $data = $oStoreModel->getDisplayItems($module_info->module_srl, $widget_info->category_srl, $widget_info->num_columns*$widget_info->num_rows);
				}
				$display_categories = array_merge($display_categories, $data);
			}
			Context::set('display_categories', $display_categories);

			// 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
			$tpl_path = sprintf('%sskins/%s', $this->widget_path, $widget_info->skin);
			Context::set('colorset', $widget_info->colorset);
			Context::set('widget_info', $widget_info);

			// 템플릿 파일을 지정
			$tpl_file = 'list';

			// 템플릿 컴파일
			$oTemplate = &TemplateHandler::getInstance();
			$output = $oTemplate->compile($tpl_path, $tpl_file);
			return $output;
		}
	}
?>
