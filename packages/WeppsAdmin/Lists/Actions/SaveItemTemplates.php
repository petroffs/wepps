<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;

class SaveItemTemplates extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='s_Templates') {
	    	$this->copyTpl($this->element['FileTemplate']);
	    }
	}
	private function copyTpl($tpl) {
		$tmp = substr($tpl, 0, - 4);
		$root = Connect::$projectDev['root']."/packages/WeppsExtensions/Template/";
		if (! is_file("{$root}{$tmp}.tpl")) {
			copy("{$root}Template.tpl","{$root}{$tmp}.tpl");
		}
		if (! is_file("{$root}{$tmp}.css")) {
			copy("{$root}Template.css","{$root}{$tmp}.css");
		}
		if (! is_file("{$root}{$tmp}.js")) {
			copy("{$root}Template.js","{$root}{$tmp}.js");
		}
	}
}