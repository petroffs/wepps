<?php

namespace WeppsAdmin\Updates;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class UpdatesMethodsWepps extends UpdatesWepps {
	public $parent = 0;
	public $settings;
	private $filename;
	
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
		
		if (!in_array('Content-Type: application/zip',$response->response_headers)) {
			return [
					'output' => 'wrong update file\'s url : '.$fileSrc
			];
		}
		
		$this->cli->put($response->response, $fileDst);
		$path = pathinfo($fileDst);
		
		/*
		 * Распаковать в папку $tag
		 */
		$zip = new \ZipArchive();
		$result = $zip->open($fileDst);
		$zipPath = $path['dirname'].'/updates';
		if ($result === false) {
			return [
					'output' => 'Zip error'
			];
		}
		
		/*
		 * При отладке скрыть
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
		$this->cli->error("Disallowed files:\n".implode("\n", $modified));
		$this->cli->br();
		$this->cli->success("Allowed files:\n".implode("\n", $diff));
		
		$this->cli->put(json_encode([
				'disallowed'=>$modified,
				'allowed'=>$diff
		],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), $path['dirname']."/log.conf");
		
		$this->cli->br();
		$this->cli->warning("Type 'yes' to continue: ");
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'yes'){
			return [
					'output'=>"Aborting!"
			];
		}
		return self::setUpdatesInstall($diff,$path['dirname']);
	}
	private function setUpdatesInstall(array $diff = [],string $path) {
		$diff[] = 'packages/WeppsAdmin/Updates/files/md5.conf';

		$pathRollback = $path . "/rollback";
		$pathDiff = $path . "/diff";
		
		foreach ($diff as $value) {
			if (empty($value)) {
				continue;
			}
			if (file_exists(ConnectWepps::$projectDev['root']."/".$value)) {
				$this->cli->copy(ConnectWepps::$projectDev['root']."/".$value, $pathRollback."/".$value);
			}
			if (file_exists($path."/updates/".$value)) {
				$this->cli->copy($path."/updates/".$value, $pathDiff."/".$value);
			}
		}
		$this->zip("{$path}/wepps.platform-rollback.zip", "{$pathRollback}/*");
		$this->zip("{$path}/wepps.platform-diff.zip", "{$pathDiff}/*");

		/*
		 * Реальный апдейт из updates
		 * После него только откат вернет файлы
		 */
		foreach ($diff as $value) {
			if (empty($value)) {
				continue;
			}
			$this->cli->copy($path."/updates/".$value, ConnectWepps::$projectDev['root']."/".$value);
		}
		
		$this->cli->rmdir($pathRollback);
		$this->cli->rmdir($pathDiff);
		
		/*
		 * При отладке скрыть
		 */
		$this->cli->rmdir($path . "/updates");

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
				'files'=>$files
		];
	}
	private function zip (string $archive,string $path) : bool {
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
}
?>