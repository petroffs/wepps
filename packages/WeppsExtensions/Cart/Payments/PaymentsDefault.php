<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect\ConnectWepps;

class PaymentsDefaultWepps extends PaymentsWepps
{
    public function __construct(array $settings=[])
    {
        parent::__construct($settings);
    }

}