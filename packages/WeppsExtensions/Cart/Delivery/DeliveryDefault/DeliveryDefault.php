<?php
namespace WeppsExtensions\Cart\Delivery\DeliveryDefault;

use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryWepps;

class DeliveryDefaultWepps extends DeliveryWepps
{
  public function __construct(array $settings, CartUtilsWepps $cartUtils)
  {
    parent::__construct($settings, $cartUtils);
  }
}