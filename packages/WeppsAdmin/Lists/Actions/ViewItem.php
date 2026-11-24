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

namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Smarty;

class ViewItem extends Request
{
	public $noclose = 1;
	public $scheme = [];
	public $element = [];
	public $settings = [];
	public $headers;
	#[\Override]
	public function request($action = ""): void
    {
		//$this->get['element']['Name'] = "Wepps";
		$this->element = &$this->get['element'];
		//unset($this->get['listScheme']['Name']);
		$this->scheme = &$this->get['listScheme'];
		$this->settings = &$this->get['listSettings'];
		//TemplateHeaders - передать тип для переменнной $headers
		$this->headers = &$this->get['headers'];
		$this->headers->js("/packages/WeppsAdmin/Lists/Actions/ViewItem.{$this->headers::$rand}.js");

		$smarty = Smarty::getSmarty();

		$smarty->assign('itemGroup', 'Dopoln1');
		$tpl1 = $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/Actions/ViewItemDopoln1.tpl');
		$smarty->assign('itemGroup', 'Dopoln2');
		$tpl2 = $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/Actions/ViewItemDopoln2.tpl');

		//$this->fetch($key, $value);

		/*
		 * Задать навигацию и шаблон для доп. блоков
		 */
		$this->settings['ActionShowIdAddons'] = [
				["title" => "Дополнение 1","group" => "Dopoln1","tpl" => $tpl1],
				["title" => "Дополнение 2","group" => "Dopoln2","tpl" => $tpl2],
		];
	}
}
