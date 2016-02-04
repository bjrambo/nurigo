<?php

	class category_menu extends WidgetHandler {

		/**
		* @brief widget
		**/

		function proc($in_args) {
			$oModuleModel = &getModel('module');

			$args->node_id = Context::get('category');
			if($args->node_id)
			{
				$output = executeQuery('nproduct.getCategoryInfo', $args);
				if(!$output->toBool()) return $output;
				$category_info = $output->data;
				$parent_nodes = explode('.',$category_info->node_route);
				Context::set('parent_nodes', $parent_nodes);
			}
			unset($args);

			$args->module_srl = $in_args->module_srls;
			$output = executeQueryArray('nproduct.getCategoryList', $args);
			if (!$output->toBool()) return $output;
			$category_list = $output->data;
			$category_tree = array();
			$category_index = array();
			if ($category_list) {
				foreach ($category_list as $no => $cate) {
					$node_route = $cate->node_route.$cate->node_id;
					$stages = explode('.',$node_route);
					$code_str = '$category_tree["' . implode('"]["', $stages) . '"] = array();';
					eval($code_str);
					// get and set mid
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($cate->module_srl);
					if ($module_info->mid) $cate->mid = $module_info->mid;
					$category_index[$cate->node_id] = $cate;
				}
			}
			Context::set('category_tree', $category_tree);
			Context::set('category_index', $category_index);


			// 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
			$tpl_path = sprintf('%sskins/%s', $this->widget_path, $in_args->skin);
			Context::set('skin', $in_args->skin);
			Context::set('widget_info', $in_args);

			// 템플릿 파일을 지정
			$tpl_file = 'categorymenu.html';

			// 템플릿 컴파일
			$oTemplate = &TemplateHandler::getInstance();
			return $oTemplate->compile($tpl_path, $tpl_file);
		}
	}
?>
