<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class DeliveryRussianPostWepps extends DeliveryWepps
{
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->setDeliveryType(1);
    }
    public function getOperations()
    {
        return [
            'tpl' => 'OperationsPoints.tpl',
            'data' => [],
            'allowOrderBtn' => false
        ];
    }
}