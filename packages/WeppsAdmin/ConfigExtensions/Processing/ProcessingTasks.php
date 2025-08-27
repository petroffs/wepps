<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Connect;

class ProcessingTasks {
	private $root;
	public function __construct() {
		$this->root = Connect::$projectDev['root'];
	}
	public function removeFiles() {
		/*
		 * Получить список всех файлов в папке /pic/,/files/lists/
		 * Составить список для дальнеших действий
		 * Составить список из таблицы s_Files
		 * Проверить расхождение и на основе этого
		 * Составить список тех файлов, которых нет в s_Files
		 * Удалить все файлы, которые попадут в этот список
		 * Удалить все что в /Addons/Forms/uploads, кроме .htaccess
		 */
		
		/*
		 * Данные в папках
		 */
		$output = [];
		exec("find {$this->root}/files/lists/ {$this->root}/pic/ -name '*.*' ! -name '*.htaccess'",$output);
		$arr1 = [];
		foreach ($output as $value) {
			$str = substr($value, strpos($value, "/files/"));
			$arr1[$str][] = $value;
		}
		$arr1keys = array_keys($arr1);
		
		/*
		 * Данные в базе
		 */
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
		if (count ( $diff ) != 0) {
			foreach ( $diff as $value ) {
				if (isset ( $arr1 [$value] )) {
					foreach ( $arr1 [$value] as $filename ) {
						unlink ( $filename );
						$i++;
					}
				}
			}
		}
		echo "\n$i files deleted\n";
	}
}