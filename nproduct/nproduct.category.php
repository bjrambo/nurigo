<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductCategory
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductCategory 
 */
class nproductCategory
{
	var $category_index;
	var $category_tree;
	var $parent_nodes;

	/**
	 * @brief constructor
	 */
	function nproductCategory($module_srl, $category=0)
	{
		$args->node_id = $category;
		if($args->node_id)
		{
			$output = executeQuery('nproduct.getCategoryInfo', $args);
			if(!$output->toBool()) return $output;
			$category_info = $output->data;
			$parent_nodes = explode('.',$category_info->node_route);
			$this->parent_nodes = $parent_nodes;
		}
		unset($args);

		// category tree
		$args->module_srl = $module_srl;
		$output = executeQueryArray('nproduct.getCategoryAllSubitems', $args);
		if(!$output->toBool()) return $output;

		$category_list = $output->data;
		$category_tree = array();
		$category_index = array();
		if($category_list) 
		{
			foreach($category_list as $no => $cate) 
			{
				$node_route = $cate->node_route.$cate->node_id;
				$stages = explode('.',$node_route);
				$code_str = '$category_tree["' . implode('"]["', $stages) . '"] = array();';
				eval($code_str);
				$category_index[$cate->node_id] = $cate;
			}
		}
		$this->category_tree = $category_tree;
		$this->category_index =  $category_index;
	}
	
	/**
	 * @brief check if category is active
	 */
	function isActive($key, $category)
	{
		return $category==$this->category_index[$key]->node_id || (count($this->parent_nodes) && in_array($this->category_index[$key]->node_id, $this->parent_nodes));
	}

	/**
	 * @brief get 
	 */
	function get($key)
	{
		return $this->category_index[$key];
	}
}
