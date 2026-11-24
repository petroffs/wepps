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

namespace WeppsExtensions\Addons\Jwt;

use WeppsCore\Connect;
use Ahc\Jwt\JWT as VendorJWT;
use WeppsCore\Utils;

class Jwt
{
	private $secret;

	public function __construct($settings = [])
    {
		$this->secret = Connect::$projectServices['jwt']['secret'];
	}
	public function token_encode(array $payload = [], int $lifetime = 86200): string
    {
		$jwt = new VendorJWT($this->secret, 'HS256', $lifetime);
		return $jwt->encode($payload);
	}
	public function token_decode(string $token = ''): array
    {
		$jwt = new VendorJWT($this->secret);
		try {
			$output = [
					'type' => 'jwt',
					'status' => 200,
					'message' => 'OK',
					'payload' => $jwt->decode($token)
			];
		} catch (\Exception $e) {
			$output = [
					'type' => 'jwt',
					'status' => (strstr($e->getMessage(), ': Expired')) ? 401 : 400,
					'message' => $e->getMessage(),
					'code' => $e->getCode(),
			];
		}
		return $output;
	}

	public function bearer(): array
    {
		$headers = Utils::getAllHeaders();
		$bearer = 'Bearer ';
		if (!empty($headers['authorization']) && strstr($headers['authorization'], $bearer)) {
			$token = str_replace($bearer, '', $headers['authorization']);
			$output = $this->token_decode($token);
			return $output;
		}
		$output = [
				'type' => 'jwt',
				'status' => 403,
				'message' => 'no token'
		];
		return $output;
	}
}
