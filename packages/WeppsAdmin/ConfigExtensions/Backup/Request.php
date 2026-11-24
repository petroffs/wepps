<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsAdmin\ConfigExtensions\Backup;

require_once __DIR__ . '/../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\TextTransforms;
use WeppsCore\Utils;

class RequestBackup extends Request
{
	#[\Override]
	public function request($action = ""): void
	{
		$this->tpl = '';
		if (empty($this->cli) && @Connect::$projectData['user']['ShowAdmin'] != 1) {
			Exception::error404();
		}
		$cnf = Connect::$projectDB['cnf'];
		$db = Connect::$projectDB['dbname'];
		$root = Connect::$projectDev['root'];
		$host = Connect::$projectDev['host'];
		$charset = Connect::$projectDB['charset'];
		$backupPath = '/packages/WeppsAdmin/ConfigExtensions/Backup/files/';
		switch ($action) {
			case "database":
				/*
				 * Создать бекап
				 */
				$table = (!empty($this->get['list']) && is_string($this->get['list'])) ? $this->get['list'] : '';
				$path = $root . $backupPath;
				$type = 1;
				$comment = (!empty($this->get['comment'])) ? "-" . TextTransforms::translit($this->get['comment'], 2) : "";
				$filename = $path . $host . "-" . date("Ymd-His") . $comment . ".sql";

				/*
				 * Добавить исключения в бекапах
				 */
				switch ($type) {
					case "1":
						$tableArg = ($table !== '') ? ' ' . escapeshellarg($table) : '';
						$str = "mysqldump --defaults-extra-file=" . escapeshellarg((string)$cnf) . " -K --default-character-set=" . escapeshellarg((string)$charset) . " --add-drop-table " . escapeshellarg((string)$db) . $tableArg . " > " . escapeshellarg($filename);
						break;
					case "2":
						$str = "mysqldump --defaults-extra-file=" . escapeshellarg((string)$cnf) . " -K --default-character-set=" . escapeshellarg((string)$charset) . " --add-drop-table " . escapeshellarg((string)$db) . " DataTbls > " . escapeshellarg($filename);
						break;
					default:
						$str = "mysqldump --defaults-extra-file=" . escapeshellarg((string)$cnf) . " -K --default-character-set=" . escapeshellarg((string)$charset) . " --add-drop-table " . escapeshellarg((string)$db) . " > " . escapeshellarg($filename);
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
					AdminUtils::modal("<p>Ошибка запроса: " . htmlspecialchars($str, ENT_QUOTES) . "</p>");
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
				$id = is_string($this->get['id'] ?? null) ? basename($this->get['id']) : '';
				if (!preg_match('/^[A-Za-z0-9._-]+$/', $id)) {
					AdminUtils::modal("<p>Некорректное имя файла резервной копии</p>");
					break;
				}
				$filename = $path . $id;
				$charset = Connect::$projectDB['charset'];
				if (strstr($filename, "sql.gz")) {
					$str = "zcat " . escapeshellarg($filename) . " | mysql --defaults-file=" . escapeshellarg((string)$cnf) . " --default-character-set=" . escapeshellarg((string)$charset) . " " . escapeshellarg((string)$db);
				} else {
					$str = "mysql --defaults-file=" . escapeshellarg((string)$cnf) . " --default-character-set=" . escapeshellarg((string)$charset) . " " . escapeshellarg((string)$db) . " < " . escapeshellarg($filename);
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
					echo htmlspecialchars($str, ENT_QUOTES);
					AdminUtils::modal("<p>Бекап базы данных: " . htmlspecialchars($str, ENT_QUOTES) . "</p>");
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
				// id из запроса: basename + safeRelPath — удаление только внутри папки бекапов
				$id = Utils::safeRelPath(basename((string) ($this->get['id'] ?? ''))) ?? '';
				if ($id === '') {
					AdminUtils::modal('<p>Некорректное имя файла резервной копии</p>');
					break;
				}
				$filename = $path . $id;

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
					$cmdJs = htmlspecialchars($cmd, ENT_QUOTES);
				$js = "
                    <script>
                    $('#dialog').html('<p>Выполните код в вашей OS из папки DocumentRoot:</p><label class=\"w_label w_input\"><input type=\"text\" value=\"{$cmdJs}\" id=\"dialog-cmd\"/></label>').dialog({
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
				$cmdJs = htmlspecialchars($cmd, ENT_QUOTES);
				$js = "
                    <script>
                    $('#dialog').html('<p>Выполните код в вашей OS из папки DocumentRoot:</p><label class=\"w_label w_input\"><input type=\"text\" value=\"{$cmdJs}\" id=\"dialog-cmd\"/></label>').dialog({
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
				// id из запроса: basename + safeRelPath — удаление только внутри папки бекапов
				$id = Utils::safeRelPath(basename((string) ($this->get['id'] ?? ''))) ?? '';
				if ($id === '') {
					AdminUtils::modal('<p>Некорректное имя файла резервной копии</p>');
					break;
				}
				$filename = $path . $id;
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

				$table = addslashes(htmlspecialchars((string) $this->get['id'], ENT_QUOTES));
				// Имя таблицы уходит в SQL напрямую — только идентификаторы [A-Za-z0-9_]
				if (preg_match('/^[A-Za-z0-9_]+$/D', (string) $this->get['id']) !== 1) {
					AdminUtils::modal("<p>Некорректное имя таблицы</p>");
					break;
				}

				/*
				 * Структура бд
				 */
				$sql = "SHOW CREATE TABLE $table";
				$res = Connect::$instance->fetch($sql);
				$str = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $res[0]['Create Table']) . ";\n\n";

				/*
				 * Конфиг
				 */
				$sql = "select * from s_Config where TableName = ?";
				$res = Connect::$instance->fetch($sql, [$table]);

				unset($res[0]['Id']);
				$arr = Connect::$instance->prepare($res[0]);
				$str .= "insert ignore into s_Config {$arr['insert']}\n\n";

				/*
				 * Конфиг полей
				 */
				$sql = "select * from s_ConfigFields where TableName = ?";
				$res = Connect::$instance->fetch($sql, [$table]);

				foreach ($res as $value) {
					$arr = Connect::$instance->prepare($value);
					$str .= "insert ignore into s_ConfigFields {$arr['insert']}\n";
				}

				header("Content-Type: text/sql");
				header("Content-Disposition:attachment;filename=list_" . Utils::safeChars($table, '/[^A-Za-z0-9_]/') . ".sql");
				$str = preg_replace("/values \('(\d+)'/", 'values (null', $str);
				// Выгрузка SQL-файла: Content-Type text/sql + attachment, в браузер не рендерится
				/** @psalm-suppress TaintedInput */
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