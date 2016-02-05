<?php
	function checkRecentItems($module_name=null, $addon_info=null, $module_type=null)
	{
		$prefix = trim($addon_info->r_trolley_id);

		// array 체크 후 불필요한것 삭제.
		if($_COOKIE[$prefix.'Recent_item']) 
		{
			$prev_cookie = explode(',',$_COOKIE[$prefix.'Recent_item']);
			// 비어있는 array 삭제.
			foreach($prev_cookie as $k => $v)
			{
				if(!$v)
				{
					$present_cookie = array_pop($document_srl_array);
					SetCookie($prefix.'Recent_item', $present_cookie, 30*24*60*60+time(), '/');
				}
			}
		}
		else setCookie($prefix.'Recent_item', '');

		/*
		if(!Context::get('document_srl') && Context::get('item_srl'))
		{
			debugPrint("A-3-1");
			$args->item_srl = Context::get('item_srl');
			$output = executeQuery($module_type.'.getItemInfo', $args);
			Context::set('document_srl', $output->data->document_srl);
		}
		*/

		// nstore 나 digital 일때 상품 클릭시 쿠키 셋업
		if(Context::get('document_srl'))
		{
			$document_srl_get = Context::get('document_srl');  // document_srl을 가져온다 
			$item_name = Context::get('item_name');
		
			if(!$_COOKIE[$prefix.'Recent_item'])
			{
				SetCookie($prefix.'Recent_item', $document_srl_get, 30*24*60*60+time(), '/'); // Cookie['document_srl']이 없을때
			}

			if($_COOKIE[$prefix.'Recent_item'] && $document_srl_get) // Cookie가 있을때
			{
				$srl_count = explode(',',$_COOKIE[$prefix.'Recent_item']); //string을 array로
				$compare = strstr($_COOKIE[$prefix.'Recent_item'], $document_srl_get); //document_srl_get과 쿠키가 중복되는 지 확인

				// 중복시
				if($compare && $document_srl_get)
				{
					$srl_count = explode(',',$_COOKIE[$prefix.'Recent_item']); //string을 array로
					$document_srl_get_array = array( 0 => $document_srl_get);
					$diff_srl = array_diff($srl_count, $document_srl_get_array);;
					//array_shift($srl_count);  //array 의 첫번째를 빼준다.
					array_push($diff_srl,$document_srl_get); //array 의 마지막에 넣는다
					$document_srl = implode(',',$diff_srl);
					SetCookie($prefix.'Recent_item', $document_srl, 30*24*60*60+time(), '/');
				}
				// 비중복시
				else if(!$compare && $document_srl_get)
				{
					if(count($srl_count) < 13)  //Cookie의 array가 13개 이하일때 
					{
						$document_srl = $_COOKIE[$prefix.'Recent_item'];
						$document_srl = $document_srl.','.$document_srl_get;
						SetCookie($prefix.'Recent_item', $document_srl, 30*24*60*60+time(), '/');
					}
					else //Cookie의 array가 12개 이상일때
					{
						array_shift($srl_count);  //array 의 첫번째를 빼준다.
						array_push($srl_count,$document_srl_get); //array 의 마지막에 넣는다
						$document_srl = implode(',',$srl_count);
						SetCookie($prefix.'Recent_item', $document_srl, 30*24*60*60+time(), '/');
					}
				}
			}
		}
	}

	function setRecentView($addon_info)
	{
		$prefix = trim($addon_info->r_trolley_id);

		$t_ncart_mid = $addon_info->r_ncart_mid;
		if(!$t_ncart_mid) $t_ncart_mid = null;

		Context::set('t_ncart_mid', $t_ncart_mid);
		Context::set('addon_info', $addon_info);
		Context::set('r_prefix', $prefix);

		$mid = Context::get('mid');

		/*
		$document_srl_array = explode(',',$_COOKIE[$prefix.'Recent_item']);

		$url_arr = array();

		// nstore 와 digital에 따라 item 정보를 가져와 셋팅한다.
		foreach($document_srl_array as $key => $v)
		{
			$search_type = substr($v, -1, 1);
			if($search_type == 'N')
			{
				require_once('./modules/nstore_core/nstore_core.item.php');
				$oNstore_coreModel = &getModel('nstore');
				$config = $oNstore_coreModel->getModuleConfig();

				$srl = substr($v, 0, -1);
				$args->document_srl = $srl;
				$output = executeQuery('nstore.getItemInfo', $args);

				$item = new nstore_coreItem('nstore', $output->data,$config->currency);

				$value_url = getUrl('mid',$mid,'act','dispNstoreItemDetail','document_srl',$srl);
				$value_src = $item->getThumbnail(80,80);
				$value_name = $item->item_name;

				$url_arr[] = array("document_srl" => $v, "item_url" => $value_url, "item_src" => $value_src, "item_name" => $value_name, "class" => '', "price" => $item->printPrice($item->price), "item_price" => $item->price);
			}
			else if($search_type == 'D')
			{
				require_once('./modules/nstore_core/nstore_core.item.php');
				$oNstore_coreModel = &getModel('nstore_digital');
				$config = $oNstore_coreModel->getModuleConfig();

				$srl = substr($v, 0, -1);
				$args->document_srl = $srl;
				$output = executeQuery('nstore_digital.getItemInfo', $args);

				$item = new nstore_coreItem('nstore_digital', $output->data,$config->currency);
				
				$value_url = getUrl('mid',$mid,'act','dispNstore_digitalItemDetail','document_srl',$srl);
				$value_src = $item->getThumbnail(80,80);
				$value_name = $item->item_name;

				//<div id=\'lately_close\' class = \'close_'.$i.'\' onClick=\"delitem('.$i.')\"></div>
				$url_arr[] = array("document_srl" => $v, "item_url" => $value_url, "item_src" => $value_src, "item_name" => $value_name, "class" => '', "price" => $item->printPrice($item->price), "item_price" => $item->price);
			}
		}
		$url_count = (count($url_arr)-1); 

		// item_count set.
		SetCookie('url_count', $url_count, 30*24*60*60+time(), '/');

		$recent_total = 0;
		for($i = $url_count; $i >= 0; $i--)
		{
			$url = $url_arr[$i];
			$url_arr[$i]['class'] = "url_".$i;
			$recent_items[] = $url_arr[$i];
			$recent_total.=$url_arr[$i]['item_price']; 
		}

		// recently_items set.
		Context::set('recently_items',$recent_items);
		*/


		// mileage 연동
		
		$logged_info = Context::get('logged_info');
		if($logged_info)
		{
			$oNmileageModel = &getModel('nmileage');
			if(!$oNmileageModel)
			{
				Context::set('t_mileage', 'nmileage not installed.');
			}
			else
			{
				$t_mileage = $oNmileageModel->getMileage($logged_info->member_srl);
				Context::set('t_mileage', $t_mileage);
			}
		}

		/*
		// pagenum set

		if($_COOKIE['pagenum'] == 1 || !$_COOKIE['pagenum'])
		{
			for($r = $url_count; $r > $url_count-4; $r--)
			{
				Context::addHtmlHeader('<script>jQuery(document).ready(function (){jQuery("#url_'.$r.'").css("display","");})</script>');
			}
		}
		if($_COOKIE['pagenum'] == 2)
		{
			for($r = $url_count-4; $r > $url_count-8; $r--)
			{
				Context::addHtmlHeader('<script>jQuery(document).ready(function (){jQuery("#url_'.$r.'").css("display","");})</script>');
			}
		}
		if($_COOKIE['pagenum'] == 3)
		{
			for($r = $url_count-8; $r > $url_count-12; $r--)
			{
				Context::addHtmlHeader('<script>jQuery(document).ready(function (){jQuery("#url_'.$r.'").css("display","");})</script>');
			}
		}

		 */
	}

	function setDocumentEvent($addon_info)
	{
		$oDocumentModel = getModel('document');

		$args->mid = $addon_info->event_mid;
		$output = executeQuery('module.getMidInfo',$args);
		if(!$output->toBool())
		{
			return $output;
		}
		
		$args->module_srl = $output->data->module_srl;

		if(!$addon_info->event_count) $event_count = 1;
		else $event_count = $addon_info->event_count;
		for($i = 0; $i < $event_count; $i++)
		{
			$output = executeQueryArray('document.getDocumentList', $args);

			$array_number = count($output->data);
			$array_number = $array_number - $i;

			if($output->data[$array_number]->document_srl)
			{
				$event_url[$output->data[$array_number]->document_srl] = getNotEncodedUrl('', 'document_srl', $output->data[$array_number]->document_srl);
				$document[] = $oDocumentModel->getDocument($output->data[$array_number]->document_srl);

				$args->document_srl = $output->data[$array_number]->document_srl;
				$output = executeQueryArray('document.getDocumentExtraVars', $args);
				if($output->data)
				{
					foreach($output->data as $k => $v)
					{
						if($v->eid == 'event_url' && $v->value)
						{
							$event_url[$v->document_srl] = $v->value;
						}
					}
				}
			}
		}

		if($addon_info->event_count && $addon_info->event_mid)
		{
			Context::set('r_doc_info', $document);
		}

		Context::set('event_url', $event_url);

		$o_event_size = 300;
		if($addon_info->o_event_size) $o_event_size = $addon_info->o_event_size;
		Context::set('o_event_size',$o_event_size);

		$f_event_size = 120;
		if($addon_info->f_event_size) $f_event_size = $addon_info->f_event_size;
		Context::set('f_event_size',$f_event_size);

	}

	function setButtonEvent($addon_info)
	{
		for($i = 1; $i < 5; $i++)
		{
			$button_url = "r_button0".$i."_url";
			$button_name = "r_button0".$i;
			if(trim($addon_info->$button_url) && trim($addon_info->$button_name))
			{
				Context::set($button_name, 'Y');
			}
		}
	}
?>
