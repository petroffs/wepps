<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Template\TemplateUtilsWepps;

class DeliveryWepps
{
    /**
     * Тип доставки
     * 1 - Доставка в ПВЗ
     * 2 - Доставка до двери
     * @var int
     */
    private $type = 1;
    private $settings;
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }
    public function getTariff(CartUtilsWepps $cartUtils)
    {
        $cartSummary = $cartUtils->getCartSummary();
        if (empty($cartSummary)) {
            return [];
        }
        $output = [
            'status'=>200,
            'title'=> $this->settings['Name'],
            'text'=> 'Тариф способа доставки',
            'price' => $this->settings['Tariff'],
            'period' => '1-3'
        ];
        if (@$this->settings['IsTariffPercentage']==1) {
            $output['price'] = TemplateUtilsWepps::round($this->settings['Tariff'] * $cartSummary['sumActive'] / 100,0,'str');
        }
        return $output;
    }
    public function getDiscount(CartUtilsWepps $cartUtils) : array
    {
        $cartSummary = $cartUtils->getCartSummary();
        if (empty($cartSummary)) {
            return [];
        }
        $output = [
            'status'=>($this->settings['Discount']>0)?200:0,
            'title'=> $this->settings['Name'],
            'text'=> "Скидка за выбранный способ доставки",
            'price' => $this->settings['Discount'],
        ];
        if (@$this->settings['IsDiscountPercentage']==1) {
            $output['price'] = TemplateUtilsWepps::round($this->settings['Discount'] * $cartSummary['sumActive'] / 100,0,'str');
        }
        return $output;
    }
    public function getOperations() {
        return ['template','data for template','fetch ?'];
    }
    public function processOperations() {
        return ['template','data for template','fetch ?'];
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