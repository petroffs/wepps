<?php
namespace WeppsExtensions\Cart\Payments\PaymentsQR;

use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsWepps;

class PaymentsQRWepps extends PaymentsWepps
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
		$tpl = 'PaymentsQR/PaymentsQR.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
}