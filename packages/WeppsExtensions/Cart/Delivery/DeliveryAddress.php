<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryAddressWepps extends DeliveryWepps
{
  public function __construct(array $settings, CartUtilsWepps $cartUtils)
  {
    parent::__construct($settings, $cartUtils);
  }
  public function getOperations(): array
  {
    $headers = $this->cartUtils->getHeaders();
    $jdata = json_decode($this->settings['JSettings'], true);
    $tpl = 'OperationsNotice.tpl';
    $data = [];
    $allowBtn = false;
    $cart = $this->cartUtils->getCart();
    $citiesById = $this->deliveryUtils->getCitiesById($cart['citiesId']);
    $headers->js("/ext/Cart/Delivery/OperationsAddress.{$headers::$rand}.js");
    $headers->css("/ext/Cart/Delivery/OperationsAddress.{$headers::$rand}.css");
    $headers->css("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/css/suggestions.min.css");
    $headers->js("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/js/jquery.suggestions.min.js");
    $tpl = 'OperationsAddress.tpl';
    $data = [
      'deliveryCtiy' => $citiesById[0],
      'token' => ConnectWepps::$projectServices['dadata']['token']
    ];
    $allowBtn = true;
    return [
      'title' => $this->settings['Name'],
      'ext' => $this->settings['DeliveryExt'],
      'tpl' => $tpl,
      'data' => $data,
      'active' => self::getOperationsActive($cart),
      'allowOrderBtn' => $allowBtn
    ];
  }
}