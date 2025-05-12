<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\CliWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class PaymentsUtilsWepps
{
    public function __construct()
    {

    }
    
    public function getByDeliveryId(string $deliveryId,CartUtilsWepps $cartUtils,string $paymentsId=''): array
    {
        $conditions = "p.DisplayOff=0 and p.Id != ?";
        if (!empty($paymentsId)) {
            $conditions = "p.DisplayOff=0 and p.Id = ?";
        }
        $sql = "select p.Id,p.Name,p.Priority,p.DisplayOff,
                p.Tariff,p.IsTariffPercentage,p.Discount,p.IsDiscountPercentage,
                if (p.PaymentsExt!='',p.PaymentsExt,'PaymentsDefaultWepps') PaymentsExt
                from OrdersPayments p
                join s_SearchKeys sk on sk.Name = p.Id and sk.Field3 = 'List::OrdersPayments::Delivery'
                join OrdersDelivery d on d.Id = sk.Field1
                where $conditions and d.Id=?
                group by p.Id
                order by p.Priority";
        //UtilsWepps::debug($paymentsId);
        $res = ConnectWepps::$instance->fetch($sql, [$paymentsId,$deliveryId]);
        foreach ($res as $key => $value) {
            $className = "\WeppsExtensions\\Cart\\Payments\\{$value['PaymentsExt']}";
		    $class = new $className($value);
            $res[$key]['Addons']['tariff'] = $class->getTariff($cartUtils);
            $res[$key]['Addons']['discount'] = $class->getDiscount($cartUtils);
        }
        return $res;
    }
}