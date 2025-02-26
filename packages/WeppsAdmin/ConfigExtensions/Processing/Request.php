<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Connect\ConnectWepps;

require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../configloader.php';

/**
 * @var \Smarty $smarty
 */

class RequestProcessingWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (empty($this->cli) && @ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
			ExceptionWepps::error404();
		}
		switch ($action) {
			case "searchindex":
				$str = ListsWepps::setSearchIndex();
				ConnectWepps::$db->exec($str);
				break;
			case "resetproducts":
				$obj = new ProcessingProductsWepps();
				$obj->resetProducts();
				break;
			case "namesproducts":
				/*
				 * Не используется в UI
				 */
				$obj = new ProcessingProductsWepps();
				$obj->changeProductsNames();
				break;
			case "removefiles":
				$obj = new ProcessingTasksWepps();
				$obj->removeFiles();
				break;
			default:
				UtilsWepps::debug('def1',1);
				ExceptionWepps::error404();
				break;
		}
		UtilsWepps::modal('Обработка завершена',$this->cli);
	}
}
$request = new RequestProcessingWepps (!empty($argv)?$argv:$_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);