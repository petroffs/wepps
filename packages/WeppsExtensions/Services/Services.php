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

namespace WeppsExtensions\Services;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Services extends Extension
{
	#[\Override]
	public function request(): void
    {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Services/Services.tpl';
				$conditions = "";
				$obj = new Data("Services");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->fetch($conditions, 20, $this->page, "t.Priority");
				$smarty->assign('elements', $res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/Services/Services.{$this->rand}.css");
		$this->headers->js("/ext/Services/Services.{$this->rand}.js");
		$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
}
