<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileage
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileage
 */
class nmileage extends ModuleObject 
{
        /**
         * @brief 모듈 설치 실행
         **/
        function moduleInstall()
        {
        }

        /**
         * @brief 설치가 이상없는지 체크
         **/
        function checkUpdate()
        {
			$oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

			// 2013. 09. 25 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'nmileage', 'model', 'triggerModuleListInSitemap', 'after')) return true;

			return FALSE;
        }

        /**
         * @brief 업데이트(업그레이드)
         **/
        function moduleUpdate()
        {
			$oDB = &DB::getInstance();
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');

			// 2013. 09. 25 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'nmileage', 'model', 'triggerModuleListInSitemap', 'after'))
				$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'nmileage', 'model', 'triggerModuleListInSitemap', 'after');


			return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시파일 재생성
         **/
        function recompileCache()
        {
        }
}

/* End of file nmileage.class.php */
/* Location: ./modules/nmileage/nmileage.class.php */
