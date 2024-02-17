<?php

namespace WeppsAdmin\Updates;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;
use WeppsCore\Utils\CliWepps;

class UpdatesMethodsWepps extends UpdatesWepps {
	public $parent = 0;
	public $settings;
	private $filename;
	private $cli;
	public function __construct($settings=[]) {
		parent::__construct();
		$this->filename = __DIR__.'/files/md5.conf';
		$this->cli = new CliWepps();
		$this->cli->display();
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
		return $this->getDiff($this->filename);
	}
	public function getReleasesList() : array {
		$filename = @ConnectWepps::$projectServices['weppsupdates']['weppsurl']."/releases.json";
		if (empty($filename)) {
			return [
					'output' => 'wrong settings'
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
					'output' => 'wrong settings weppsurl'
			];
		}
		$current = $this->getReleaseCurrentVersion();
		$jdata = $jdata2 = json_decode($json,true);
		if (empty($jdata)) {
			return [
					'output' => 'wrong settings url: '.$filename
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
					'output'=>"wrong tag"
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
		
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0750, true);
		}
		if (!file_exists($fileDst)) {
			file_put_contents($fileDst,$response->response);
		}
		*/
		
		/*
		 * Распаковать в папку $tag
		 */
		$zip = new \ZipArchive();
		$result = $zip->open($fileDst);
		$zipPath = $path['dirname'].'/updates';
		if ($result === false) {
			return [
					'output' => 'zip error'
			];
		}
		$zip ->extractTo($zipPath);
		$zip ->close();
		
		$fileMD5 = $zipPath."/packages/WeppsAdmin/Updates/files/md5.conf";
		
		
		/*
		 * Это нельзя перезаписывать из релиз-архива
		 */
		$modifiedSelf = $this->getReleaseCurrentModified();
		
		/*
		 * Это нужно перезаписать
		 */
		$modifiedRelease = $this->getDiff($fileMD5);
		
		/*
		 * Расхождение diff между $modifiedSelf и $modifiedRelease
		 * Нужно записать как обновление
		 * 
		 * Перед записью diff необходимо записать backup
		 */
		$diff = array_diff(explode("\n",$modifiedRelease['output']),explode("\n",$modifiedSelf['output']));
		
		if (empty($diff)) {
			return [
					'output' => "no data for update"
			];
		}
		
		$this->cli->error("\nDisallowed files:\n{$modifiedSelf['output']}");
		$this->cli->success("\nAllowed files:\n".implode("\n", $diff));
		$this->cli->warning("\nType 'yes' to continue: ");
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
		
		if (empty($diff)) {
			return [
					'output' => 'no files for update'
			];
		}
		$diff[] = 'packages/WeppsAdmin/Updates/files/md5.conf';
		$pathRollback = $path . "/rollback";
		$pathDiff = $path . "/diff";
		$pathUpdates = $path . "/updates";
		foreach ($diff as $value) {
			$this->cli->copy(ConnectWepps::$projectDev['root']."/".$value, $pathRollback."/".$value);
			$this->cli->copy($path."/updates/".$value, $pathDiff."/".$value);
		}
		
		$archive = "{$path}/wepps.platform-rollback.zip";
		$cmd = "7z a -tzip {$archive} {$pathRollback}/*";
		$this->command($cmd);
		
		$archive = "{$path}/wepps.platform-diff.zip";
		$cmd = "7z a -tzip {$archive} {$pathDiff}/*";
		$this->command($cmd);
		//UtilsWepps::debugf($cmd,1);
		
		/*
		 * Реальный апдейт из updates
		 * После него только откат вернет файлы
		 */
		foreach ($diff as $value) {
			$this->cli->copy($path."/updates/".$value, ConnectWepps::$projectDev['root']."/".$value);
		}
		
		/*
		 * delete path* forlders, keep zip only
		 */
		sleep(3);
		$this->cli->rmdir($pathRollback);
		$this->cli->rmdir($pathDiff);
		$this->cli->rmdir($pathUpdates);

		return [
				'output' => 'Updates is complete succsessfull!'
		];
	}
	private function getFilesum($filename) {
		$str = file_get_contents($filename);
		$str = str_replace("\r\n", "\n", $str);
		return md5($str);
	}
	private function getDiff($fileMD5) {
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
				'output'=>$output,
				'files'=>$files
		];
	}
	private function command(string $cmd) : bool {
		if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
			$cmd = str_replace("\\", "/", $cmd);
		}
		exec($cmd);
		return true;
	}
}
?>