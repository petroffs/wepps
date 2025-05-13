<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class DeliveryDefaultWepps extends DeliveryWepps
{
  public function __construct(array $settings)
  {
    parent::__construct($settings);
    $this->setDeliveryType(2);
  }
  public function getOperations() {
        return [
            'tpl' => 'OperationsAddress.tpl',
            'data' => [],
            'allowOrderBtn' => true
        ];
    }
}