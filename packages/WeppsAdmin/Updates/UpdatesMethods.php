<?php

namespace WeppsAdmin\Updates;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class UpdatesMethodsWepps extends UpdatesWepps {
	public $parent = 0;
	public $settings;
	private $filename;
	private $path;
	
	public function __construct($settings=[]) {
		parent::__construct();
		$this->filename = __DIR__.'/files/md5.conf';
	}
	public function getReleaseCurrentVersion() : string {
		if (!is_file($this->filename)) {
			return '';
		}
		$jdata = json_decode(file_get_contents($this->filename),true);
		return @$jdata['version'];
		return true;
	}
	public function getReleaseCurrentModified() : array {
		return $this->getDiff($this->filename,false);
	}
	public function getReleasesList() : array {
		$filename = @ConnectWepps::$projectServices['weppsupdates']['weppsurl']."/releases.json";
		if (empty($filename)) {
			return [
					'output' => 'Wrong settings'
			];
		}
		$arrContextOptions = [
				"ssl" => [
						"verify_peer" => false,
						"verify_peer_name" => false
				]
		];
		$json = @file_get_contents($filename,false,stream_context_create($arrContextOptions));
		if (empty($json)) {
			return [
					'output' => 'Wrong settings weppsurl'
			];
		}
		$current = $this->getReleaseCurrentVersion();
		$jdata = $jdata2 = json_decode($json,true);
		if (empty($jdata)) {
			return [
					'output' => 'Wrong settings url: '.$filename
			];
		}
		$index = 0;
		foreach ($jdata as $key=>$value) {
			if ($value == $current) {
				$jdata2[$key] = $value." (current)";
				$index = $key;
			}
		}
		$jdata = array_slice($jdata, 0,$index+1);
		$jdata2 = array_slice($jdata2, 0,$index+1);
		$output = implode ("\n",$jdata2);
		return [
				'releases'=>$jdata,
				'list'=>$jdata2,
				'output'=>$output,
				'version'=>$current
		];
	}
	public function setUpdates(string $tag) {
		$releases = $this->getReleasesList();
		if (!in_array($tag, $releases['releases']) || $releases['version']==$tag) {
			return [
					'output'=>"Wrong tag"
			];
		}
		$file = ConnectWepps::$projectServices['weppsupdates']['weppsfile'];
		$fileDst = __DIR__."/files/updates/$tag/$file";
		$fileSrc = @ConnectWepps::$projectServices['weppsupdates']['weppsurl']."/packages/PPSAdmin/Releases/files/$tag/$file";
		$curl = new Curl();
		$curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
		$response = $curl->get($fileSrc);
		if (!in_array('content-type: application/zip',array_map('strtolower', $response->response_headers))) {
			return [
					'output' => 'wrong update file\'s url : '.$fileSrc
			];
		}
		$this->cli->put($response->response, $fileDst);
		$this->path = pathinfo($fileDst);
		
		/*
		 * Распаковать в папку $tag
		 */
		$zip = new \ZipArchive();
		$result = $zip->open($fileDst);
		$zipPath = $this->path['dirname'].'/updates';
		if ($result === false) {
			return [
					'output' => 'Zip error'
			];
		}
		
		/*
		 * При отладке скрыть (после распаковки первого архива)
		 */
		$zip->extractTo($zipPath);
		$zip->close();

		$fileMD5 = $zipPath."/packages/WeppsAdmin/Updates/files/md5.conf";
		
		/*
		 * Измененные файлы релиза
		 */
		$modifiedSelf = $this->getReleaseCurrentModified();
		
		/*
		 * Это нужно перезаписать в текущий релиз
		 */
		$modifiedRelease = $this->getDiff($fileMD5,true);
		
		/*
		 * Расхождение между $modifiedSelf и $modifiedRelease
		 */
		$modified = explode("\n",$modifiedSelf['output']);
		$diff = array_diff(explode("\n",$modifiedRelease['output']),$modified);
		$diff = array_values($diff);
		
		$this->cli->br();
		$this->cli->warning("Disallowed files:\n".implode("\n", $modified));
		$this->cli->br();
		$this->cli->success("Allowed files:\n".implode("\n", $diff));
		
		$this->cli->put(json_encode([
				'disallowed'=>$modified,
				'allowed'=>$diff
		],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), $this->path['dirname']."/log.conf");
		
		/*
		 * БД
		 */
		$sql = $this->getTablesUpdates($fileMD5);
		
		$this->cli->br();
		$this->cli->warning("Type 'yes' to continue: ");
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'yes'){
			return [
					'output'=>"Aborting!"
			];
		}
		
