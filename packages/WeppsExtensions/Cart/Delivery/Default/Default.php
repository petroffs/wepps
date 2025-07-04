<?php
namespace WeppsExtensions\Cart\Delivery\Default;

use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryWepps;

class DefaultWepps extends DeliveryWepps
{
  public function __construct(array $settings, CartUtilsWepps $cartUtils)
  {
    parent::__construct($settings, $cartUtils);
  }
}