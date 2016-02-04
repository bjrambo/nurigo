<?php
    /**
     * @class  store_searchModel
     * @author NURIGO (contact@nurigo.net)
     * @brief Model class of store_search module
     **/

	require_once(_XE_PATH_.'modules/integration_search/integration_search.model.php');
	if (file_exists(_XE_PATH_.'modules/nproduct/nproduct.item.php'))
	{
		require_once(_XE_PATH_.'modules/nproduct/nproduct.item.php');
	}
	class store_searchModel extends integration_searchModel {
		/**
		 * @brief Initialization
		 **/
		function init() {
		}

		/**
		 * @brief Search documents
		 **/
		function getProducts($target, $module_srls_list, $search_target, $search_keyword, $page=1, $list_count = 20) {
			if(is_array($module_srls_list)) $module_srls_list = implode(',',$module_srls_list);

			if($target == 'exclude') {
				$module_srls_list .= ',0'; // exclude 'trash'
				if ($module_srls_list{0} == ',') $module_srls_list = substr($module_srls_list, 1);
				$args->exclude_module_srl = $module_srls_list;
			} else {
				$args->module_srl = $module_srls_list;
				$args->exclude_module_srl = '0'; // exclude 'trash'
			}

			$args->page = $page;
			$args->list_count = $list_count;
			$args->page_count = 10;
			$args->search_target = $search_target;
			$args->search_keyword = $search_keyword;
			$args->sort_index = 'list_order'; 
			$args->order_type = 'asc';
			if(!$args->module_srl) unset($args->module_srl);
			// Get a list of documents
			$oDocumentModel = &getModel('document');

			$documentlist_output = $oDocumentModel->getDocumentList($args);
			if (!$documentlist_output->toBool()) return $documentlist_output;
			unset($args);
			$document_srl_list = array();
			$documentlist_index = array();
			if ($documentlist_output->data) {
				foreach ($documentlist_output->data as $key=>$val) {
					$document_srl_list[] = $val->document_srl;
					$documentlist_index[$val->document_srl] = $key;
				}
			}
			$args->document_srl = implode(',',$document_srl_list);
			$output = executeQueryArray('nproduct.getItemListByDocumentSrl', $args);
			if (!$output->toBool()) return $output;
			unset($args);
			if ($output->data) {
				foreach ($output->data as $key=>$val) {
					if ($documentlist_output->data[$documentlist_index[$val->document_srl]]) $documentlist_output->data[$documentlist_index[$val->document_srl]]->item = new nproductItem($val);
				}
			}
			return $documentlist_output;
		}

		/**
		 * @brief Search documents
		 **/
		function getDocuments($target, $module_srls_list, $exclude_module_srls, $search_target, $search_keyword, $page=1, $list_count = 20) {
			if(is_array($module_srls_list)) $module_srls_list = implode(',',$module_srls_list);
			if(is_array($exclude_module_srls)) $exclude_module_srls = implode(',',$exclude_module_srls);

			if($target == 'exclude') {
				$module_srls_list .= ',0'; // exclude 'trash'
				if ($module_srls_list{0} == ',') $module_srls_list = substr($module_srls_list, 1);
				$args->exclude_module_srl = $module_srls_list;
			} else {
				if ($exclude_module_srls{0} == ',') $exclude_module_srls = substr($exclude_module_srls, 1);
				$args->module_srl = $module_srls_list;
				$args->exclude_module_srl = '0,'.$exclude_module_srls; // exclude 'trash'
			}

			$args->page = $page;
			$args->list_count = $list_count;
			$args->page_count = 10;
			$args->search_target = $search_target;
			$args->search_keyword = $search_keyword;
			$args->sort_index = 'list_order'; 
			$args->order_type = 'asc';
			if(!$args->module_srl) unset($args->module_srl);
			// Get a list of documents
			$oDocumentModel = &getModel('document');

			return $oDocumentModel->getDocumentList($args);
		}
	}
?>
