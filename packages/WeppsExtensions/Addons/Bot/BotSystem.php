<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\LogsWepps;
use WeppsCore\Utils\MemcachedWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\Yookassa\YookassaWepps;

class BotSystemWepps extends BotWepps {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function tasks() {
		$sql = "select * from s_LocalServicesLog where InProgress in (1,0) and IsProcessed=0 order by InProgress desc,Id limit 1";
		$res = ConnectWepps::$instance->fetch($sql);
		if (empty($res) || $res[0]['InProgress']==1) {
			return;
		}
		$ids = array_column($res,'Id');
		new MemcachedWepps('no');
		$logs = new LogsWepps();
		$cartUtils = new CartUtilsWepps();
		$yookassa = new YookassaWepps([],$cartUtils);
		foreach ($res as $value) {
			switch($value['Name']) {
				case 'order-new':
					$cartUtils->processLog($value, $logs);
					break;
				case 'order-payment':
					$cartUtils->processPaymentLog($value, $logs);
					break;
				case 'yookassa':
					$yookassa->processLog($value,$logs);
					break;
				default:
					$logs->update($value['Id'],['message'=>'task fail'],404);
					break;
			}
		}
		/*
		 * Реализовано в $logs->update
		 */
		#$in = ConnectWepps::$instance->in($ids);
		#$sql = "update s_LocalServicesLog set InProgress=1,IsProcessed=1 where Id in ($in)";
		#ConnectWepps::$instance->query($sql,$ids);
	}
}