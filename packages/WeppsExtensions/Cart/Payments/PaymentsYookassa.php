<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class PaymentsYookassaWepps extends PaymentsWepps
{
	private $get;

	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
		$this->get = $_GET;
		switch (@$this->get['action']) {
			case 'form':
				UtilsWepps::debug(1,1);
				break;
			default:
				break;
		}
	}
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Payments/PaymentsQR.{$headers::$rand}.js");
		#$headers->css("/ext/Cart/Payments/PaymentsQR.{$headers::$rand}.css");
		$tpl = 'PaymentsYookassa.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
}