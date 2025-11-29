<?php
namespace WeppsAdmin\Updates;

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Connect;
use Curl\Curl;
use WeppsCore\Utils;

/**
 * Class UpdatesMethods
 *
 * Обеспечивает набор утилит для работы с релизами/обновлениями проекта:
 * - чтение информации о текущей версии и изменённых файлах
 * - загрузка и распаковка релизного zip-файла
 * - подготовка и применение обновлений (файловые и SQL изменения)
 *
 * Использует настройки из `Connect::$projectServices['wepps']` и взаимодействует
 * с консольным интерфейсом `$this->cli` для вывода и подтверждения действий.
 */
class UpdatesMethods extends Updates
{
	/** @var int Parent id (unused in current implementation) */
	public int $parent = 0;
	private string $filename;
	private array $path;
	private string $nameconf;

	/**
	 * UpdatesMethods constructor.
	 *
	 * @param array $settings Опции и настройки (пока не используются)
	 */
	public function __construct($settings = [])
	{
		parent::__construct();
		$this->filename = __DIR__ . '/files/md5.conf';
		$this->nameconf = Connect::$projectServices['wepps']['nameconf'];
	}
	/**
	 * Возвращает версию текущего релиза, записанную в `md5.conf`.
	 *
	 * @return string Текущая версия или сообщение об отсутствии информации
	 */
	public function getReleaseCurrentVersion(): string
	{
		if (!is_file($this->filename)) {
			return 'no version info';
		}
		$jdata = json_decode(file_get_contents($this->filename), true);
		return $jdata['version'] ?? 'version info fail';
	}
	/**
	 * Возвращает список изменённых/несовпадающих файлов для текущего релиза.
	 *
	 * @return array Массив с ключами: 'filename','output','files'
	 */
	public function getReleaseCurrentModified(): array
	{
		return $this->getDiff($this->filename, false);
	}
	/**
	 * Загружает список релизов с удалённого `releases.json` и помечает текущий.
	 *
	 * @return array Массив с ключами: 'releases','list','output','version' или 'output' с ошибкой
	 */
	public function getReleasesList(): array
	{
		$filename = @Connect::$projectServices['wepps']['updates'] . "/releases.json";
		if (empty($filename)) {
			return [
				'output' => 'Wrong settings'
			];
		}
		$arrContextOptions = [
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false
			]
		];
		$json = @file_get_contents($filename, false, stream_context_create($arrContextOptions));
		if (empty($json)) {
			return [
				'output' => 'Wrong settings weppsurl'
			];
		}
		$current = $this->getReleaseCurrentVersion();
		$jdata = $jdata2 = json_decode($json, true);
		if (empty($jdata)) {
			return [
				'output' => 'Wrong settings url: ' . $filename
			];
		}
		$index = 0;
		foreach ($jdata as $key => $value) {
			if ($value == $current) {
				$jdata2[$key] = $value . " (current)";
				$index = $key;
			}
		}
		$jdata = array_slice($jdata, 0, $index + 1);
		$jdata2 = array_slice($jdata2, 0, $index + 1);
		$output = implode("\n", $jdata2);
		return [
			'releases' => $jdata,
			'list' => $jdata2,
			'output' => $output,
			'version' => $current
		];
	}
	/**
	 * Запускает процесс скачивания и подготовки выбранного релиза по тегу.
	 *
	 * Выполняет валидацию, скачивает zip-файл релиза, распаковывает его и
	 * формирует список отличий/разрешённых файлов. Для применения обновления
	 * требуется подтверждение в STDIN ('yes').
	 *
	 * @param string $tag Тег релиза, например 'v1.2.3'
	 * @return array Ассоциативный массив с ключом 'output' и сообщением об ошибке или результате
	 */
	public function setUpdates(string $tag)
	{
		$releases = $this->getReleasesList();
		if (!in_array($tag, $releases['releases']) || $releases['version'] == $tag) {
			return [
				'output' => "Wrong tag"
			];
		}

		// @debug: тестовый флаг для локальной отладки
		// Значение 0 — обычный режим работы (скачивание и распаковка релиза выполняются).
		// Значение != 0 — тестовый режим: пропускает сетевые операции/распаковку,
		// используется при локальной отладке, чтобы не обращаться к внешним ресурсам.
		$test = 0;

		$file = $this->nameconf . '-updates.zip';
		$fileDst = __DIR__ . "/files/updates/$tag/$file";
		$fileSrc = @Connect::$projectServices['wepps']['updates'] . "/packages/PPSAdmin/Releases/files/$tag/$file";

		if ($test == 0) {
			$curl = new Curl();
			$curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
			$curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
			$response = $curl->get($fileSrc);
			if (!in_array('content-type: application/zip', array_map('strtolower', $response->response_headers))) {
				return [
					'output' => 'wrong update file\'s url : ' . $fileSrc
				];
			}
			$this->cli->put($response->response, $fileDst);
		}
		$this->path = pathinfo($fileDst);

		/*
		 * Распаковать в папку $tag
		 */
		$zip = new \ZipArchive();
		$result = $zip->open($fileDst);
		$zipPath = $this->path['dirname'] . '/updates';
		$fileMD5 = $zipPath . "/packages/WeppsAdmin/Updates/files/md5.conf";
		if ($result === false) {
			return [
				'output' => 'Zip error'
			];
		}

		/*
		 * При отладке скрыть
		 */
		if ($test == 0) {
			$zip->extractTo($zipPath);
			$zip->close();
		}

		/*
		 * Измененные файлы релиза
		 */
		$modifiedSelf = $this->getReleaseCurrentModified();

		/*
		 * Это нужно перезаписать в текущий релиз
		 */
		$modifiedRelease = $this->getDiff($fileMD5, true);

		/*
		 * Расхождение между $modifiedSelf и $modifiedRelease
		 */
		$modified = explode("\n", $modifiedSelf['output']);
		$diff = array_diff(explode("\n", $modifiedRelease['output']), $modified);
		$diff = array_values($diff);
		$this->cli->br();
		$this->cli->warning("Disallowed files:\n" . implode("\n", $modified));
		$this->cli->br();
		$this->cli->success("Allowed files:\n" . implode("\n", $diff));
		$this->cli->put(json_encode([
			'disallowed' => $modified,
			'allowed' => $diff
		], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), $this->path['dirname'] . "/log.conf");

		/*
		 * БД
		 */
		$sql = $this->getTablesUpdates($fileMD5);
		$this->cli->br();
		$this->cli->warning("Type 'yes' to continue: ");
		$handle = fopen("php://stdin", "r");
		$line = fgets($handle);
		if (trim($line) != 'yes') {
			return [
				'output' => "Aborting!"
			];
		}
		return self::setUpdatesInstall($diff, $sql);
	}
	/**
	 * Применяет файловые изменения и SQL (при наличии) для установки обновления.
	 *
	 * @param array $diff Список путей файлов для копирования из распакованного пакета
	 * @param string $sql SQL-скрипт для выполнения (может быть пустым)
	 * @return array Результат операции
	 */
	private function setUpdatesInstall(array $diff = [], string $sql = '')
	{
		$diff[] = 'packages/WeppsAdmin/Updates/files/md5.conf';

		$pathRollback = $this->path['dirname'] . "/rollback";
		$pathDiff = $this->path['dirname'] . "/diff";

		foreach ($diff as $value) {
			if (empty($value)) {
				continue;
			}
			if (file_exists(Connect::$projectDev['root'] . "/" . $value)) {
				$this->cli->copy(Connect::$projectDev['root'] . "/" . $value, $pathRollback . "/" . $value);
			}
			if (file_exists($this->path['dirname'] . "/updates/" . $value)) {
				$this->cli->copy($this->path['dirname'] . "/updates/" . $value, $pathDiff . "/" . $value);
			}
		}
		$this->zip("{$this->path['dirname']}/{$this->nameconf}-rollback.zip", "{$pathRollback}/*");
		$this->zip("{$this->path['dirname']}/{$this->nameconf}-diff.zip", "{$pathDiff}/*");

		/*
		 * Реальный апдейт из updates
		 * После него только откат вернет файлы
		 */
		foreach ($diff as $value) {
			if (empty($value)) {
				continue;
			}
			$this->cli->copy($this->path['dirname'] . "/updates/" . $value, Connect::$projectDev['root'] . "/" . $value);
		}

		$this->cli->rmdir($pathRollback);
		$this->cli->rmdir($pathDiff);

		/*
		 * При отладке скрыть
		 */
		$this->cli->rmdir($this->path['dirname'] . "/updates");
		if (!empty($sql)) {
			Connect::$db->exec($sql);
		}
		return [
			'output' => 'Updates is complete succsessfull!'
		];
	}
	/**
	 * Возвращает md5 суммы содержимого файла, с нормализацией переводов строк.
	 *
	 * @param string $filename Путь к файлу
	 * @return string Пустая строка если файл не найден, иначе md5
	 */
	private function getFilesum($filename)
	{
		if (!file_exists($filename)) {
			return '';
		}
		$str = file_get_contents($filename);
		$str = str_replace("\r\n", "\n", $str);
		return md5($str);
	}
	/**
	 * Сравнивает список файлов из JSON md5-конфигурации с фактическими файлами
	 * в рабочем дереве и возвращает список отличий.
	 *
	 * @param string $fileMD5 Путь к JSON-файлу с описанием файлов и md5
	 * @param bool $includeRemoved Включать ли отсутствующие файлы в вывод
	 * @return array Ассоциативный массив с ключами 'filename','output','files'
	 */
	private function getDiff(string $fileMD5, bool $includeRemoved = false)
	{
		if (!is_file($fileMD5)) {
			return [
				'output' => ''
			];
		}
		$files = [];
		$output = "\n";
		$jdata = json_decode(file_get_contents($fileMD5), true);
		if (empty($jdata['files'])) {
			return [
				'output' => ''
			];
		}
		foreach ($jdata['files'] as $value) {
			/*
			 * 1 - совпадает
			 * 2 - не совпадает
			 * 3 - нет файла
			 */
			$status = 3;
			$filename = Connect::$projectDev['root'] . '/' . $value['file'];
			if (!is_file($filename)) {
				$files[] = [
					'file' => $value['file'],
					'md5' => "",
					'status' => $status
				];
				if ($includeRemoved === true) {
					$output .= "{$value['file']}\n";
				}
				continue;
			}

			$md5sum = $this->getFilesum($filename);
			$status = ($md5sum == $value['md5']) ? 1 : 2;

			if ($status == 1) {
				continue;
			}

			$files[] = [
				'file' => $value['file'],
				'md5' => $md5sum,
				'status' => $status
			];
			$output .= "{$value['file']}\n";
		}
		$output = trim($output);
		return [
			'filename' => $fileMD5,
			'output' => $output,
			'files' => $files,
		];
	}
	/**
	 * Формирует SQL-скрипт для обновления структуры/данных таблиц на основе
	 * сравнения текущей версии и релизного md5-файла.
	 *
	 * @param string $fileMD5 Путь к md5-файлу релиза
	 * @return string SQL-скрипт для выполнения (пустая строка при отсутствии изменений)
	 */
	private function getTablesUpdates($fileMD5): string
	{
		if (!is_file($fileMD5)) {
			return '';
		}
		$files = [];
		$jdata = json_decode(file_get_contents($this->filename), true);
		$jrelease = json_decode(file_get_contents($fileMD5), true);
		if (empty($jdata['db']) || empty($jrelease['db'])) {
			return '';
		}

		$files = $this->getTablesDiff($jrelease['db'], $jdata['db'])['diff'];
		if (empty($files)) {
			return '';
		}

		/*
		 * Данные текущих таблиц, сверяем с md5 текущей версии
		 * Затем, сравниваем md5 массивов jdata (текущая версия) и jcurrent (текущие реальные файлы бд).
		 * Если расхождение - то disallow
		 * Если схождение - находим расхождение с jrelease - и уже его в обновление
		 */
		$jcurrent = [];
		$new = [];
		foreach ($files as $value) {
			if ($value['status'] == 2) {
				$table = $this->getTablesStructure($value['table']);
				$value['md5'] = $table['md5'];
				$jcurrent[] = $value;
			} elseif ($value['status'] == 3) {
				$new[] = $value['table'];
			}
		}

		/*
		 * allowed		$db['match']
		 * disallowed	$db['diff'];
		 */
		$db = $this->getTablesDiff($jcurrent, $jdata['db'], true);
		$allowed = array_column($db['match'], 'table');
		$disallowed = array_column($db['diff'], 'table');
		$columns = [
			'add' => [],
			'update' => []
		];
		if ($disallowed) {
			$this->cli->br();
			$this->cli->warning("Disallowed db-tables:\n" . implode("\n", $disallowed));
		}
		if ($new) {
			$this->cli->br();
			$this->cli->success("New db-tables:\n" . implode("\n", $new));
		}
		if ($allowed) {
			$this->cli->br();
			$this->cli->success("Allowed db-tables:\n" . implode("\n", $allowed));
		}
		$str = '';
		if (!empty($new)) {
			foreach ($new as $value) {
				$filename = $this->path['dirname'] . '/updates/packages/WeppsAdmin/Updates/files/db/' . $value . '-table.sql';
				if (!file_exists($filename)) {
					$this->cli->br();
					$this->cli->error("no sql-file for new table {$value}");
					exit();
				}
				$str .= str_replace("\\\'", "'", file_get_contents($filename)) . "\n";
			}
			if (!empty($str)) {
				$str .= "alter table s_Config auto_increment = 0;\nalter table s_ConfigFields auto_increment = 0;\n\n";
			}
		}

		/*
		 * Столбцы в таблицах
		 */
		foreach ($files as $value) {
			if (!in_array($value['table'], $allowed)) {
				continue;
			}
			$table = $this->getTablesStructure($value['table']);
			$sql = "show columns from {$value['table']}";
			$res = Connect::$instance->fetch($sql);
			$columnsRelease = array_keys($value['columns']);
			$columnsCurrent = array_column($res, 'Field');

			/*
			 * Новый столбец
			 */
			$arr = array_diff($columnsRelease, $columnsCurrent);
			if (!empty($arr)) {
				$filedata = '';
				foreach ($arr as $v) {
					$col = $value['columns'][$v];
					$alterTable = self::getTableAlter($col);
					$columns['add'][] = "{$value['table']}.{$v}";
					$s = "alter table {$value['table']} add {$col['column']['Field']} {$col['column']['Type']} {$alterTable['null']} {$alterTable['default']} {$alterTable['extra']}";
					$str .= trim($s) . ";\n";
					$filename = $this->path['dirname'] . '/updates/packages/WeppsAdmin/Updates/files/db/' . $value['table'] . '-table.sql';
					if (!file_exists($filename)) {
						$this->cli->br();
						$this->cli->error("no sql-file for new column {$value['table']}");
						exit();
					}
					if (empty($filedata)) {
						$filedata = str_replace("\\\'", "'", file_get_contents($filename));
					}
					$matches = [];
					preg_match("/(.+)into s_ConfigFields(.+),'$v',(.+)/", $filedata, $matches);
					$str .= $matches[0] . "\n";
				}
			}

			/*
			 * Обновить
			 */
			$arr = array_intersect($columnsRelease, $columnsCurrent);
			if (!empty($arr)) {
				foreach ($arr as $v) {
					$col = $value['columns'][$v];
					foreach ($res as $v2) {
						if ($v2['Field'] == $v) {
							$md5 = md5(json_encode($v2, JSON_UNESCAPED_UNICODE));
							if ($md5 == $col['md5']) {
								continue;
							}
							$alterTable = self::getTableAlter($col);
							$columns['update'][] = "{$value['table']}.{$v}";
							$s = "alter table {$value['table']} change {$col['column']['Field']} {$col['column']['Field']} {$col['column']['Type']} {$alterTable['null']} {$alterTable['default']} {$alterTable['extra']}";
							$str .= trim($s) . ";\n";
						}
					}
				}
			}

			/*
			 * Удалить
			 * Пока не будем использовать
			 * Возможно при определенном флаге можно
			 */
			$arr = array_diff($columnsCurrent, $columnsRelease);
			if (!empty($arr)) {
				foreach ($arr as $v) {
					$columns['delete'][] = "{$value['table']}.{$v}";
					#$str .= "alter table {$value['table']} drop {$v};\n";
				}
			}
		}
		$this->cli->put(json_encode([
			'disallowed' => $disallowed,
			'new' => $new,
			'allowed' => $allowed,
			'allowed-add' => @$columns['add'],
			'allowed-update' => @$columns['update'],
			'need-delete' => @$columns['delete'],
		], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), $this->path['dirname'] . "/log-db.conf");
		return $str;
	}
	/**
	 * Упаковывает указанные файлы в архив с помощью 7zip (вызов внешней утилиты).
	 *
	 * @param string $archive Путь к создаваемому архиву
	 * @param string $path Маска/путь файлов для упаковки
	 * @return bool Всегда возвращает true при завершении команды
	 */
	private function zip(string $archive, string $path): bool
	{
		if (file_exists($archive)) {
			$this->cli->rmfile($archive);
		}
		$cmd = "7z a -tzip {$archive} {$path}";
		$this->cli->cmd($cmd, true);
		return true;
	}
	/**
	 * Выполняет shell-команду через CLI helper и возвращает результат.
	 *
	 * @param string $cmd Команда для выполнения
	 * @return array Результат выполнения (вывод и коды)
	 */
	private function command(string $cmd): array
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
			$cmd = str_replace("\\", "/", $cmd);
		}
		return $this->cli->cmd($cmd, true);
	}
	/**
	 * Собирает структуру таблицы (CREATE TABLE + insert ignore в s_Config/s_ConfigFields)
	 * и возвращает её SQL-представление и md5 суммы.
	 *
	 * @param string $table Имя таблицы
	 * @return array Массив с ключами 'md5' и 'sql'
	 */
	private function getTablesStructure(string $table)
	{
		/*
		 * Структура бд
		 */
		$sql = "SHOW CREATE TABLE $table";
		$res = Connect::$instance->fetch($sql);
		$str = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $res[0]['Create Table']) . ";\n\n";
		$md5 = self::getTablesMD5($str);

		/*
		 * Конфиг
		 */
		$sql = "select * from s_Config where TableName = '$table'";
		$res = Connect::$instance->fetch($sql);
		unset($res[0]['Id']);
		$arr = AdminUtils::query($res[0]);
		$str .= "insert ignore into s_Config {$arr['insert']}\n\n";

		/*
		 * Конфиг полей
		 */
		$sql = "select * from s_ConfigFields where TableName = '$table'";
		$res = Connect::$instance->fetch($sql);
		foreach ($res as $value) {
			$arr = AdminUtils::query($value);
			$str .= "insert ignore into s_ConfigFields {$arr['insert']}\n";
		}
		$str = preg_replace("/values \('(\d+)'/", 'values (null', $str);
		return [
			'md5' => $md5,
			'sql' => $str
		];
	}
	/**
	 * Нормализует SQL-строку структуры таблицы и возвращает md5 контрольную сумму.
	 *
	 * @param string $string Входная SQL-строка
	 * @return string MD5 контрольная сумма
	 */
	private function getTablesMD5(string $string)
	{
		$string = trim($string);
		$string = str_replace("\r\n", "\n", $string);
		$string = str_replace('COLLATE=', 'COLLATE ', $string);
		$string = preg_replace("/COLLATE ([\d\_a-zA-Z]+)/", 'COLLATE RPL1', $string);
		$string = preg_replace("/CHARSET=([\d\_a-zA-Z]+)/", 'CHARSET=RPL2', $string);
		$string = preg_replace("/AUTO_INCREMENT=([\d\_a-zA-Z]+)/", 'AUTO_INCREMENT=0', $string);
		return md5($string);
	}
	/**
	 * Сравнивает два набора описаний таблиц и возвращает различия и совпадения.
	 *
	 * @param array $db1 Описание релизной версии таблиц
	 * @param array $db2 Описание текущей версии таблиц
	 * @param bool $matches Включать ли совпадающие таблицы в выход
	 * @return array Массив с ключами 'diff','match','new'
	 */
	private function getTablesDiff($db1, $db2, $matches = false)
	{
		$jdataMD5 = array_column($db2, 'md5');
		$jdataTables = array_column($db2, 'table');

		/*
		 * 2 - структуру таблицы надо обновить (возможно и данные, если есть),
		 * 3 - такой таблицы нет в тек.версии
		 */
		$status = 0;
		$files = [];
		$files2 = [];
		foreach ($db1 as $value) {
			if (!in_array($value['md5'], $jdataMD5)) {
				$status = (in_array($value['table'], $jdataTables)) ? 2 : 3;
				$files[] = [
					'md5' => $value['md5'],
					'status' => $status,
					'table' => $value['table'],
					'columns' => $value['columns']
				];
			} elseif ($matches == true) {
				$files2[] = [
					'md5' => $value['md5'],
					'status' => 0,
					'table' => $value['table'],
					'columns' => $value['columns']
				];
			}
		}
		return [
			'diff' => $files,
			'match' => $files2,
			'new' => []
		];
	}
	/**
	 * Формирует часть SQL для ALTER TABLE на основе описания столбца.
	 *
	 * @param array $col Массив описания столбца (структура как в s_ConfigFields)
	 * @return array Массив с ключами 'null','default','extra'
	 */
	private function getTableAlter(array $col): array
	{
		return [
			'null' => ($col['column']['Null'] == 'NO') ? 'not null' : '',
			'default' => (!empty($col['column']['Default'])) ? "default '{$col['column']['Default']}'" : '',
			'extra' => (!empty($col['column']['Extra'])) ? $col['column']['Extra'] : ''
		];
	}
}