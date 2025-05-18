<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryUtilsWepps
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
        $sql = "select c.Id,r.Id RegionsId,c.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where concat(c.Name,', ',r.Name) like ? limit $limit,$onpage";
        return ConnectWepps::$instance->fetch($sql, ["{$term}%"]);
    }
    public function getCitiesById(int $id, int $page = 1, int $onpage = 12): array
    {
        $page = max(1, (int) ($page) ?? 1);
        $limit = ($page - 1) * $onpage;
        $sql = "select c.Id,r.Id RegionsId,c.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where c.Id = ? limit $limit,$onpage";
        return ConnectWepps::$instance->fetch($sql, [$id]);
    }
    public function getTariffsByCitiesId(string $citiesId, CartUtilsWepps $cartUtils, string $deliveryId = ''): array
    {
        $conditions = "d.DisplayOff=0 and d.Id != ?";
        if (!empty($deliveryId)) {
            $conditions = "d.DisplayOff=0 and d.Id = ?";
        }
        $sql = "select d.Id,d.Name,d.Descr,d.DeliveryExt,d.IncludeCitiesId,d.ExcludeCitiesId,
                d.IncludeRegionsId,d.ExcludeRegionsId,d.Tariff,d.IsTariffPercentage,d.Discount,d.IsDiscountPercentage,d.JSettings,
                if (d.DeliveryExt!='',d.DeliveryExt,'DeliveryDefaultWepps') DeliveryExt,
                c.Id CitiesId,r.Id RegionsId,c.Name CitiesName,r.Name RegionsName
                from OrdersDelivery d 
                left join CitiesCdek c on c.Id=?
                left join RegionsCdek r on r.Id = c.RegionsId
                where $conditions group by d.Id";
        $res = ConnectWepps::$instance->fetch($sql, [$citiesId, $deliveryId]);
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
            if ($w == 1) {
                $output[] = $value;
            }
        }
        $cartSummary = $cartUtils->getCartSummary();
        foreach ($output as $key => $value) {
            $className = "\WeppsExtensions\\Cart\\Delivery\\{$value['DeliveryExt']}";
            /**
             * @var DeliveryWepps $class
             */
            $class = new $className($value,$cartUtils);
            $output[$key]['Addons']['tariff'] = $class->getTariff();
            $output[$key]['Addons']['discount'] = $class->getDiscount($cartUtils);
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
    private function _match($digit, $field)
    {
        $ex = explode(',', $field);
        $ex = array_map('trim', $ex);
        return in_array($digit, $ex);
    }
}