<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class DeliveryDefaultWepps extends DeliveryWepps
{
    public function __construct() {
		parent::__construct();
        $this->setDeliveryType(2);
	}
}