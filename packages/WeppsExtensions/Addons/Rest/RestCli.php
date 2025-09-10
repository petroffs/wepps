<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Utils;

class RestCli extends Rest {
	public $parent = 0;
	public function __construct($settings=[]) {
		parent::__construct($settings);
	}
	public function removeLogLocal() {
		$sql = "truncate s_Tasks";
		Connect::$instance->query($sql);
		$directoryPath = __DIR__."/files/";
		$directoryScan = scandir($directoryPath);
		if (count($directoryScan)>2) {
			exec("rm {$directoryPath}*");
		}
	}
	public function cliTest() {
		$output = [
				'message'=>'ok'
		];
		$this->status = 200;
		$this->setResponse($output,false);
	}
}

?>