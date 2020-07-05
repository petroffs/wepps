<?
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
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$tpl = 'Backup.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = array(0=>array('Url'=>"/_pps/extensions/{$this->get['ext']['KeyUrl']}/",'Name'=>$this->title));
		$headers = new TemplateHeadersWepps();
		$headers->js ("/packages/WeppsAdmin/ConfigExtensions/Backup/Backup.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/ConfigExtensions/Backup/Backup.{$headers::$rand}.css");
		switch ($action) {
			case 'database':
				$this->title = "Резервирование базы данных";
				$tpl = 'BackupDatabase.tpl';
				$dh = opendir(ConnectWepps::$projectDev['root']."/packages/WeppsAdmin/ConfigExtensions/Backup/files/");
				while ($file = readdir($dh)) {
					if ($file != '.' && $file != '..' && strstr($file, ".sql"))
						$files[] = $file;
				}
				closedir($dh);
				$systemMessage1 = "";
				if (isset($files)) {
					sort($files);
					reset($files);
					$smarty->assign('backups',$files);
				}
				break;
			case 'backuplists':
				$this->title = "Резервирование списков";
				$tpl = 'BackupLists.tpl';
				
				$perm = AdminWepps::userPerm($_SESSION['user']['UserPermissions']);
				$translate = AdminWepps::getTranslate();
				$smarty->assign('translate',$translate);
				//UtilsWepps::debug($translate);
				/*
				 * Списки с учетом прав доступа
				 */
				
				$fcond = "'".implode("','", $perm['lists'])."'";
				$sql = "select * from s_Config as t where TableName in ($fcond) order by t.Category,t.Priority";
				$res = ConnectWepps::$instance->fetch($sql);
				$str = "";
				foreach ($res as $value) {
					$str .= "union (select '{$value['TableName']}' as TableName,count(*) as Rows,  (select count(*) from s_ConfigFields as cf where cf.TableName='{$value['TableName']}') as Fields from {$value['TableName']} as t)\n";
				}
				$str = trim($str,"union ");
				$stat = ConnectWepps::$instance->fetch($str,array(),'group');
				$arr = array();
				foreach ($res as $value) {
					$value['RowsCount'] = $stat[$value['TableName']][0]['Rows'];
					$value['FieldsCount'] = $stat[$value['TableName']][0]['Fields'];
					$arr[$value['Category']][] = $value;
				}
				$smarty->assign('lists',$arr);
				
				//UtilsWepps::debugf($arr,1);
				
				
				break;
			case 'files':
				$this->title = "Резервирование файлов";
				$tpl = 'BackupFiles.tpl';
				$dh = opendir(ConnectWepps::$projectDev['root']."/packages/WeppsAdmin/ConfigExtensions/Backup/files/");
				while ($file = readdir($dh)) {
					if ($file != '.' && $file != '..' && strstr($file, ".7z"))
						$files[] = $file;
				}
				closedir($dh);
				$systemMessage1 = "";
				if (isset($files)) {
					sort($files);
					reset($files);
					$smarty->assign('backups',$files);
				}
				break;
			default:
				if ($action!="") {
					ExceptionWepps::error404();
				}
				break;
		}
		$this->headers = &$headers;
		//$this->tpl = $smarty->fetch( __DIR__ . '/' . $tpl);
		$this->tpl = $tpl;
	}
}
?>