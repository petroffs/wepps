<?php
namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Exception;
use WeppsCore\Data;

class Uploads extends Request {
	public $way;
	public $title;
	public $headers;
	public function request($action="") {
		$smarty = Smarty::getSmarty();
		$this->tpl = 'Uploads.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
			'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/",
			'Name'=>$this->title
		]);
		$this->headers = new TemplateHeaders();
		$this->headers->js ("/packages/WeppsAdmin/ConfigExtensions/Uploads/Uploads.{$this->headers::$rand}.js");
		$this->headers->css ("/packages/WeppsAdmin/ConfigExtensions/Uploads/Uploads.{$this->headers::$rand}.css");
		if ($action=="") {
			return;
		}
		switch ($action) {
			case 'excel':
				$this->title = "Загрузки из Excel";
				$this->tpl = 'UploadsExcel.tpl';
				if (isset($_SESSION['uploads']['list-data-form'])) {
					$smarty->assign('uploaded',$_SESSION['uploads']['list-data-form']);
				}

				$obj = new Data("s_UploadsSource");
				$source = $obj->fetchmini("DisplayOff=0",200,1);
				$smarty->assign('source',$source);
				
				$obj = new Data("s_Files");
				$files = $obj->fetch("TableName='s_UploadsSource'",5,1,"t.Id desc");
				$smarty->assign('files',$files);
				break;
			default:
				Exception::error404();
				break;
		}
		array_push($this->way, [
			'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
			'Name'=>$this->title
		]);
	}
}