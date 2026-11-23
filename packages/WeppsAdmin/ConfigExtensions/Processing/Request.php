<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsAdmin\ConfigExtensions\Processing;

require_once __DIR__ . '/../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsAdmin\Lists\Lists;
use WeppsCore\Connect;
use WeppsExtensions\Addons\Bot\BotSystem;

class RequestProcessing extends Request
{
	#[\Override]
	public function request($action = ""): void
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
				case 'clearminify':
					$obj = new ProcessingTasks();
					$obj->clearMinify();
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
				case 'restapdocs':
					$files = (new RestApiDocs())->generate();
					$message = '✅ Создано файлов: ' . count($files) . ' в .docs/restapi (auth, clientapp, m2m — HTML + Markdown)';
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
		AdminUtils::modal($message ?? '✅ Обработка завершена', $this->cli);
	}
}
$request = new RequestProcessing(!empty($argv) ? $argv : $_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);