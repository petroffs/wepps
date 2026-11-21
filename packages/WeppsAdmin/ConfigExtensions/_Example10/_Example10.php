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

namespace WeppsAdmin\ConfigExtensions\_Example10;

use WeppsCore\Request;
use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Exception;

class _Example10 extends Request
{
	public $title;
	public function request($action = "")
	{

		$smarty = Smarty::getSmarty();
		$this->tpl = '_Example10.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [
			[
				'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/",
				'Name' => $this->title
			]
		];
		$smarty->assign('test1', 'test1');
		$smarty->assign('url', '/packages/WeppsAdmin/ConfigExtensions/_Example10/Request.php');
		$headers = new TemplateHeaders();
		$headers->js("/packages/WeppsAdmin/ConfigExtensions/_Example10/_Example10.{$headers::$rand}.js");
		$headers->css("/packages/WeppsAdmin/ConfigExtensions/_Example10/_Example10.{$headers::$rand}.css");
		switch ($action) {
			case 'examplelink':
				$this->title = "Тест";
				$this->tpl = '_Example10Test.tpl';
				break;
			default:
				if ($action != "") {
					Exception::error404();
				}
				break;
		}
		$this->headers = &$headers;
	}
}