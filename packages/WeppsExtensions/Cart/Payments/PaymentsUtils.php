<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\CliWepps;
use WeppsCore\Utils\UtilsWepps;

class PaymentsUtilsWepps
{
    public function __construct()
    {

    }
    public function getPaymentsByDeliveryId(string $deliveryId): array
    {
        $sql = "select p.Id,p.Name,p.Priority,p.DisplayOff from OrdersPayments p
                join s_SearchKeys sk on sk.Name = p.Id and sk.Field3 = 'List::OrdersPayments::Delivery'
                join OrdersDelivery d on d.Id = sk.Field1
                where p.DisplayOff = 0 and d.Id = ?
                group by p.Id
                order by p.Priority";
        return ConnectWepps::$instance->fetch($sql, [$deliveryId]);
    }
}