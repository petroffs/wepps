<?php
namespace WeppsAdmin\ConfigExtensions\Backup;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsAdmin\Admin\Admin;

class Backup extends Request
{
	public $way;
	public $title;
	public $headers;
	public function request($action = "")
	{
		$smarty = Smarty::getSmarty();
		$this->tpl = 'Backup.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
			'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/",
			'Name' => $this->title
		]);
		$this->headers = new TemplateHeaders();
		$this->headers->js("/packages/WeppsAdmin/ConfigExtensions/Backup/Backup.{$this->headers::$rand}.js");
		$this->headers->css("/packages/WeppsAdmin/ConfigExtensions/Backup/Backup.{$this->headers::$rand}.css");
		if ($action == "") {
			return;
		}
		switch ($action) {
			case 'database':
				$this->title = "Резервирование базы данных";
				$this->tpl = 'BackupDatabase.tpl';
				$dh = opendir(Connect::$projectDev['root'] . "/packages/WeppsAdmin/ConfigExtensions/Backup/files/");
				while ($file = readdir($dh)) {
					if ($file != '.' && $file != '..' && strstr($file, ".sql"))
						$files[] = $file;
				}
				closedir($dh);
				if (isset($files)) {
					sort($files);
					reset($files);
					$smarty->assign('backups', $files);
				}
				break;
			case 'backuplists':
				$this->title = "Резервирование списков";
				$this->tpl = 'BackupLists.tpl';

				$perm = Admin::getPermissions(Connect::$projectData['user']['UserPermissions']);
				$translate = Admin::getTranslate();
				$smarty->assign('translate', $translate);
				/*
				 * Списки с учетом прав доступа
				 */
				$fcond = "'" . implode("','", $perm['lists']) . "'";
				$sql = "select * from s_Config as t where TableName in ($fcond) order by t.Category,t.Priority";
				$res = Connect::$instance->fetch($sql);
				$arr = [];
				foreach ($res as $value) {
					$arr[$value['Category']][] = $value;
				}
				$smarty->assign('lists', $arr);
				break;
			case 'files':
				$this->title = "Резервирование файлов";
				$this->tpl = 'BackupFiles.tpl';
				$dh = opendir(Connect::$projectDev['root'] . "/packages/WeppsAdmin/ConfigExtensions/Backup/files/");
				while ($file = readdir($dh)) {
					if ($file != '.' && $file != '..' && strstr($file, ".7z"))
						$files[] = $file;
				}
				closedir($dh);
				if (isset($files)) {
					sort($files);
					reset($files);
					$smarty->assign('backups', $files);
				}
				break;
			default:
				Exception::error404();
				break;
		}
		array_push($this->way, [
			'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
			'Name' => $this->title
		]);
	}
}