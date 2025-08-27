<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect;
use WeppsCore\Logs;
use WeppsCore\Memcached;
use WeppsCore\Utils;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Payments\Yookassa\Yookassa;
use WeppsExtensions\Profile\ProfileUtils;

class BotSystem extends Bot {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function tasks() {
		$sql = "select * from s_LocalServicesLog where InProgress in (1,0) and IsProcessed=0 order by InProgress desc,Id limit 50";
		$res = Connect::$instance->fetch($sql);
		if (empty($res) || $res[0]['InProgress']==1) {
			return;
		}
		$ids = array_column($res,'Id');
		new Memcached('no');
		$logs = new Logs();
		$cartUtils = new CartUtils();
		$yookassa = new Yookassa([],$cartUtils);
		$profileUtils = new ProfileUtils([]);
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
				case 'password':
					$profileUtils->processPasswordLog($value,$logs);
					break;
				default:
					$logs->update($value['Id'],['message'=>'task fail'],404);
					break;
			}
		}
		/*
		 * Реализовано в $logs->update
		 */
		#$in = Connect::$instance->in($ids);
		#$sql = "update s_LocalServicesLog set InProgress=1,IsProcessed=1 where Id in ($in)";
		#Connect::$instance->query($sql,$ids);
	}
}