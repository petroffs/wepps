<?php
namespace WeppsAdmin\Bot;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\DataWepps;
use Curl\Curl;
use WeppsExtensions\Addons\RemoteServices\DadataWepps;
use WeppsAdmin\Lists\ListsWepps;

class BotWepps {
	public $parent = 1;
	protected $host;
	protected $root;

	public function __construct($myPost=[]) {
		$this->host = ConnectWepps::$projectDev['host'];
		$this->root = ConnectWepps::$projectDev['root'];
		$action = (!isset($myPost[1])) ? "" : $myPost[1];
		if ($this->parent==1) {
			switch ($action) {
				case "sitemap":
					$obj = new BotSitemapWepps();
					$obj->setSitemap();
					break;
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
				case "filescleaner":
					$obj = new BotHashesWepps();
					$obj->removeFiles();
					break;
				case "yandexmarket":
			
					break;
				case "setBackupYandex":
					$obj->backupYandexDisk("add");
					$obj->backupYandexDisk("send");
					$obj->backupYandexDisk("rotation");
					break;
				case "hashes":
					$obj = new BotHashesWepps();
					$obj->setHashes();
					break;
				case "telegram":
					$obj = new BotTelegramWepps();
					#$obj->test2();
					$obj->attach();
					break;
				case "dbtest":
					$t = ListsWepps::setListItem(
						"DataTbls", 
						55, 
						[ 
							'pps_path'=>'list',
							
							
							'GUID' => "",
							'BTest' => 'test text'
						]
					);
					UtilsWepps::debugf($t,1);
					break;
					
					$obj = new DataWepps("DataTbls");
					$t = $obj->set(55,[
							'GUID'=>"",
							'BTest'=>'test text',
					]);
					UtilsWepps::debugf($t,1);
					break;
				default:
					echo "\nERROR\n";
					exit();
					break;
			}
			echo "\n$action - OK\n";
		}
	}
}
?>