<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect;
use WeppsCore\Cli;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\Cdek\Cdek;
use WeppsExtensions\Cart\Delivery\DeliveryUtils;
use WeppsExtensions\Cart\Payments\PaymentsUtils;

class Bot {
	public $parent = 1;
	protected $host;
	protected $root;
	protected $cli;

	public function __construct($settings=[]) {
		$this->host = Connect::$projectDev['host'];
		$this->root = Connect::$projectDev['root'];
		$this->cli = new Cli();
		$start = microtime(true);
		$action = (!isset($settings[1])) ? "" : $settings[1];
		if ($this->parent==0) {
			return;
		}
		switch ($action) {
			/*
			 * data operations
			 */
			case 'tasks':
				$obj = new BotSystem();
				$obj->tasks();
				break;
			case 'feeds':
				$obj = new BotFeeds();
				$obj->setSitemap();
				break;
			case 'cdek':
				$obj = new Cdek([],new CartUtils());
				$obj->setPoints();
				$obj->setCities();
				$obj->setRegions();
				break;
			/*
			 * tests
			 */
			case 'hashes':
				$obj = new BotTest();
				$obj->setHashes();
				break;
			case 'telegramtest':
				$obj = new BotTest();
				$obj->telegram();
				break;
			case 'mailtest':
				$obj = new BotTest();
				$obj->mail();
				break;
			case 'dbtest':
				$obj = new BotTest();
				$obj->testDB();
				break;
			case 'clitest':
				$obj = new BotTest();
				$obj->cli();
				break;
			case 'passtest':
				$obj = new BotTest();
				$obj->password();
				break;
			case 'deliverytariffs':
				$obj = new DeliveryUtils();
				$cartUtils = new CartUtils();
				#$obj->getDeliveryTariffsByCitiesId(137);
				$obj->getTariffsByCitiesId("20",$cartUtils);
				break;
			case 'paymentstariffs':
				$obj = new PaymentsUtils();
				$cartUtils = new CartUtils();
				#$obj->getDeliveryTariffsByCitiesId(137);
				$obj->getByDeliveryId("6", $cartUtils);
				break;
			case 'ordertext':
				$obj = new BotTest();
				$obj->testOrderText();
				break;
			default:
				echo "\nERROR\n";
				exit();
		}
		$start = microtime(true)-$start;
		echo "\n$action - OK [$start sec.]\n";
	}
}