<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryPickupWepps extends DeliveryWepps
{
	public function __construct(array $settings, CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	public function getOperations()
	{
		$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Delivery/OperationsNotice.{$headers::$rand}.js");
		$headers->css("/ext/Cart/Delivery/OperationsNotice.{$headers::$rand}.css");
		$tpl = 'OperationsNotice.tpl';
		$data = [
			'text' => $this->settings['Descr'],
			'address' => ConnectWepps::$projectInfo['address'],
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