<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\UtilsWepps;

class DeliveryUtilsWepps
{
    public function __construct()
    {
    }
    public function getCitiesByQuery(string $term = '',int $page = 1,int $onpage = 12): array
    {
        $page = max(1, (int) ($page) ?? 1);
        $limit = ($page - 1) * $onpage;
        $term = urldecode($term);
        $sql = "select c.Id,r.Id RegionsId,c.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where concat(c.Name,', ',r.Name) like ? limit $limit,$onpage";
        $res = ConnectWepps::$instance->fetch($sql, ["{$term}%"]);
        return $res;
    }
    public function getCitiesById(int $id,int $page = 1,int $onpage = 12): array
    {
        $page = max(1, (int) ($page) ?? 1);
        $limit = ($page - 1) * $onpage;
        $sql = "select c.Id,r.Id RegionsId,c.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where c.Id = ? limit $limit,$onpage";
        $res = ConnectWepps::$instance->fetch($sql, [$id]);
        return $res;
    }
    public function getDeliveryTariffsByCitiesId(int $cityId) : array
    {
        $cities = $this->getCitiesById($cityId);
        if (empty($cities)) {
            return [];
        }

        UtilsWepps::debug($cities,21);

        $cityName = $this->getCitiesByQuery(""); 

        $conditions = "t.DisplayOff=0 and Region = '' or Region like '%{$this->get['city']}%') and RegionExcl not like ('%{$this->get['city']}%') $cond";
        $obj = new DataWepps("OrdersDelivery");


				$res = $obj->getMax("t.DisplayOff=0 ");
				#$cartUtils->
				#$this->tpl = 'RequestDeliveryEmpty.tpl';	
				// $obj = new DataWepps("TradeDeliveryVars");
				// $res = $obj->getMax("t.DisplayOff=0 and (Region = '' or Region like '%{$this->get['city']}%') and RegionExcl not like ('%{$this->get['city']}%') $cond",30,1,'t.Priority,t.Name');
				// $this->assign('delivery', $res);
        UtilsWepps::debug($cityId,21);
        return [];
    }
}