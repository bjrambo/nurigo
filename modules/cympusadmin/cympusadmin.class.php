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
	function init($module = null)
	{
		// change into administration layout
		$config = getModel('cympusadmin')->getConfig();
		$args = new stdClass();
		$args->module = 'cympusadmin';
		$module_list = getModel('module')->getModuleSrlList($args);
		if(!empty($module_list))
		{
			foreach($module_list as $module_info)
			{
				$cympus_module_info = $module_info;
			}
		}
		$module_path = './modules/cympusadmin/';
		$template_path = sprintf("%sskins/%s/",$module_path, $cympus_module_info->skin);
		if(!is_dir($template_path) || !$cympus_module_info->skin)
		{
			$cympus_module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$module_path, $cympus_module_info->skin);
		}

		if($module)
		{
			$module->setLayoutPath($template_path);
			$module->setLayoutFile(_CYMPUSADMIN_LAYOUT_);
		}
		else
		{
			$this->setLayoutPath($template_path);
			$this->setLayoutFile(_CYMPUSADMIN_LAYOUT_);
		}

		Context::loadLang(_XE_PATH_ . 'modules/cympusadmin/lang/');

		$logged_info = Context::get('logged_info');

		if($logged_info->is_admin == 'Y')
		{
			// parse admin menu
			$oXmlParser = new XmlParser();
			$xml_obj = $oXmlParser->loadXmlFile('./modules/cympusadmin/conf/' . _CYMPUSADMIN_MENU_);
			$admin_menu = cympusadmin::getMenu($xml_obj->menu->item);
			Context::set('cympusadmin_menu', $admin_menu);
		}
		else
		{
			$output = ModuleHandler::triggerCall('cympusadmin.getManagerMenu', 'before', $manager_menu);
			if(!$output->toBool())
			{
				return $output;
			}

			Context::set('cympusadmin_menu', $manager_menu);

		}

		$news = getNewsFromAgency();
		Context::set('news', $news);
		Context::set('admin_bar', 'false');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoXml('cympusadmin');
		Context::set('cympus_modinfo', $module_info);
	}

	/**
	 * Install cympusadmin module
	 * @return Object
	 */
	function moduleInstall()
	{
		return $this->makeObject();
	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();

		if(!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympusadmin', 'model', 'triggerGetManagerMenu', 'before'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Update module
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		if(!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympusadmin', 'model', 'triggerGetManagerMenu', 'before'))
		{
			$oModuleController->insertTrigger('cympusadmin.getManagerMenu', 'cympusadmin', 'model', 'triggerGetManagerMenu', 'before');
		}

		return $this->makeObject();
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache()
	{
	}


	function getMenu(&$in_xml_obj, $depth = 0, &$parent_item = null)
	{
		if(!is_array($in_xml_obj))
		{
			$xml_obj = array($in_xml_obj);
		}
		else
		{
			$xml_obj = $in_xml_obj;
		}
		$act = Context::get('act');

		$menus = array();
		$idx = 0;
		foreach($xml_obj as $it)
		{
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
				foreach($it->action as $action)
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

			if(in_array($act, $obj->action) || ($obj->action_prefix && $obj->action_prefix == substr($act, 0, strlen($obj->action_prefix))))
			{
				$obj->selected = true;
				if($parent_item)
				{
					$parent_item->selected = true;
				}
			}
			if($it->item && ($it->attrs->modinst != 'true' || Context::get('module_srl')))
			{
				$obj->submenu = cympusadmin::getMenu($it->item, $depth + 1, $obj);
				if($obj->selected && $parent_item)
				{
					$parent_item->selected = true;
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
				//TODO : Check again.
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

	/**
	 * Create new Object for php7.2
	 * @param int $code
	 * @param string $msg
	 * @return BaseObject|Object
	 */
	public function makeObject($code = 0, $msg = 'success')
	{
		return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
	}
}
