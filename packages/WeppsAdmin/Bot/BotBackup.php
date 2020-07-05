<?
namespace WeppsAdmin\Bot;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class BotBackupWepps {
	
	private $root;
	private $host;
	private $backupPath;
	private $db;
	private $cnf;
	private $dateMask;
	
	public function __construct() {
		$this->root = ConnectWepps::$projectDev['root'];
		$this->host = ConnectWepps::$projectDev['host'];
		$this->backupPath = '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
		$this->db = ConnectWepps::$projectDB['dbname'];
		$this->cnf = ConnectWepps::$projectDB['cnf'];
		$this->dateMask = date("Ymd-His");
	}
	
	public function addBackupIgnoredByGit() {
		$content = file_get_contents( __DIR__ . "/../../../.gitignore");
		$files = explode("\n", $content);
		$backupFilename = "{$this->root}{$this->backupPath}{$this->host}-{$this->dateMask}-gitignored.7z";
		
		$this->addBackupDB();
		$this->removeBackup();
		
		$str = "";
		foreach ($files as $file) {
			if (substr($file, 0,1)=='/' && !strstr($file, "Backup/files")) {
				$str .= " \"{$this->root}{$file}\"";
			}
		}
		
		$filedb = "";
		$scandir = scandir($this->root.$this->backupPath);
		foreach ($scandir as $file) {
			if (strstr($file,".sql")) {
				$filedb = " \"{$this->root}{$this->backupPath}{$file}\"";
			}
		}
		// -tzip после аоа
		$str = "7z a -aoa -spf2 {$backupFilename} {$str} {$filedb}";
		$str = str_replace("/*", "/", $str);
		exec($str);
		$this->removeBackupDB();
		return 1;
	}
	
	public function addBackup() {
		$backupFilename = "{$this->root}{$this->backupPath}{$this->host}-{$this->dateMask}.7z";
		$this->addBackupDB();
		$this->removeBackup();
		$exclude = "-xr!.git -xr!pic";
		$cmd = "7z a -aoa -spf2 {$backupFilename} {$this->root} $exclude > {$this->root}/debug.conf";
		exec($cmd);
		$this->removeBackupDB();
		return 1;
	}
	
	public function removeBackup() {
		$scandir = scandir($this->root.$this->backupPath);
		foreach ($scandir as $file) {
			if (strstr($file,".7z")) {
				unlink("{$this->root}{$this->backupPath}{$file}");
			}
		}
		return 1;
	}
	
	public function addBackupDB($removeSqlFiles=true) {
		
		if ($removeSqlFiles==true) {
			$this->removeBackupDB();
		}
		
		$backupFilenameDB = "{$this->root}{$this->backupPath}{$this->host}-{$this->dateMask}.sql";
		$str = "mysqldump --defaults-extra-file={$this->cnf} -K --default-character-set=utf8 --add-drop-table $this->db > $backupFilenameDB";
		exec($str);
		return 1;
	}
	
	public function removeBackupDB() {
		$scandir = scandir($this->root.$this->backupPath);
		foreach ($scandir as $file) {
			if (strstr($file,".sql")) {
				unlink("{$this->root}{$this->backupPath}{$file}");
			}
		}
		return 1;
	}
}

?>