<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class DeliveryWepps
    
{
    /**
     * 
     * Тип доставки
     * 1 - Доставка в ПВЗ
     * 2 - Доставка до двери
     * @var int
     */
    private $type = 1;
    public function __construct()
    {

    }

    public function getTariff()
    {

    }
    public function getPoints()
    {

    }

    public function getAddressForm()
    {

    }
    public function setDeliveryType(int $type = 1) {
        $this->type = $type;
    }
}