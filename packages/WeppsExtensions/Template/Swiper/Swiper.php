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

namespace WeppsExtensions\Template\Swiper;

use Smarty\Smarty;
use WeppsCore\TemplateHeaders;

class Swiper
{
	protected TemplateHeaders $headers;
	protected string $rand;

	protected Smarty $smarty;
	// специфической логики, связанной с каруселью Swiper.
	public function __construct(TemplateHeaders &$headers, Smarty &$smarty)
	{
		$this->headers = &$headers;
		$this->rand = &$headers::$rand;
		$this->smarty = &$smarty;
		$this->headers->css("/packages/vendor_local/swiper.11.2.10/swiper-bundle.min.css");
		$this->headers->js("/packages/vendor_local/swiper.11.2.10/swiper-bundle.min.js");
		$this->headers->js("/ext/Template/Swiper/SwiperManager.{$this->rand}.js");
	}
	public function render(array $slides): string
	{
		if (empty($slides)) {
			return '';
		}
		$this->headers->js("/ext/Template/Swiper/Swiper.{$this->rand}.js");
		$this->headers->css("/ext/Template/Swiper/Swiper.{$this->rand}.css");
		$this->smarty->assign('slides', $slides);
		return $this->smarty->fetch('packages/WeppsExtensions/Template/Swiper/Swiper.tpl');
	}
}