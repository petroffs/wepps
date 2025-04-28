<?php
namespace WeppsExtensions\Addons\Bot;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Utils\CliWepps;
use WeppsExtensions\Cart\Delivery\DeliveryCdekWepps;
use WeppsExtensions\Cart\Delivery\DeliveryUtilsWepps;

class BotWepps {
	public $parent = 1;
	protected $host;
	protected $root;
	protected $cli;

	public function __construct($myPost=[]) {
		$this->host = ConnectWepps::$projectDev['host'];
		$this->root = ConnectWepps::$projectDev['root'];
		$this->cli = new CliWepps();
		$start = microtime(true);
		$action = (!isset($myPost[1])) ? "" : $myPost[1];
		if ($this->parent==0) {
			return;
		}
		switch ($action) {
			/*
			 * data operations
			 */
			case "feeds":
				$obj = new BotFeedsWepps();
				$obj->setSitemap();
				break;
			case 'cdek':
				$obj = new DeliveryCdekWepps([]);
				$obj->setPoints();
				$obj->setCities();
				$obj->setRegions();
				break;
			/*
			 * tests
			 */
			case 'hashes':
				$obj = new BotTestWepps();
				$obj->setHashes();
				break;
			case 'telegramtest':
				$obj = new BotTestWepps();
				$obj->telegram();
				break;
			case 'mailtest':
				$obj = new BotTestWepps();
				$obj->mail();
				break;
			case 'dbtest':
				$obj = new BotTestWepps();
				$obj->testDB();
				break;
			case 'clitest':
				$obj = new BotTestWepps();
				$obj->cli();
				break;
			case 'passtest':
				$obj = new BotTestWepps();
				$obj->password();
				break;
			case 'deliverytariffs':
				$obj = new DeliveryUtilsWepps();
				#$obj->getDeliveryTariffsByCitiesId(137);
				$obj->getDeliveryTariffsByCitiesId(394);
				break;
			default:
				echo "\nERROR\n";
				exit();
		}
		$start = microtime(true)-$start;
		echo "\n$action - OK [$start sec.]\n";
	}
}
?>