<?php
/**
 * cympusadmin class
 * Base class of cympusadmin module
 *
 * @author NURIGO (contact@nurigo.net)
 * @package /modules/cympusadmin
 * @version 0.1
 */
require_once('cympusadmin.config.php');
require_once(_CYMPUSADMIN_FUNCTION_);

class cympusadmin extends ModuleObject
{
	function init()
	{
		// forbit access if the user is not an administrator
		$oMemberModel = &getModel('member');
		if(!$this->grant->manager && !$this->grant->is_admin) return $this->stop("msg_is_not_administrator");

		// change into administration layout
		//$this->setTemplatePath('./modules/cympusadmin/tpl');
		$this->setLayoutPath('./modules/cympusadmin/tpl');
		$this->setLayoutFile(_CYMPUSADMIN_LAYOUT_);

		Context::loadLang(_XE_PATH_ . 'modules/cympusadmin/lang/');

		if($this->grant->is_admin)
		{
			// parse admin menu
			$act = Context::get('act');
			$oXmlParser = new XmlParser();
			$xml_obj = $oXmlParser->loadXmlFile('./modules/cympusadmin/conf/' . _CYMPUSADMIN_MENU_);
			$admin_menu = array();
			$admin_menu = cympusadmin::getMenu($xml_obj->menu->item);
			Context::set('cympusadmin_menu', $admin_menu);
		}
		else
		{
			$output = ModuleHandler::triggerCall('cympusadmin.getManagerMenu', 'before', $manager_menu);
			if(!$output->toBool()) return $output;

			Context::set('cympusadmin_menu', $manager_menu);
			
		}

		$news = getNewsFromAgency();
		Context::set('news', $news);
		Context::set('admin_bar', 'false');

		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoXml('cympusadmin');
		Context::set('cympus_modinfo', $module_info);

		return new Object();
	}

	/**
	 * Install cympusadmin module
	 * @return Object
	 */
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

		if(!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympusadmin', 'model', 'triggerGetManagerMenu', 'before')) return true;

		return false;
	}

	/**
	 * Update module
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if (!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympusadmin', 'model', 'triggerGetManagerMenu', 'before')) {
		    $oModuleController->insertTrigger('cympusadmin.getManagerMenu', 'cympusadmin', 'model', 'triggerGetManagerMenu', 'before');
		}

		return new Object();
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache()
	{
	}


	function getMenu(&$in_xml_obj, $depth=0,&$parent_item=null) 
	{
		if(!is_array($in_xml_obj)) 
		{
			$xml_obj = array($in_xml_obj);
		} else {
			$xml_obj = $in_xml_obj;
		}
		$act = Context::get('act');

		$menus = array();
		$idx = 0;
		foreach ($xml_obj as $it) {
			$obj = new StdClass();
			$obj->id = $idx++;
			if($parent_item) 
			{
				$obj->parent_id = $parent_item->id;
			}
			$obj->title = $it->title->body;
			$obj->icon = $it->icon->body;
			$obj->action = array();
			if(is_array($it->action))
			{
				foreach ($it->action as $action)
				{
					$obj->action[] = $action->body;
				}
			}
			else
			{
				$obj->action[] = $it->action->body;
			}
			$obj->action_prefix = $it->action_prefix->body;
			$obj->description = $it->description->body;
			$obj->selected = false;

			if(in_array($act, $obj->action) || ($obj->action_prefix && $obj->action_prefix == substr($act, 0, strlen($obj->action_prefix))) ) 
			{
				$obj->selected = true;
				if($parent_item) 
				{
					$parent_item->selected = true;
				}
			}
			if($it->item && ($it->attrs->modinst != 'true'||Context::get('module_srl'))) 
			{
				$obj->submenu = cympusadmin::getMenu($it->item, $depth+1, $obj);
				if($obj->selected && $parent_item) 
				{
					$parent_item->selected= true;
				}
				if($obj->selected) 
				{
					Context::set('cympusadmin_selected_menu', $obj);
				}
			}
			if($it->attrs->cond)
			{
				$code = sprintf('$rtn = %s;', $it->attrs->cond);
				eval($code);
				if(!$rtn)
				{
					continue;
				}
			}
			$menus[$obj->id] = $obj;
			unset($obj);
		}
		return $menus;
	}
}
