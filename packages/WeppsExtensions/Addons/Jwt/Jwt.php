<?php
namespace WeppsExtensions\Addons\Jwt;

use WeppsCore\Connect;
use Ahc\Jwt\JWT as VendorJWT;
use WeppsCore\Utils;

class Jwt {
	private $secret;
	
	public function __construct($settings=[]) {
		$this->secret = Connect::$projectServices['jwt']['secret'];
	}
	public function token_encode(array $payload=[],int $lifetime = 86200) : string {
		$jwt = new VendorJWT($this->secret,'HS256',$lifetime);
		return $jwt->encode($payload);
	}
	public function token_decode(string $token='') : array {
		$jwt = new VendorJWT($this->secret);
		try {
			$output = [
					'type'=>'jwt',
					'status'=>200,
					'message'=>'OK',
					'payload'=>$jwt->decode($token)
			];
		} catch (\Exception $e) {
			$output = [
					'type'=>'jwt',
					'status'=>(strstr($e->getMessage(), ': Expired'))?401:500,
					'message' => $e->getMessage(),
					'code' => $e->getCode(),
			];
		}
		return $output;
	}
	
	public function bearer() : array {
		$headers = Utils::getAllHeaders();
		$bearer = 'Bearer ';
		if (!empty($headers['authorization']) && strstr($headers['authorization'], $bearer)) {
			$token = str_replace($bearer, '', $headers['authorization']);
			$output = $this->token_decode($token);
			return $output;
		}
		$output = [
				'type'=>'jwt',
				'status'=>403,
				'message'=>'no token'
		];
		return $output;
	}
}