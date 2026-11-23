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

namespace WeppsExtensions\Cart;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;

class Cart extends Extension {
	#[\Override]
	public function request(): void {
		$smarty = Smarty::getSmarty();
		$cartUtils = new CartUtils();
		$this->tpl = __DIR__ . '/Cart.tpl';
		$this->headers->meta('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">');
		$this->headers->meta('<meta http-equiv="Pragma" content="no-cache">');
		$this->headers->meta('<meta http-equiv="Expires" content="0">');
		$cartUtils->setHeaders($this->headers);
		switch (Navigator::$pathItem) {
			case '':
				$template = new CartTemplates($smarty,$cartUtils);
				$template->default();
				break;
			case 'checkout':
				$this->extensionData['element'] = 1;
				$headers = new TemplateHeaders();
				$cartUtils->setHeaders($headers); # зачем ?
				$template = new CartTemplates($smarty,$cartUtils);
				$template->checkout();
				break;
			case 'order':
				$this->extensionData['element'] = 1;
				$template = new CartTemplates($smarty,$cartUtils);
				$template->order();
				break;
			case 'notice':
				$this->extensionData['element'] = 1;
				break;
			default:
				/* $this->extensionData['element'] = 1;
				$template = new CartTemplates($smarty,$cartUtils);
				$template->empty(); */
				Exception::error404();
				break;
		}
		$smarty->assign('normalView',0);
		$apikey = Connect::$projectServices['yandexmaps']['apikey'];
		$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
		$this->headers->css("/ext/Cart/Cart.{$this->headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$this->headers::$rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}