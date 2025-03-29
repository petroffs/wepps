<?php
namespace WeppsAdmin\ConfigExtensions\Backup;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;

require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../configloader.php';

/**
 * @var \Smarty $smarty
 */

class RequestBackupWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (empty($this->cli) && @ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
			ExceptionWepps::error404();
		}
		$cnf = ConnectWepps::$projectDB['cnf'];
		$db = ConnectWepps::$projectDB['dbname'];
		$root = ConnectWepps::$projectDev['root'];
		$host = ConnectWepps::$projectDev['host'];
		$backupPath = '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
		switch ($action) {
			case "database":
				/*
				 * Создать бекап
				 */
				$table = (!empty($this->get['list']))?$this->get['list']:'';
				$path = $root . $backupPath;
				$type = 1;
				$comment = (!empty($this->get['comment'])) ? "-".TextTransformsWepps::translit($this->get['comment'],2) : "";
				$filename = $path . $host . "-" . date("Ymd-His").$comment.".sql";
				
				/*
				 * Добавить исключения в бекапах
				 */
				switch ($type) {
					case "1":
						$str = "mysqldump --defaults-extra-file={$cnf} -K --default-character-set=utf8 --add-drop-table $db $table > $filename";
						break;
					case "2":
						$str = "mysqldump --defaults-extra-file={$cnf} -K --default-character-set=utf8 --add-drop-table $db DataTbls > $filename";
						break;
					default:
						$str = "mysqldump --defaults-extra-file={$cnf} -K --default-character-set=utf8 --add-drop-table $db > $filename";
						break;
				}
				system($str, $error);
				$cmd = ($error == 0) ? "ОК" : "Error: $error";
				
				/*
				 * Вывод финального сообщения
				 */
				if ($cmd=="ОК") {
					UtilsWepps::modal("<p>Бекап базы данных: {$cmd}</p>");
				} else {
					UtilsWepps::modal("<p>Ошибка запроса: {$str}</p>");
				}
				break;
			case "database-restore":
				if (!isset($this->get['form']) || !isset($this->get['id'])) {
					ConnectWepps::$instance->close();
				}
				/*
				 * Восстановить из бекапа
				 */
				$path = $root . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
				$filename = $path . $this->get['id'];
				if (strstr($filename, "sql.gz")) {
					$str = "zcat $filename | mysql --defaults-extra-file={$cnf} $db";
				} else {
					$str = "mysql --defaults-extra-file={$cnf} $db < $filename";
				}
				$error = "";
				system($str, $error);
				$cmd = ($error == 0) ? "ОК" : "Error ($error)";
				/*
				 * Вывод финального сообщения
				 */
				if ($cmd=="ОК") {
					UtilsWepps::modal("<p>Бекап базы данных: {$cmd}</p>");
				} else {
					echo $str;
					UtilsWepps::modal("<p>Бекап базы данных: {$str}</p>");
				}
				break;
			case "database-remove":
				if (!isset($this->get['form']) || !isset($this->get['id'])) {
					ConnectWepps::$instance->close();
				}
				/*
				 * Удалить файл бекапа
				 */
				$path = $root . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
				$filename = $path . $this->get['id'];
				
				if (is_file($filename)) {
					unlink($filename);
				}
				UtilsWepps::modal('<p>Бекап базы данных: удален</p>');
				break;
			case "files":
				if (isset($this->get['add']) && (int) $this->get['add']==1) {
					$exclude = "-xr\\\!pic/* -xr\\\!{$host}*.7z";
					if (empty($this->get['add-git'])) {
						$exclude .= " -xr\\\!.git";
					}
					
					/*
					 * Создать бекап
					 */
					$filename = "." . $backupPath . $host . "-" . date("Ymd-His") . ".7z";
					$cmd = "7z a {$filename} . $exclude > ./debug.conf";

					/*
					 * Вывод финального сообщения
					 */
					$js = "
                    <script>
                    $('#dialog').html('<p>Выполните код в вашей OS из папки DocumentRoot:</p><label class=\"pps pps_input\"><input type=\"text\" value=\"{$cmd}\" id=\"dialog-cmd\"/></label>').dialog({
        				'title':'Сообщение',
        				'modal': true,
        				'buttons' : [{
        					text : 'Копировать',
        					icon : 'ui-icon-copy',
        					click : function() {
								var element1 = $('#dialog-cmd');
								element1.select();
								document.execCommand('copy');
        					}
        				},{
        					text : 'Закрыть',
        					icon : 'ui-icon-close',
        					click : function() {
        						$(this).dialog('close');
        					}
        				}]
        			});
                    </script>
                    ";
					echo $js;
					
				}
				break;
			case "files-restore":
				$path = $root . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
				$filename = $path . $this->get['id'];
				
				if (!is_file($filename)) {
					ExceptionWepps::error404();
				}
				
				$path = './' . $backupPath;
				$filename = $path . $this->get['id'];
				$cmd = "7z x {$filename} -aoa > ./debug.conf";
				
				/*
				 * Вывод финального сообщения
				 */
				$js = "
                    <script>
                    $('#dialog').html('<p>Выполните код в вашей OS из папки DocumentRoot:</p><label class=\"pps pps_input\"><input type=\"text\" value=\"{$cmd}\" id=\"dialog-cmd\"/></label>').dialog({
        				'title':'Сообщение',
        				'modal': true,
        				'buttons' : [{
        					text : 'Копировать',
        					icon : 'ui-icon-copy',
        					click : function() {
								var element1 = $('#dialog-cmd');
								element1.select();
								document.execCommand('copy');
        					}
        				},{
        					text : 'Закрыть',
        					icon : 'ui-icon-close',
        					click : function() {
        						$(this).dialog('close');
        					}
        				}]
        			});
                    </script>
                    ";
				echo $js;
				break;
			case "files-remove":
				if (!isset($this->get['form']) || !isset($this->get['id'])) {
					ConnectWepps::$instance->close();
				}
				/*
				 * Удалить файл бекапа
				 */
				$path = $root . $backupPath;
				$filename = $path . $this->get['id'];
				if (is_file($filename)) {
					unlink($filename);
				}
				
				/*
				 * Вывод финального сообщения
				 */
				UtilsWepps::modal('<p>Бекап файлов: удален</p>');
				break;
			case "list":
				/*
				 * Создание sql для создания списка
				 */
				
				$table = addslashes($this->get['id']);
				
				/*
				 * Структура бд
				 */
				$sql = "SHOW CREATE TABLE $table";
				$res = ConnectWepps::$instance->fetch($sql);
				$str = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $res[0]['Create Table']).";\n\n";

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
				
				header("Content-Type: text/sql");
				header("Content-Disposition:attachment;filename=list_{$table}.sql");
				$str = preg_replace("/values \('(\d+)'/", 'values (null', $str);				
				echo $str;
				break;
				
			/*
			 * Не используется в UI
			 */
			case "addBackupIgnored":
				$obj = new BackupFilesWepps();
				$obj->addBackupIgnoredByGit();
				break;
			case "addBackup":
				$obj = new BackupFilesWepps();
				$obj->addBackup();
				break;
			case "addBackupDB":
				$obj = new BackupFilesWepps();
				$obj->addBackupDB(false);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestBackupWepps (!empty($argv)?$argv:$_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);