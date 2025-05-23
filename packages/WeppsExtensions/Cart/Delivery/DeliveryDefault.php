<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryDefaultWepps extends DeliveryWepps
{
  public function __construct(array $settings, CartUtilsWepps $cartUtils)
  {
    parent::__construct($settings, $cartUtils);
  }
}