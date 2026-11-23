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

namespace WeppsExtensions\Addons\Redirect;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Redirect extends Extension {
	#[\Override]
	/**
	 * @return void
	 */
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Addons/Redirect/Redirect.tpl';
				if (isset($this->navigator->child[0])) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: {$this->navigator->child[0]['Url']}");
					exit();
				}
				break;
			default:
				Exception::error404();
				break;
		}
		
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Redirect/Redirect.{$this->rand}.css");
		$this->headers->js("/ext/Redirect/Redirect.{$this->rand}.js");
		
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>