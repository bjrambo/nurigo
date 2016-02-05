<?php
	/**
	 * @class  store_searchView
	 * @author NURIGO (contact@nurigo.net)
	 * @brief view class of the store_search module
	 *
	 * Search Output
	 *
	 **/

	require_once(_XE_PATH_.'modules/integration_search/integration_search.view.php');
	class store_searchView extends integration_searchView {

		/**
		 * @brief Initialization
		 **/
		function init() {
		}

		/**
		 * @brief Search Result
		 **/
		function IS() {
			$oFile = &getClass('file');
			$oModuleModel = &getModel('module');
			// Check permissions
			if(!$this->grant->access) return new Object(-1,'msg_not_permitted');

			$config = $oModuleModel->getModuleConfig('store_search');
			if(!$config->skin) $config->skin = 'store';
			Context::set('module_info', unserialize($config->skin_vars));
			$this->setTemplatePath($this->module_path."/skins/".$config->skin."/");

			$target = $config->target;
			if(!$target) $target = 'include';
				
			if (empty($config->target_module_srl))
				$module_srl_list = array();
			else
				$module_srl_list = explode(',',$config->target_module_srl);

			$product_module_srl_list = array();
			$modinstlist_output = executeQueryArray('nproduct.getModInstList');
			$tmp_arr = $modinstlist_output->data;
			if (!is_array($tmp_arr)) $tmp_arr = array();
			foreach ($tmp_arr as $key=>$val) {
				$product_module_srl_list[] = $val->module_srl;
			}

			// Set a variable for search keyword
			$is_keyword = Context::get('is_keyword');
			// Set page variables
			$page = (int)Context::get('page');
			if(!$page) $page = 1;
			// Search by search tab
			$where = Context::get('where');
			// Create integration search model object 
			if($is_keyword) {
				$oIS = &getModel('store_search');
				switch($where) {
					case 'product' :
						$search_target = Context::get('search_target');
						if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
						Context::set('search_target', $search_target);

						$output = $oIS->getProducts('include', $product_module_srl_list, $search_target, $is_keyword, $page, 10);

						Context::set('output', $output);
						$this->setTemplateFile("product", $page);
						break;
					case 'document' :
						$search_target = Context::get('search_target');
						if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
						Context::set('search_target', $search_target);

						$output = $oIS->getDocuments($target, $module_srl_list, $product_module_srl_list, $search_target, $is_keyword, $page, 10);
						Context::set('output', $output);
						$this->setTemplateFile("document", $page);
						break;
					case 'comment' :
						$output = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 10);
						Context::set('output', $output);
						$this->setTemplateFile("comment", $page);
						break;
					case 'trackback' :
						$search_target = Context::get('search_target');
						if(!in_array($search_target, array('title','url','blog_name','excerpt'))) $search_target = 'title';
						Context::set('search_target', $search_target);

						$output = $oIS->getTrackbacks($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
						Context::set('output', $output);
						$this->setTemplateFile("trackback", $page);
						break;
					case 'multimedia' :
						$output = $oIS->getImages($target, $module_srl_list, $is_keyword, $page,20);
						Context::set('output', $output);
						$this->setTemplateFile("multimedia", $page);
						break;
					case 'file' :
						$output = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 20);
						Context::set('output', $output);
						$this->setTemplateFile("file", $page);
						break;
					default :
						$output['product'] = $oIS->getProducts('include', $product_module_srl_list, 'title_content', $is_keyword, $page, 5);
						$output['document'] = $oIS->getDocuments($target, $module_srl_list, $product_module_srl_list, 'title_content', $is_keyword, $page, 5);
						$output['comment'] = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 5);
						$output['trackback'] = $oIS->getTrackbacks($target, $module_srl_list, 'title', $is_keyword, $page, 5);
						$output['multimedia'] = $oIS->getImages($target, $module_srl_list, $is_keyword, $page, 5);
						$output['file'] = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 5);
						Context::set('search_result', $output);
						$this->setTemplateFile("index", $page);
						break;
				}
			} else {
				$this->setTemplateFile("no_keywords");
			}
		}
	}
?>
