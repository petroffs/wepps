<?php
namespace WeppsExtensions\Cart\Delivery\DeliveryPickup;

use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryWepps;

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
		$headers->css("/ext/Cart/Delivery/DeliveryPickup/OperationsNotice.{$headers::$rand}.css");
		$tpl = 'DeliveryPickup/OperationsNotice.tpl';
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