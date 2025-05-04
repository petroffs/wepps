<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Template\TemplateUtilsWepps;

class PaymentsWepps
{
    private $settings;
    public function __construct(array $settings=[])
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
            'status'=>($this->settings['Tariff']>0)?200:0,
            'title'=> $this->settings['Name'],
            'text'=> 'Наценка за выбранный способ оплаты',
            'price' => $this->settings['Tariff'],
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
            'text'=> "Скидка за выбранный способ оплаты",
            'price' => $this->settings['Discount'],
        ];
        if (@$this->settings['IsDiscountPercentage']==1) {
            $output['price'] = TemplateUtilsWepps::round($this->settings['Discount'] * $cartSummary['sumActive'] / 100,0,'str');
        }
        return $output;
    }
}