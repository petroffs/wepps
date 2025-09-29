<?php
require_once __DIR__ . '/../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\TextTransforms;

class RequestBackup extends Request
{
	public function request($action = "")
	{
		$this->tpl = '';
		if (empty($this->cli) && @Connect::$projectData['user']['ShowAdmin'] != 1) {
			Exception::error404();
		}
		$cnf = Connect::$projectDB['cnf'];
		$db = Connect::$projectDB['dbname'];
		$root = Connect::$projectDev['root'];
		$host = Connect::$projectDev['host'];
		$backupPath = '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
		switch ($action) {
			case "database":
				/*
				 * Создать бекап
				 */
				$table = (!empty($this->get['list'])) ? $this->get['list'] : '';
				$path = $root . $backupPath;
				$type = 1;
				$comment = (!empty($this->get['comment'])) ? "-" . TextTransforms::translit($this->get['comment'], 2) : "";
				$filename = $path . $host . "-" . date("Ymd-His") . $comment . ".sql";

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
				if ($cmd == "ОК") {
					AdminUtils::modal("<p>Бекап базы данных: {$cmd}</p>");
				} else {
					AdminUtils::modal("<p>Ошибка запроса: {$str}</p>");
				}
				break;
			case "database-restore":
				if (!isset($this->get['form']) || !isset($this->get['id'])) {
					Connect::$instance->close();
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
				if ($cmd == "ОК") {
					AdminUtils::modal("<p>Бекап базы данных: {$cmd}</p>");
				} else {
					echo $str;
					AdminUtils::modal("<p>Бекап базы данных: {$str}</p>");
				}
				break;
			case "database-remove":
				if (!isset($this->get['form']) || !isset($this->get['id'])) {
					Connect::$instance->close();
				}
				/*
				 * Удалить файл бекапа
				 */
				$path = $root . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
				$filename = $path . $this->get['id'];

				if (is_file($filename)) {
					unlink($filename);
				}
				AdminUtils::modal('<p>Бекап базы данных: удален</p>');
				break;
			case "files":
				if (isset($this->get['add']) && (int) $this->get['add'] == 1) {
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
                    $('#dialog').html('<p>Выполните код в вашей OS из папки DocumentRoot:</p><label class=\"w_label w_input\"><input type=\"text\" value=\"{$cmd}\" id=\"dialog-cmd\"/></label>').dialog({
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
					Exception::error404();
				}

				$path = './' . $backupPath;
				$filename = $path . $this->get['id'];
				$cmd = "7z x {$filename} -aoa > ./debug.conf";

				/*
				 * Вывод финального сообщения
				 */
				$js = "
                    <script>
                    $('#dialog').html('<p>Выполните код в вашей OS из папки DocumentRoot:</p><label class=\"w_label w_input\"><input type=\"text\" value=\"{$cmd}\" id=\"dialog-cmd\"/></label>').dialog({
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
					Connect::$instance->close();
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
				AdminUtils::modal('<p>Бекап файлов: удален</p>');
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
				$res = Connect::$instance->fetch($sql);
				$str = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $res[0]['Create Table']) . ";\n\n";

				/*
				 * Конфиг
				 */
				$sql = "select * from s_Config where TableName = '$table'";
				$res = Connect::$instance->fetch($sql);

				unset($res[0]['Id']);
				$arr = AdminAdminUtils::query($res[0]);
				$str .= "insert ignore into s_Config {$arr['insert']}\n\n";

				/*
				 * Конфиг полей
				 */
				$sql = "select * from s_ConfigFields where TableName = '$table'";
				$res = Connect::$instance->fetch($sql);

				foreach ($res as $value) {
					$arr = AdminAdminUtils::query($value);
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
				$obj = new BackupFiles();
				$obj->addBackupIgnoredByGit();
				break;
			case "addBackup":
				$obj = new BackupFiles();
				$obj->addBackup();
				break;
			case "addBackupDB":
				$obj = new BackupFiles();
				$obj->addBackupDB(false);
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestBackup(!empty($argv) ? $argv : $_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);