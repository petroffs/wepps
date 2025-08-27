<?php
namespace WeppsExtensions\Cart\Delivery\DefaultDelivery;

use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\Delivery;

class DefaultDelivery extends Delivery
{
  public function __construct(array $settings, CartUtils $cartUtils)
  {
    parent::__construct($settings, $cartUtils);
  }
}