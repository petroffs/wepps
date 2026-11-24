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

namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsExtensions\Addons\RemoteServices\RemoteServices;

class Dadata extends RemoteServices
{
	private $token = '';

	public function __construct($settings = [])
    {
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Accept', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Authorization', 'Token ' . $this->token);
		$this->settings = $settings;
	}

	/*
	 * API справочников: отделения Почты России
	 */
	public function getPostalCode($city): array
    {
		$url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/postal_office";
		$body = '{"query": "' . addslashes($city) . '"}';
		return $this->getResponse($url, $body);
	}

	/*
	 * Поиск Идентификатор города в СДЭК, Boxberry и DPD
	 */
	public function getDeliveryCode($cityKladrCode): array
    {
		$url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/delivery";
		$body = '{"query": "' . addslashes($cityKladrCode) . '"}';
		return $this->getResponse($url, $body);
	}
}
