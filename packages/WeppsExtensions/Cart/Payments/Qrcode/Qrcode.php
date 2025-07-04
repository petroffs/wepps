<?php
namespace WeppsExtensions\Cart\Payments\Qrcode;

use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsWepps;

class QrcodeWepps extends PaymentsWepps
{
	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Payments/PaymentsQR.{$headers::$rand}.js");
		#$headers->css("/ext/Cart/Payments/PaymentsQR.{$headers::$rand}.css");
		$tpl = 'Qrcode/Qrcode.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
}