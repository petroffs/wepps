<?php
namespace WeppsExtensions\Addons\Bot;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Lists\ListsWepps;

class BotWepps {
	public $parent = 1;
	protected $host;
	protected $root;

	public function __construct($myPost=[]) {
		$this->host = ConnectWepps::$projectDev['host'];
		$this->root = ConnectWepps::$projectDev['root'];
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
				
			/*
			 * services
			 */
			case "addBackupIgnored":
				$obj = new BotBackupWepps();
				$obj->addBackupIgnoredByGit();
				break;
			case "addBackup":
				$obj = new BotBackupWepps();
				$obj->addBackup();
				break;
			case "addBackupDB":
				$obj = new BotBackupWepps();
				$obj->addBackupDB(false);
				break;
			case "removeFiles":
				$obj = new BotSystemWepps();
				$obj->removeFiles();
				break;
			
			/*
			 * tests
			 */
			case "hashes":
				$obj = new BotTestWepps();
				$obj->setHashes();
				break;
			case "telegramtest":
				$obj = new BotTestWepps();
				$obj->telegram();
				break;
			case "mailtest":
				$obj = new BotTestWepps();
				$obj->mail();
				break;
			case "dbtest":
				$obj = new BotTestWepps();
				$obj->testDB();
				break;
			default:
				echo "\nERROR\n";
				exit();
				break;
		}
		$start = microtime(true)-$start;
		echo "\n$action - OK [$start sec.]\n";
	}
}
?>