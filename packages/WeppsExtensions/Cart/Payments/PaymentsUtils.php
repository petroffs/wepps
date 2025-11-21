<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect;
use WeppsExtensions\Cart\CartUtils;

class PaymentsUtils
{
    public function __construct()
    {

    }
    
    public function getByDeliveryId(string $deliveryId,CartUtils $cartUtils,string $paymentsId=''): array
    {
        $conditions = "p.IsHidden=0 and p.Id != ?";
        if (!empty($paymentsId)) {
            $conditions = "p.IsHidden=0 and p.Id = ?";
        }
        $sql = "SELECT p.Id,p.Name,p.Priority,p.IsHidden,
                p.Tariff,p.IsTariffPercentage,p.Discount,p.IsDiscountPercentage,
                if (p.PaymentsExt!='',p.PaymentsExt,'PaymentsDefault') PaymentsExt
                from OrdersPayments p
                join s_SearchKeys sk on sk.Name = p.Id and sk.Field3 = 'List::OrdersPayments::Delivery'
                join OrdersDelivery d on d.Id = sk.Field1
                where $conditions and d.Id=?
                group by p.Id
                order by p.Priority";
        $res = Connect::$instance->fetch($sql, [$paymentsId,$deliveryId]);
        foreach ($res as $key => $value) {
            $className = "\WeppsExtensions\\Cart\\Payments\\{$value['PaymentsExt']}\\{$value['PaymentsExt']}";
            /**
             * @var \WeppsExtensions\Cart\Payments\Payments $class
             */
		    $class = new $className($value,$cartUtils);
            $res[$key]['Addons']['tariff'] = $class->getTariff($cartUtils);
            $res[$key]['Addons']['discount'] = $class->getDiscount($cartUtils);
            $res[$key]['Addons']['extension'] = $value['PaymentsExt'];
        }
        return $res;
    }
}