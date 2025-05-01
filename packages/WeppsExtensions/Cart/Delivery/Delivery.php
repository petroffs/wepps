<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Template\TemplateUtilsWepps;

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
    private $settings;
    public function __construct(array $settings)
    {
        #$settings = json_decode($value['JSettings'],true) ?? [];
        $this->settings = $settings;
    }
    public function getTariff(CartUtilsWepps $cartUtils)
    {
        // if (empty($cartUtils->getCartSummary())) {
        //     $cartUtils->setCartSummary();
        // }
        $output = [
            'status'=>200,
            'title'=> $this->settings['Name'],
            'price' => $this->settings['Tariff'],
            'period' => '1-3'
        ];
        if (@$this->settings['IsTariffPercentage']==1) {
            $output['price'] = TemplateUtilsWepps::round($this->settings['Tariff'] * $cartUtils->getCartSummary()['sumActive'] / 100,2);
        }
        return $output;
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