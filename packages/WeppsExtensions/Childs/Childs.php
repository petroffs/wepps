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

namespace WeppsExtensions\Childs;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;
use WeppsCore\Utils;

class Childs extends Extension {
	#[\Override]
	/**
	 * @return void
	 */
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Childs/Childs.tpl';
				#Utils::debug($this->navigator->child,1);
				$smarty->assign('elements',$this->navigator->child);
				break;
			default:
				Exception::error404();
				break;
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Childs/Childs.{$this->rand}.css");
		#$this->headers->js("/ext/Childs/Childs.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>