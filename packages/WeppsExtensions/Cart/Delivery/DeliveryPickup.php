<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryPickupWepps extends DeliveryWepps
{
  public function __construct(array $settings,CartUtilsWepps $cartUtils)
  {
    parent::__construct($settings,$cartUtils);
    $this->setDeliveryType(1);
  }
  public function getOperations() {
        return [
            'tpl' => 'OperationsNotice.tpl',
            'data' => [],
            'allowOrderBtn' => true
        ];
    }
}