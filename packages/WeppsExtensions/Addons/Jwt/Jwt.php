<?php
namespace WeppsExtensions\Addons\Jwt;
use WeppsCore\Connect\ConnectWepps;
use Ahc\Jwt\JWT;
use WeppsCore\Utils\UtilsWepps;

class JwtWepps {
	private $secret;
	
	public function __construct($settings=[]) {
		$this->secret = ConnectWepps::$projectServices['jwt']['secret'];
	}
	public function token_encode(array $payload=[],int $lifetime = 86200) : string {
		$jwt = new JWT($this->secret,'HS256',$lifetime);
		return $jwt->encode($payload);
	}
	public function token_decode(string $token='') : array {
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
	
	public function bearer() : array {
		$headers = UtilsWepps::getAllHeaders();
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

?>