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

namespace WeppsExtensions\Gallery;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Gallery extends Extension {
	#[\Override]
	/**
	 * @return void
	 */
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Gallery/Gallery.tpl';
				$obj = new Data("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
			$obj->setParams([(int)$this->navigator->content['Id']]);
			$res = $obj->fetch("t.TableName='Gallery' and fg.NavigatorId = ?",500,1,'t.Priority');
				$smarty->assign('elements',$res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/packages/vendor/dimsemenov/magnific-popup/dist/magnific-popup.css");
		$this->headers->js("/packages/vendor/dimsemenov/magnific-popup/dist/jquery.magnific-popup.js");
		$this->headers->css("/ext/Gallery/Gallery.{$this->rand}.css");
		$this->headers->js("/ext/Gallery/Gallery.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}