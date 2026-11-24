<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils;
use WeppsExtensions\Cart\CartUtils;

class Delivery
{
    protected $settings;
    protected $cartUtils;
    protected $deliveryUtils;
    public function __construct(array $settings, CartUtils $cartUtils)
    {
        $this->settings = $settings;
        $this->cartUtils = $cartUtils;
        $this->deliveryUtils = new DeliveryUtils();
    }
    public function getTariff(): array
    {
        $cartSummary = $this->cartUtils->getCartSummary();
        if (empty($cartSummary)) {
            return [];
        }
        $price = (float) $this->settings['Tariff'];
        if ($this->settings['FreeLevel'] > 0 && $this->settings['FreeLevel'] <= $cartSummary['sumActive']) {
            $price = 0;
        }
        $output = [
            'status' => 200,
            'title' => $this->settings['Name'],
            'text' => 'Тариф способа доставки',
            'price' => $price,
            'period' => '1-3'
        ];
        if (@$this->settings['IsTariffPercentage'] == 1) {
            $output['price'] = Utils::round($this->settings['Tariff'] * $cartSummary['sumActive'] / 100, 0);
        }
        return $output;
    }
    public function getDiscount(CartUtils $cartUtils): array
    {
        $cartSummary = $cartUtils->getCartSummary();
        if ($cartSummary === []) {
            return [];
        }
        $output = [
            'status' => ($this->settings['Discount'] > 0) ? 200 : 0,
            'title' => $this->settings['Name'],
            'text' => "Скидка за выбранный способ доставки",
        ];
        switch (@$this->settings['IsDiscountPercentage']) {
            case 1:
                $output['price'] = $cartUtils->getCartPercentage((float)$this->settings['Discount']);
                break;
            default:
                $output['price'] = Utils::round($this->settings['Discount']);
                break;
        }
        return $output;
    }
    public function getOperations()
    {
        $tpl = 'OperationsNotice.tpl';
        $data = [
            'text' => $this->settings['Descr']
        ];
        $allowBtn = true;
        return [
            'title' => $this->settings['Name'],
            'ext' => $this->settings['DeliveryExt'],
            'tpl' => $tpl,
            'data' => $data,
            'active' => @$this->cartUtils->getCart()['deliveryOperations'],
            'allowOrderBtn' => $allowBtn
        ];
    }
    public function getOperationsActive(array $cart)
    {
        $operations = [];
		$key = array_search($cart['deliveryId'], array_column(($cart['deliveryOperations'] ?? []), 'id'));
        if ($key !== false) {
			$operations = $cart['deliveryOperations'][$key]['data'];
        }
        return $operations;
    }
    public function getErrors(array $get): array
    {
        return [];
	}
}
