<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class PaymentsDefaultWepps extends PaymentsWepps
{
	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings,$cartUtils);
	}
	public function getOrderComplete()
	{
		UtilsWepps::debug(1, 1);
	}
}