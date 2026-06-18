<?php
require_once __DIR__ . '/../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsAdmin\ConfigExtensions\Processing\ProcessingProducts;
use WeppsAdmin\ConfigExtensions\Processing\ProcessingRestApi;
use WeppsAdmin\ConfigExtensions\Processing\ProcessingTasks;
use WeppsCore\Request;
use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsAdmin\Lists\Lists;
use WeppsCore\Connect;
use WeppsExtensions\Addons\Bot\BotSystem;

class RequestProcessing extends Request
{
	public function request($action = "")
	{
		$this->tpl = '';
		if (empty($this->cli) && @Connect::$projectData['user']['ShowAdmin'] != 1) {
			Exception::error404();
		}
		try {
			switch ($action) {
				case 'tasks':
					$obj = new BotSystem();
					$obj->tasks();
					break;
				case 'searchindex':
					$str = Lists::setSearchIndex();
					Connect::$db->exec($str);
					break;
				case 'removefiles':
					$obj = new ProcessingTasks();
					$obj->removeFiles();
					break;
				case 'resetproducts':
					$obj = new ProcessingProducts();
					$obj->resetProducts();
					break;
				case 'resetproductsaliases':
					$obj = new ProcessingProducts();
					$obj->resetProductsAliases();
					break;
				case 'generateproductsvariations':
					$obj = new ProcessingProducts();
					$obj->generateProductsVariations();
					break;
				case 'resetproductsvariations':
					$obj = new ProcessingProducts();
					$obj->resetProductsVariationsAll();
					break;
				case 'clearcache':
					$obj = new BotSystem();
					$obj->clearCache();
					break;
				case 'mappingtypes':
					$obj = new ProcessingRestApi();
					$obj->mappingTypes();
					break;
				case 'mappingnames':
					$obj = new ProcessingRestApi();
					$obj->mappingNames();
					break;
				case 'addtests':
					$obj = new ProcessingRestApi();
					$obj->addTests();
					break;
				default:
					Utils::debug('def1', 1);
					Exception::error404();
					break;
			}
		} catch (\Exception $e) {
			AdminUtils::modal('❌ Ошибка: ' . $e->getMessage(), $this->cli);
			return;
		}
		AdminUtils::modal('✅ Обработка завершена', $this->cli);
	}
}
$request = new RequestProcessing(!empty($argv) ? $argv : $_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);