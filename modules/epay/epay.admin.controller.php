<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class epayAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief epay admin controller
 **/
class epayAdminController extends epay
{
	/**
	 * @brief insert module instance info.
	 **/
	function procEpayAdminInsertEpay()
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'epay';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) {
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) {
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		} else {
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$this->setRedirectUrl(getNotencodedUrl('','module',Context::get('module'),'act','dispEpayAdminInsertEpay','module_srl',$output->get('module_srl')));
	}

	/**
	 * @brief delete module instance.
	 */
	function procEpayAdminDeleteModInst()
	{
		$module_srl = Context::get('module_srl');
		// 원본을 구해온다
		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module','epay');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');
		$this->setRedirectUrl(getNotencodedUrl('','module',Context::get('module'),'act','dispEpayAdminEpayList'));
	}

	/**
	 * @brief insert plugin info. (it will be deleted in the future)
	 */
	function procEpayAdminInsertPlugin()
	{
		$plugin_srl = getNextSequence();
		$args->plugin_srl = $plugin_srl;
		$args->plugin = Context::get('plugin');
		$args->title = Context::get('title');
		$output = executeQuery("epay.insertPlugin", $args);
		if(!$output->toBool()) return $output;

		require_once(_XE_PATH_.'modules/epay/epay.plugin.php');
		require_once(_XE_PATH_.'modules/epay/plugins/'.$args->plugin.'/'.$args->plugin.'.plugin.php');

		$tmp_fn = create_function('', "return new {$args->plugin}();");
		$oPlugin = $tmp_fn();
		if (@method_exists($oPlugin,'pluginInstall'))
		{
			$oPlugin->pluginInstall($args);
		}

		// 결과 리턴
		$this->add('plugin_srl', $plugin_srl);
	}

	/**
	 * @brief update plugin info. (it will be deleted in the future)
	 */
	function procEpayAdminUpdatePlugin()
	{
		$oEpayModel = &getModel('epay');

		// module, act, layout_srl, layout, title을 제외하면 확장변수로 판단.. 좀 구리다..
		$extra_vars = Context::getRequestVars();
		unset($extra_vars->module);
		unset($extra_vars->act);
		unset($extra_vars->plugin_srl);
		unset($extra_vars->plugin);
		unset($extra_vars->title);

		$args = Context::gets('plugin_srl','title');

		$plugin_info = $oEpayModel->getPluginInfo($args->plugin_srl);

		// extra_vars의 type이 image일 경우 별도 처리를 해줌
		if($plugin_info->extra_var) {
			foreach($plugin_info->extra_var as $name => $vars) {
				if($vars->type!='image') continue;

				$image_obj = $extra_vars->{$name};
				$extra_vars->{$name} = $plugin_info->extra_var->{$name}->value;

				// 삭제 요청에 대한 변수를 구함
				$del_var = $extra_vars->{"del_".$name};
				unset($extra_vars->{"del_".$name});
				// 삭제 요청이 있거나, 새로운 파일이 업로드 되면, 기존 파일 삭제
				if($del_var == 'Y' || $image_obj['tmp_name']) {
					FileHandler::removeFile($extra_vars->{$name});
					$extra_vars->{$name} = '';
					if($del_var == 'Y' && !$image_obj['tmp_name']) continue;
				}

				// 정상적으로 업로드된 파일이 아니면 무시
				if(!$image_obj['tmp_name'] || !is_uploaded_file($image_obj['tmp_name'])) continue;

				// 이미지 파일이 아니어도 무시 (swf는 패스~)
				if(!preg_match("/\.(jpg|jpeg|gif|png|swf|enc|pem)$/i", $image_obj['name'])) continue;

				// 경로를 정해서 업로드
				if ($vars->location)
				{
					$location = $this->mergeKeywords($vars->location,$extra_vars);
					$path = sprintf("./files/epay/%s/%s/",$args->plugin_srl,$location);
				}
				else
				{
					$path = sprintf("./files/attach/images/%s/", $args->plugin_srl);
				}

				// 디렉토리 생성
				if(!FileHandler::makeDir($path)) continue;

				$filename = $path.$image_obj['name'];

				// 파일 이동
				if(!move_uploaded_file($image_obj['tmp_name'], $filename)) continue;

				$extra_vars->{$name} = $filename;
			}
		}

		// DB에 입력하기 위한 변수 설정
		$args->extra_vars = serialize($extra_vars);

		$output = executeQuery('epay.updatePlugin', $args);
		if(!$output->toBool()) return $output;

		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout.html');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile("top_refresh.html");
	}

	/**
	 * delete plugin info. (it will be deleted in the future)
	 */
	function procEpayAdminDeletePlugin()
	{
		$plugin_srl = Context::get('plugin_srl');
		if (!$plugin_srl) return new Object(-1, 'msg_invalid_request');
		$args->plugin_srl = $plugin_srl;
		$output = executeQuery('epay.deletePlugin',$args);
		if (!$output->toBool()) return $output;

		FileHandler::removeDir(sprintf(_XE_PATH_."files/epay/%s",$plugin_srl));

		$this->setMessage('success_deleted');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispEpayAdminPluginList','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief update the state of payments
	 */
	function procEpayAdminUpdateState()
	{
		if(!Context::get('transaction_srl') || !Context::get('state')) return;
		else
		{
			$args->transaction_srl = Context::get('transaction_srl');
			$args->state = Context::get('state');

			$output = executeQuery('epay.updateTransaction', $args);
			if(!$output->toBool()) return $output;
		}
	}
}
/* End of file epay.admin.controller.php */
/* Location: ./modules/epay/epay.admin.controller.php */
