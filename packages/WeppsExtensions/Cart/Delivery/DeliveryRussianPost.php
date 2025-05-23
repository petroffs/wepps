<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryRussianPostWepps extends DeliveryWepps
{
    public function __construct(array $settings,CartUtilsWepps $cartUtils)
    {
        parent::__construct($settings,$cartUtils);
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