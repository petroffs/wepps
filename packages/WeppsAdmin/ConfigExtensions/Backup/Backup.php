<?php
namespace WeppsAdmin\ConfigExtensions\Backup;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Admin\AdminWepps;

class BackupWepps extends RequestWepps {
	public $way;
	public $title;
	public $headers;
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = 'Backup.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
			'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/",
			'Name'=>$this->title
		]);
		$this->headers = new TemplateHeadersWepps();
		$this->headers->js ("/packages/WeppsAdmin/ConfigExtensions/Backup/Backup.{$this->headers::$rand}.js");
		$this->headers->css ("/packages/WeppsAdmin/ConfigExtensions/Backup/Backup.{$this->headers::$rand}.css");
		if ($action=="") {
			return;
		}
		switch ($action) {
			case 'database':
				$this->title = "Резервирование базы данных";
				$this->tpl = 'BackupDatabase.tpl';
				$dh = opendir(ConnectWepps::$projectDev['root']."/packages/WeppsAdmin/ConfigExtensions/Backup/files/");
				while ($file = readdir($dh)) {
					if ($file != '.' && $file != '..' && strstr($file, ".sql"))
						$files[] = $file;
				}
				closedir($dh);
				if (isset($files)) {
					sort($files);
					reset($files);
					$smarty->assign('backups',$files);
				}
				break;
			case 'backuplists':
				$this->title = "Резервирование списков";
				$this->tpl = 'BackupLists.tpl';
				
				$perm = AdminWepps::getPermissions(ConnectWepps::$projectData['user']['UserPermissions']);
				$translate = AdminWepps::getTranslate();
				$smarty->assign('translate',$translate);
				/*
				 * Списки с учетом прав доступа
				 */
				$fcond = "'".implode("','", $perm['lists'])."'";
				$sql = "select * from s_Config as t where TableName in ($fcond) order by t.Category,t.Priority";
				$res = ConnectWepps::$instance->fetch($sql);
				$str = "";
				foreach ($res as $value) {
					$str .= "union (select '{$value['TableName']}' as TableName,count(*) as `Rows`,  (select count(*) from s_ConfigFields as cf where cf.TableName='{$value['TableName']}') as Fields from {$value['TableName']} as t)\n";
				}
				$str = trim($str,"union ");
				$stat = ConnectWepps::$instance->fetch($str,[],'group');
				$arr = [];
				foreach ($res as $value) {
					$value['RowsCount'] = $stat[$value['TableName']][0]['Rows'];
					$value['FieldsCount'] = $stat[$value['TableName']][0]['Fields'];
					$arr[$value['Category']][] = $value;
				}
				$smarty->assign('lists',$arr);
				break;
			case 'files':
				$this->title = "Резервирование файлов";
				$this->tpl = 'BackupFiles.tpl';
				$dh = opendir(ConnectWepps::$projectDev['root']."/packages/WeppsAdmin/ConfigExtensions/Backup/files/");
				while ($file = readdir($dh)) {
					if ($file != '.' && $file != '..' && strstr($file, ".7z"))
						$files[] = $file;
				}
				closedir($dh);
				if (isset($files)) {
					sort($files);
					reset($files);
					$smarty->assign('backups',$files);
				}
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		array_push($this->way, [
			'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
			'Name'=>$this->title
		]);
	}
}