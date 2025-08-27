<?php
namespace WeppsExtensions\Cart\Delivery\Pickup;

use WeppsCore\Connect;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\Delivery;

class Pickup extends Delivery
{
	public function __construct(array $settings, CartUtils $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	public function getOperations()
	{
		$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Delivery/Notice.{$headers::$rand}.js");
		$headers->css("/ext/Cart/Delivery/Pickup/Notice.{$headers::$rand}.css");
		$tpl = 'Pickup/Notice.tpl';
		$data = [
			'text' => $this->settings['Descr'],
			'address' => Connect::$projectInfo['address'],
		];
		$allowBtn = true;
		return [
			'title' => $this->settings['Name'],
			'ext' => $this->settings['DeliveryExt'],
			'tpl' => $tpl,
			'data' => $data,
			'active' => @$this->cartUtils->getCart()['deliveryOperations'],
			'allowOrderBtn' => $allowBtn
		];

	}
}