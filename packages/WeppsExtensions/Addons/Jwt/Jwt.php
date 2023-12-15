<?php
namespace WeppsExtensions\Addons\Jwt;
use WeppsCore\Connect\ConnectWepps;
use Ahc\Jwt\JWT;

class JwtWepps {
	private $secret;
		
	public function __construct($settings=[]) {
		$this->secret = ConnectWepps::$projectServices['jwt']['secret'];
	}
	
	public function getTokenPayload($token='') {
		$jwt = new JWT($this->secret);
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
	
	public function getTokenBearer() {
		$headers = getallheaders();
		foreach ($headers as $key=>$value) {
			$headers[strtolower($key)] = $value;
		}
		$bearer = 'Bearer ';
		if (!empty($headers['authorization']) && strstr($headers['authorization'], $bearer)) {
			$token = str_replace($bearer, '', $headers['authorization']);
			$output = $this->getTokenPayload($token);
			return $output;
		}
		$output = [
				'type'=>'jwt',
				'status'=>500,
				'message'=>'no token'
		];
		return $output;
	}
}

?>