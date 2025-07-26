<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsAdmin\Admin\AdminUtilsWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Addons\Bot\BotSystemWepps;

require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../configloader.php';

class RequestProcessingWepps extends RequestWepps
{
	public function request($action = "")
	{
		$this->tpl = '';
		if (empty($this->cli) && @ConnectWepps::$projectData['user']['ShowAdmin'] != 1) {
			ExceptionWepps::error404();
		}
		switch ($action) {
			case "tasks":
				$obj = new BotSystemWepps();
				$obj->tasks();
				break;
			case "searchindex":
				$str = ListsWepps::setSearchIndex();
				ConnectWepps::$db->exec($str);
				break;
			case "removefiles":
				$obj = new ProcessingTasksWepps();
				$obj->removeFiles();
				break;
			case "resetproducts":
				$obj = new ProcessingProductsWepps();
				$obj->resetProducts();
				break;
			case "resetproductsaliases":
				$obj = new ProcessingProductsWepps();
				$obj->resetProductsAliases();
				break;
			case "generateproductsvariations":
				$obj = new ProcessingProductsWepps();
				$obj->generateProductsVariations();
				break;
			case "resetproductsvariations":
				$obj = new ProcessingProductsWepps();
				$obj->resetProductsVariationsAll();
				break;
			default:
				UtilsWepps::debug('def1', 1);
				ExceptionWepps::error404();
				break;
		}
		AdminUtilsWepps::modal('Обработка завершена', $this->cli);
	}
}
$request = new RequestProcessingWepps(!empty($argv) ? $argv : $_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);