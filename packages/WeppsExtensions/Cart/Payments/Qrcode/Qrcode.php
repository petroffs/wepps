<?php
namespace WeppsExtensions\Cart\Payments\Qrcode;

use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Payments\Payments;

class Qrcode extends Payments
{
	public function __construct(array $settings = [], CartUtils $cartUtils)
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