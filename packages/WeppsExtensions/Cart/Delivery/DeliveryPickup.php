<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class DeliveryPickupWepps extends DeliveryWepps
{
  public function __construct(array $settings)
  {
    parent::__construct($settings);
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