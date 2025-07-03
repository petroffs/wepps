<?php
namespace WeppsExtensions\Cart\Payments\PaymentsYookassa;

use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsWepps;

/* if (!function_exists('autoLoader')) {
	throw new \RuntimeException(
        'This file should be loaded via autoLoader.'
    );
} */

class PaymentsYookassaWepps extends PaymentsWepps
{
	private $get;

	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/path.{$headers::$rand}.js");
		#$headers->css("/path.{$headers::$rand}.css");
		$tpl = 'PaymentsYookassa/PaymentsYookassa.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
	public function form() {
		UtilsWepps::debug('form',1);
	}
	public function return() {
		UtilsWepps::debug('return',1);
	}
	public function check() {
		UtilsWepps::debug('check',1);
	}
	public function test() {
		UtilsWepps::debug('test',1);
	}
}