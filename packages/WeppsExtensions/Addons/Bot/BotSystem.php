<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect;
use WeppsCore\Tasks;
use WeppsCore\Memcached;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Payments\Yookassa\Yookassa;
use WeppsExtensions\Profile\ProfileUtils;

class BotSystem extends Bot {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function tasks() {
		$sql = "select * from s_Tasks where InProgress in (1,0) and IsProcessed=0 order by InProgress desc,Id limit 50";
		$res = Connect::$instance->fetch($sql);
		if (empty($res) || $res[0]['InProgress']==1) {
			return;
		}
		$ids = array_column($res,'Id');
		new Memcached('no');
		$tasks = new Tasks();
		$cartUtils = new CartUtils();
		$yookassa = new Yookassa([],$cartUtils);
		$profileUtils = new ProfileUtils([]);
		foreach ($res as $value) {
			switch($value['Name']) {
				case 'order-new':
					$cartUtils->processTask($value, $tasks);
					break;
				case 'order-payment':
					$cartUtils->processPaymentTask($value, $tasks);
					break;
				case 'yookassa':
					$yookassa->processTask($value,$tasks);
					break;
				case 'password':
					$profileUtils->processPasswordTask($value,$tasks);
					break;
				case 'password-confirm':
					$profileUtils->processPasswordConfirmTask($value,$tasks);
					break;
				default:
					$tasks->update($value['Id'],['message'=>'task fail'],404);
					break;
			}
		}
		/*
		 * Реализовано в $tasks->update
		 */
		#$in = Connect::$instance->in($ids);
		#$sql = "update s_Tasks set InProgress=1,IsProcessed=1 where Id in ($in)";
		#Connect::$instance->query($sql,$ids);
	}
}