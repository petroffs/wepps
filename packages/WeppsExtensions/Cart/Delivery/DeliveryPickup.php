<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryPickupWepps extends DeliveryWepps
{
  public function __construct(array $settings, CartUtilsWepps $cartUtils)
  {
    parent::__construct($settings, $cartUtils);
  }
  public function getOperations()
  {
    $tpl = 'OperationsNotice.tpl';
    $data = [
      'text' => $this->settings['Descr']
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