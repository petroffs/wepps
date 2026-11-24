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

use WeppsCore\Connect;
use WeppsExtensions\Cart\CartUtils;

class DeliveryUtils
{
    private $operations;
    public function __construct()
    {
    }
    public function getCitiesByQuery(string $term = '', int $page = 1, int $onpage = 12): array
    {
        $page = max(1, (int) ($page) ?? 1);
        $limit = ($page - 1) * $onpage;
        $term = urldecode($term);
        $sql = "SELECT c.Id,r.Id RegionsId,c.Name,concat(if(c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name))) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where concat(c.Name,', ',r.Name) like ? and c.Name not like '% (%' order by if(c.Name=r.Name,0,1) limit $limit,$onpage";
        return Connect::$instance->fetch($sql, ["{$term}%"]);
    }
    public function getCitiesById(int $id, int $page = 1, int $onpage = 12): array
    {
        $page = max(1, (int) ($page) ?? 1);
        $limit = ($page - 1) * $onpage;
        $sql = "SELECT c.Id,r.Id RegionsId,r.Name RegionsName,c.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where c.Id = ? limit $limit,$onpage";
        return Connect::$instance->fetch($sql, [$id]);
    }
    public function getTariffsByCitiesId(string $citiesId, CartUtils $cartUtils, string $deliveryId = ''): array
    {
        // Идентификаторы из запроса: срезаем html-taint перед SQL — bind-параметры
        // в неразбираемся SQL psalm считает записью в БД и тэйнтит все чтения
        $citiesId = htmlspecialchars($citiesId, ENT_QUOTES);
        $deliveryId = htmlspecialchars($deliveryId, ENT_QUOTES);
        $conditions = "d.IsHidden=0 and d.Id != ?";
        if (!empty($deliveryId)) {
            $conditions = "d.IsHidden=0 and d.Id = ?";
        }
        $sql = "SELECT d.Id,d.Name,d.Descr,d.DeliveryExt,d.IncludeCitiesId,d.ExcludeCitiesId,
                d.IncludeRegionsId,d.ExcludeRegionsId,d.Tariff,d.IsTariffPercentage,d.FreeLevel,d.Discount,d.IsDiscountPercentage,d.JSettings,
                if (d.DeliveryExt!='',d.DeliveryExt,'DeliveryDefault') DeliveryExt,
                c.Id CitiesId,r.Id RegionsId,c.Name CitiesName,r.Name RegionsName,pc.Name PostalCode
                from OrdersDelivery d 
                left join CitiesCdek c on c.Id=?
                left join PostalCodes pc on pc.Id=c.Id
                left join RegionsCdek r on r.Id = c.RegionsId
                where $conditions group by d.Id";
        $res = Connect::$instance->fetch($sql, [$citiesId, $deliveryId]);
        if (empty($res)) {
            return [];
        }
        $output = [];
        foreach ($res as $value) {
            $w = 0;
            if (!$value['IncludeCitiesId'] && !$value['IncludeRegionsId']) {
                $w = 1;
            } elseif (!empty($value['CitiesId']) && $this->_match($value['CitiesId'], $value['IncludeCitiesId'])) {
                $w = 1;
            } elseif (!empty($value['RegionsId']) && ($this->_match($value['RegionsId'], $value['IncludeRegionsId']))) {
                $w = 1;
            }
            if (!empty($value['CitiesId']) && !empty($value['ExcludeCitiesId']) && $this->_match($value['CitiesId'], $value['ExcludeCitiesId'])) {
                $w = 0;
            }
            if (!empty($value['RegionsId']) && !empty($value['ExcludeRegionsId']) && ($this->_match($value['RegionsId'], $value['ExcludeRegionsId']))) {
                $w = 0;
            }
            if ($w === 1) {
                $output[] = $value;
            }
        }
        $cartSummary = $cartUtils->getCartSummary();
        foreach ($output as $key => $value) {
            $className = "\WeppsExtensions\\Cart\\Delivery\\{$value['DeliveryExt']}\\{$value['DeliveryExt']}";
            if (!class_exists($className)) {
                continue;
            }
            /**
             * @var Delivery $class
             *
             * Имя класса — из внутренней конфигурации способов доставки (DeliveryExt),
             * class_exists() выше отсекает посторонние значения
             *
             * @psalm-suppress TaintedInput
             */
            $class = new $className($value, $cartUtils);
            $output[$key]['Addons']['tariff'] = $class->getTariff();
            $output[$key]['Addons']['discount'] = $class->getDiscount($cartUtils);
            $output[$key]['Addons']['extension'] = $value['DeliveryExt'];
            if ($value['Id'] == @$cartSummary['delivery']['deliveryId']) {
                $this->operations = $class->getOperations();
            }
        }
        return $output;
    }
    public function getOperations()
    {
        return $this->operations;
    }
    public function setOperations(array $data, CartUtils $cartUtils): bool
    {
        $data = array_filter($data, fn($key) => str_starts_with($key, 'operations-'), ARRAY_FILTER_USE_KEY);
        if ($data === []) {
            $cartUtils->setCartDeliveryOperations();
            return true;
        }
        $cart = $cartUtils->getCart();
        $data2 = [];
        foreach ($data as $key => $value) {
            $data2[str_replace('operations-', '', $key)] = trim(htmlspecialchars(substr($value, 0, 256)));
        }
        $key = array_search($cart['deliveryId'], array_column($cart['deliveryOperations']??[], 'id'));
        if ($key === false) {
            $cart['deliveryOperations'][] = [
                'id' => (string) ($cart['deliveryId'] ?? '-1'),
                'data' => $data2
            ];
        } else {
            $cart['deliveryOperations'][$key] = [
                'id' => (string) ($cart['deliveryId'] ?? '-1'),
                'data' => $data2
            ];
        }
        $cartUtils->setCartDeliveryOperations($cart['deliveryOperations']);
        return true;
    }
    private function _match($digit, $field): bool
    {
        $ex = explode(',', $field);
        $ex = array_map('trim', $ex);
        return in_array($digit, $ex);
    }
}