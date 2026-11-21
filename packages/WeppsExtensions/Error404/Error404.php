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

namespace WeppsExtensions\Error404;

use WeppsCore\Smarty;
use WeppsCore\Extension;

class Error404 extends Extension {
	public function request() {
		$root = $_SERVER['DOCUMENT_ROOT'];
		$this->tpl = $root.'/packages/WeppsExtensions/Error404/Error404.tpl';
		$smarty = Smarty::getSmarty();
		$this->headers->css("/ext/Error404/Error404.{$this->rand}.css");
		$this->headers->js("/ext/Error404/Error404.{$this->rand}.js");
		$smarty->assign('normalView',0);
		$this->extensionData['element'] = 1;
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));		
		return;
	}
}