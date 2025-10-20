<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WeppsCore\Connect;
use WeppsCore\Utils;

class ProcessingTasks
{
	private $root;
	public function __construct()
	{
		$this->root = Connect::$projectDev['root'];
	}
	public function removeFiles(): void
	{
		self::removeDatabaseRows();
		
		$dirs = [
			$this->root . '/files/lists/',
			$this->root . '/pic/'
		];
		$files = self::findFiles($dirs);
		foreach ($files as $value) {
			$str = substr($value, strpos($value, "/files/"));
			$arr1[$str][] = $value;
		}
		$arr1keys = array_keys($arr1);

		$sql = "select Id,FileUrl from s_Files";
		$res = Connect::$instance->fetch($sql);
		$arr2 = [];
		foreach ($res as $value) {
			$arr2[] = $value['FileUrl'];
		}

		/*
		 * Расхождение
		 */
		$diff = array_diff($arr1keys, $arr2);

		/*
		 * Удаление файлов, не записанных в s_Files
		 */
		$i = 0;
		if (count($diff) != 0) {
			foreach ($diff as $value) {
				if (isset($arr1[$value])) {
					foreach ($arr1[$value] as $filename) {
						echo $filename."\n";
						unlink($filename);
						$i++;
					}
				}
			}
		}
		echo "\n$i files deleted\n";
	}
	private function findFiles($dirs, $exclude = ['.htaccess']): array
	{
		$files = [];
		foreach ((array) $dirs as $dir) {
			if (!is_dir($dir))
				continue;

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
			);

			foreach ($iterator as $file) {
				if ($file->isFile() && !in_array($file->getFilename(), $exclude)) {
					$files[] = str_replace("\\", "/", $file->getPathname());
				}
			}
		}
		return $files;
	}
	private function removeDatabaseRows(): bool
	{
		// Получаем все уникальные имена таблиц
		$result = Connect::$instance->fetch("SELECT DISTINCT TableName,TableNameField FROM s_Files WHERE TableName != ''");
		foreach ($result as $value) {
			$table = $value['TableName'];
			$tableField = $value['TableNameField'];
			// Проверяем существует ли таблица
			$check = Connect::$instance->fetch("SELECT COUNT(*) as cnt FROM information_schema.tables 
                                    WHERE table_name = ? AND table_schema = DATABASE()", [$table]);
			$exists = $check[0]['cnt'];
			if (!$exists) {
				// Удаляем если таблицы нет
				Connect::$instance->query("DELETE FROM s_Files WHERE TableName = ?", [$table]);
			} else {
				// Удаляем "осиротевшие" записи
				$sql = "DELETE sf FROM s_Files sf 
					LEFT JOIN {$table} t ON sf.TableNameId = t.Id  and sf.TableName = '{$table}'
					WHERE sf.TableName = ? AND t.Id IS NULL";
				Connect::$instance->query($sql, [$table]);
			}
		}
		return true;
	}
}