		return self::setUpdatesInstall($diff,$sql);
	}
	private function setUpdatesInstall(array $diff = [], string $sql = '') {
		$diff[] = 'packages/WeppsAdmin/Updates/files/md5.conf';

		$pathRollback = $this->path['dirname'] . "/rollback";
		$pathDiff = $this->path['dirname'] . "/diff";
		
		foreach ($diff as $value) {
			if (empty($value)) {
				continue;
			}
			if (file_exists(ConnectWepps::$projectDev['root']."/".$value)) {
				$this->cli->copy(ConnectWepps::$projectDev['root']."/".$value, $pathRollback."/".$value);
			}
			if (file_exists($this->path['dirname']."/updates/".$value)) {
				$this->cli->copy($this->path['dirname']."/updates/".$value, $pathDiff."/".$value);
			}
		}
		$this->zip("{$this->path['dirname']}/wepps.platform-rollback.zip", "{$pathRollback}/*");
		$this->zip("{$this->path['dirname']}/wepps.platform-diff.zip", "{$pathDiff}/*");

		/*
		 * Реальный апдейт из updates
		 * После него только откат вернет файлы
		 */
		foreach ($diff as $value) {
			if (empty($value)) {
				continue;
			}
			$this->cli->copy($this->path['dirname']."/updates/".$value, ConnectWepps::$projectDev['root']."/".$value);
		}
		
		$this->cli->rmdir($pathRollback);
		$this->cli->rmdir($pathDiff);
		
		/*
		 * При отладке скрыть
		 */
		$this->cli->rmdir($this->path['dirname'] . "/updates");
		if (!empty($sql)) {
			ConnectWepps::$db->exec($sql);
		}
		return [
				'output' => 'Updates is complete succsessfull!'
		];
	}
	private function getFilesum($filename) {
		if (!file_exists($filename)) {
			return '';
		}
		$str = file_get_contents($filename);
		$str = str_replace("\r\n", "\n", $str);
		return md5($str);
	}
	private function getDiff(string $fileMD5,bool $includeRemoved=false) {
		if (!is_file($fileMD5)) {
			return [];
		}
		$files = [];
		$output = "\n";
		$jdata = json_decode(file_get_contents($fileMD5),true);
		if (empty($jdata['files'])) {
			return [];
		}
		foreach ($jdata['files'] as $value) {
			/*
			 * 1 - совпадает
			 * 2 - не совпадает
			 * 3 - нет файла
			 */
			$status = 3;
			$filename = ConnectWepps::$projectDev['root'].'/'.$value['file'];
			if (!is_file($filename)) {
				$files[] = [
						'file' => $value['file'],
						'md5' => "",
						'status'=> $status
				];
				if ($includeRemoved===true) {
					$output .= "{$value['file']}\n";
				}
				continue;
			}
			
			$md5sum = $this->getFilesum($filename);
			$status = ($md5sum == $value['md5']) ? 1 : 2;
			
			if ($status==1) {
				continue;
			}
			
			$files[] = [
					'file' => $value['file'],
					'md5' => $md5sum,
					'status'=> $status
			];
			$output .= "{$value['file']}\n";
		}
		$output = trim($output);
		return [
				'filename'=>$fileMD5,
				'output'=>$output,
				'files'=>$files,
		];
	}
	private function getTablesUpdates(string $fileMD5) : string {
		if (!is_file($fileMD5)) {
			return '';
		}
		$files = [];
		$jdata = json_decode(file_get_contents($this->filename),true);
		$jrelease = json_decode(file_get_contents($fileMD5),true);
		if (empty($jdata['db']) || empty($jrelease['db'])) {
			return '';
		}
		$files = $this->getTablesDiff($jrelease['db'], $jdata['db'])['diff'];
		if (empty($files)) {
			return '';
		}
		
		/*
		 * Данные текущих таблиц, сверяем с md5 текущей версии
		 * Затем, сравниваем md5 массивов jdata (текущая версия) и jcurrent (текущие реальные файлы бд).
		 * Если расхождение - то disallow
		 * Если схождение - находим расхождение с jrelease - и уже его в обновление
		 */
		$jcurrent = [];
		foreach ($files as $value) {
			$table = $this->getTablesStructure($value['table']);
			$value['md5']=$table['md5'];
			$jcurrent[] = $value;
		}
		
		/*
		 * allowed		$db['match']
		 * disallowed	$db['diff'];
		 */
		$db = $this->getTablesDiff($jcurrent,$jdata['db'],true);
		$allowed = array_column($db['match'], 'table');
		$disallowed = array_column($db['diff'], 'table');
		$columns = [
				'add' => [],
				'update' => []
		];
		if ($disallowed) {
			$this->cli->br();
			$this->cli->warning("Disallowed db-tables:\n".implode("\n", $disallowed));
		}
		if ($allowed) {
			$this->cli->br();
			$this->cli->success("Allowed db-tables:\n".implode("\n", $allowed));
		}
		$str = '';
		foreach ($files as $value) {
			if (!in_array($value['table'], $allowed)) {
				continue;
			}
			$table = $this->getTablesStructure($value['table']);
			$sql = "show columns from {$value['table']}";
			$res = ConnectWepps::$instance->fetch($sql);
			foreach ($res as $v) {
				$md5 = md5(json_encode($v,JSON_UNESCAPED_UNICODE));
				$col = @$value['columns'][$v['Field']];
				$alterNull = (@$col['column']['Null']=='NO')?'not null':'';
				$alterDefault = (!empty($col['column']['Default']))? "default '{$col['column']['Default']}'":'';
				$alterExtra = (!empty($col['column']['Extra']))? $col['column']['Extra'] : '';
				if (!empty($col)) {
					/*
					 * Обновить
					 */
					if ($md5!=$col['md5']) {
						$columns['update'][] = "{$value['table']}.{$col['column']['Field']}";
						$this->cli->success("[upd] {$value['table']}.{$col['column']['Field']}");
						$str .= "alter table {$value['table']} change {$col['column']['Field']} {$col['column']['Field']} {$col['column']['Type']} {$alterNull} {$alterDefault} {$alterExtra};\n";
					}
				} else {
					/*
					 * Добавить
					 */
					if (empty($col['column']['Field'])) {
						continue;
					}
					$columns['add'][] = "{$value['table']}.{$col['column']['Field']}";
					$str = "alter table {$value['table']} add {$col['column']['Field']} {$col['column']['Type']} {$alterNull} {$alterDefault} {$alterExtra};\n";
				}
			}
		}
		$this->cli->put(json_encode([
				'disallowed'=>$disallowed,
				'allowed'=>$allowed,
				'allowed-add'=>$columns['add'],
				'allowed-update'=>$columns['update'],
		],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), $this->path['dirname']."/log-db.conf");
		return $str;
	}
	private function zip(string $archive,string $path) : bool {
		if (file_exists($archive)) {
			$this->cli->rmfile($archive);
		}
		$cmd = "7z a -tzip {$archive} {$path}";
		$this->cli->cmd($cmd,true);
		return true;
	}
	private function command(string $cmd) : array {
		if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
			$cmd = str_replace("\\", "/", $cmd);
		}
		return $this->cli->cmd($cmd,true);
	}
	private function getTablesStructure(string $table) {
		/*
		 * Структура бд
		 */
		$sql = "SHOW CREATE TABLE $table";
		$res = ConnectWepps::$instance->fetch($sql);
		$str = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $res[0]['Create Table']).";\n\n";
		$md5 = self::getTablesMD5($str);
		
		/*
		 * Конфиг
		 */
		$sql = "select * from s_Config where TableName = '$table'";
		$res = ConnectWepps::$instance->fetch($sql);
		unset($res[0]['Id']);
		$arr = UtilsWepps::query($res[0]);
		$str .= "insert ignore into s_Config {$arr['insert']}\n\n";
		
		/*
		 * Конфиг полей
		 */
		$sql = "select * from s_ConfigFields where TableName = '$table'";
		$res = ConnectWepps::$instance->fetch($sql);
		foreach ($res as $value) {
			$arr = UtilsWepps::query($value);
			$str .= "insert ignore into s_ConfigFields {$arr['insert']}\n";
		}
		$str = preg_replace("/values \('(\d+)'/", 'values (null', $str);
		return [
				'md5' => $md5,
				'sql' => $str
		];
	}
	private function getTablesMD5(string $string) {
		$string = trim($string);
		$string = str_replace("\r\n", "\n", $string);
		$string = str_replace('COLLATE=', 'COLLATE ',$string);
		$string = preg_replace("/COLLATE ([\d\_a-zA-Z]+)/", 'COLLATE RPL1', $string);
		$string = preg_replace("/CHARSET=([\d\_a-zA-Z]+)/", 'CHARSET=RPL2', $string);
		$string = preg_replace("/AUTO_INCREMENT=([\d\_a-zA-Z]+)/", 'AUTO_INCREMENT=0', $string);
		return md5($string);
	}
	private function getTablesDiff($db1,$db2,$matches=false) {
		$jdataMD5 = array_column($db2, 'md5');
		$jdataTables = array_column($db2, 'table');
		
		/*
		 * 2 - структуру таблицы надо обновить (возможно и данные, если есть),
		 * 3 - такой таблицы нет в тек.версии
		 */
		$status = 0;
		$files = [];
		$files2 = [];
		foreach ($db1 as $value) {
			if (!in_array($value['md5'], $jdataMD5)) {
				$status = (in_array($value['table'], $jdataTables))?2:3;
				$files[] = [
						'md5' => $value['md5'],
						'status'=> $status,
						'table' => $value['table'],
						'columns' => $value['columns']
				];
			} elseif ($matches==true) {
				$files2[] = [
						'md5' => $value['md5'],
						'status'=> 0,
						'table' => $value['table'],
						'columns' => $value['columns']
				];
			}
		}
		return [
				'diff' => $files,
				'match'=> $files2
		];
	}
}
?>