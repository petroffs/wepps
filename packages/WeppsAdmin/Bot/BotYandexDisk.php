<?
namespace WeppsAdmin\Bot;

use WeppsCore\Connect\ConnectWepps;

class YandexDiskWepps {
	public $parent = 0;
	
	public function __construct($settings=[]) {
		
	}
	public function backupYandexDisk($action) {
		$backupBase = "{$this->root}/control/config/add/backup/db/";
		$backupFiles = "{$this->root}/control/config/add/backup/files/";
		$backupBaseFile = " | gzip > ".$backupBase."pps_db_dump_".date("Ymd-His").".sql.gz";
		$backupFilesFile = "pps_files_".date("Ymd-His")."_{$this->host}.tar.gz";
		
		$yadisk_email='[login] @ yandex.ru';
		$yadisk_pass='[pass]';
		$yadisc_dir='/Work/pps/';
		
		switch ($action) {
			case "add" :
				$scandir = scandir($backupBase);
				foreach ($scandir as $value) if (strstr($value,".sql")) unlink($backupBase.$value);
				$scandir = scandir($backupFiles);
				foreach ($scandir as $value) if (strstr($value,".tar.gz")) unlink($backupFiles.$value);
				$tmp="mysqldump --defaults-extra-file=".ConnectWepps::$projectDB['cnf']." -K --default-character-set=utf8 --add-drop-table ".ConnectWepps::$projectDB['dbname']."{$backupBaseFile}";
				exec($tmp);
				$tmp = "tar cfz ".$backupFiles.$backupFilesFile." -P --exclude={$this->root}/pic/* --exclude={$this->root}/control/config/add/backup/files/* {$this->root}/";
				//$tmp = "tar cfz ".$backupFiles.$backupFilesFile." -P --exclude={$this->path}{$this->host}/pic/* --exclude={$this->path}{$this->host}/control/config/add/backup/files/* {$this->path}{$this->host}/packages/";
				exec($tmp);
				break;
			case "send" :
				$arr = scandir($backupFiles);
				$filebackup = $backupFiles.array_pop($arr);
				$str = "curl --user $yadisk_email:$yadisk_pass -T $filebackup https://webdav.yandex.ru$yadisc_dir";
				exec ($str);
				/*
				 * Удаление бекапов
				 */
				$scandir = scandir($backupBase);
				foreach ($scandir as $value) if (strstr($value,".sql")) unlink($backupBase.$value);
				$scandir = scandir($backupFiles);
				foreach ($scandir as $value) if (strstr($value,".tar.gz")) unlink($backupFiles.$value);
				//exit();
				break;
			case "rotation" :
				include_once ("{$this->root}/packages/vendor_local/disk-yandex/class.php");
				$disk = new \yandex_disk($yadisk_email , $yadisk_pass);
				if ($ls = $disk->ls($yadisc_dir)) {
					unset($ls[0]);
					$lsco = count($ls);
					for ($i=1;$i<=$lsco;$i++) {
						if ($i<=$lsco-10) {
							$disk = new \yandex_disk($yadisk_email , $yadisk_pass);
							$disk->delete($ls[$i]);
						}
					}
				}
				break;
			default:
				break;
		}
	}
}
?>