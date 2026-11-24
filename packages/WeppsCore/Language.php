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

namespace WeppsCore;

use WeppsAdmin\Lists\Lists;
/**
 * Класс Language
 *
 * Этот класс предоставляет функциональность для работы с языками и переводами.
 */
class Language
{
	/**
	 * Получает текущий язык раздела
	 *
	 * Этот метод получает информацию о текущем языке раздела на основе ссылки языка.
	 *
	 * @param string|null $langLink Ссылка языка (например /en/)
	 * @return array Возвращает массив с информацией о языке
	 */
	public static function getLanguage($langLink = null)
	{

		if ($langLink != '/') {
			$sql = "select * from s_NGroupsLang where IsHidden='0' and LinkDirectory=? or LinkDirectory='/' order by Priority desc limit 2";
			$langData = Connect::$instance->fetch($sql, [$langLink]);
		} else {
			$sql = "select * from s_NGroupsLang where IsHidden='0' and LinkDirectory='/' order by Priority desc limit 2";
			$langData = Connect::$instance->fetch($sql);
		}

		/* $langLink = ($langLink != '/') ? "LinkDirectory='" . $langLink . "' or" : "";
			  $sql = "select * from s_NGroupsLang where IsHidden='0' and {$langLink} LinkDirectory='/' order by Priority desc limit 2";
			  $langData = Connect::$instance->fetch ( $sql ); */
		$url = "";
		if (!empty($_SERVER['REQUEST_URI'])) {
			$url = $_SERVER['REQUEST_URI'];
		}
		if (count($langData) === 1) {
			return [
				'id' => $langData[0]['Id'],
				'defaultId' => $langData[0]['Id'],
				'default' => 1,
				'interface' => "Lang" . substr($langData[0]['Name'], 0, 2),
				'interfaceDefault' => "Lang" . substr($langData[0]['Name'], 0, 2),
				'link' => "",
				'url' => $url
			];
		}
        return [
				'id' => $langData[0]['Id'],
				'defaultId' => $langData[1]['Id'],
				'default' => 0,
				'interface' => "Lang" . substr($langData[0]['Name'], 0, 2),
				'interfaceDefault' => "Lang" . substr($langData[1]['Name'], 0, 2),
				'link' => substr($langData[0]['LinkDirectory'], 0, -1),
				'url' => $url
			];
	}

	/**
	 * Получает переводы для шаблонов
	 *
	 * Этот метод получает переводы для шаблонов из списка "Перевод".
	 *
	 * @param array $langData Информация о языке
	 * @param int $backOffice Флаг для определения, является ли это административной частью
	 * @return array Возвращает массив с переводами
	 */
	public static function getMultilanguage($langData, $backOffice = 0)
	{
		$ppsInterface = [];
		$condition = ($backOffice == 0) ? "Category='front'" : "Category='back'";
		$interfaceLangs = ($langData['default'] == 1) ? $langData['interface'] : $langData['interface'] . "," . $langData['interfaceDefault'];
		$res = Connect::$instance->fetch("select Name," . $interfaceLangs . " from s_Lang where Category=? order by Name", [$backOffice == 0 ? 'front' : 'back']);
		foreach ($res as $v) {
			$ppsInterface[$v['Name']] = ($v[$langData['interface']] != '') ? $v[$langData['interface']] : $v[$langData['interfaceDefault']];
		}
		return $ppsInterface;
	}
	/**
	 * Получает переводы элементов списка данных
	 *
	 * Этот метод получает переводы элементов списка данных на основе схемы и языка.
	 *
	 * @param array $data Данные для перевода
	 * @param array $scheme Схема данных
	 * @param array $lang Информация о языке
	 * @return array Возвращает массив с переведенными данными
	 */
	public static function getRows($data = [], $scheme = [], $lang = [])
	{
		if (empty($lang) || @$lang['id'] == 1 || !isset($scheme['TableId']) || !isset($scheme['LanguageId'])) {
			return $data;
		}
		$res = Utils::array($data);
		$baseIds = array_keys($res);
		if ($baseIds === []) {
			return $data;
		}
		$resKeys = implode(',', $baseIds);
		$tableName = $scheme['TableId'][0]['TableName'] ?? '';
		if ($tableName == '') {
			return $data;
		}

		$fileFields = [];
		foreach ($scheme as $fieldName => $fieldRows) {
			if (!is_array($fieldRows) || $fieldRows === []) {
				continue;
			}
			$fieldConfig = reset($fieldRows);
			if (!is_array($fieldConfig) || empty($fieldConfig['Type'])) {
				continue;
			}
			if (strtolower(explode('::', $fieldConfig['Type'], 2)[0]) === 'file') {
				$fileFields[$fieldName] = true;
			}
		}
		$currentLangId = (int) (@$lang['id'] ?? 0);
		$defaultLangId = (int) (@$lang['defaultId'] ?? 0);
		$res2 = Connect::$instance->fetch(
			"select * from {$tableName} where TableId in ({$resKeys}) and LanguageId='{$currentLangId}' and IsHidden=0"
		);
		if (empty($res2)) {
			return $data;
		}
		$rowIdsForFiles = $baseIds;
		foreach ($res2 as $rowValue) {
			if (!empty($rowValue['Id'])) {
				$rowIdsForFiles[] = (int) $rowValue['Id'];
			}
		}
		$defaultRowsByTableId = [];
		if ($defaultLangId > 0 && $defaultLangId !== $currentLangId) {
			foreach (Connect::$instance->fetch(
				"select * from {$tableName} where TableId in ({$resKeys}) and LanguageId='{$defaultLangId}' and IsHidden=0"
			) as $defaultRow) {
				if (!empty($defaultRow['Id'])) {
					$rowIdsForFiles[] = (int) $defaultRow['Id'];
					$defaultRowsByTableId[(int) $defaultRow['TableId']] = $defaultRow;
				}
			}
		}
		//UtilsWepps::debug($tableName);
		// if ($tableName=='Sliders') {
		// 	UtilsWepps::debug($resFiles,21);
		// }
		$filesByRowId = [];
		if ($fileFields !== []) {
			$rowIdsForFiles = array_values(array_unique(array_map('intval', $rowIdsForFiles)));
			$fileIds = implode(',', $rowIdsForFiles);
			$resFiles = Connect::$instance->fetch(
				"select TableNameId, TableNameField, FileUrl from s_Files where TableName = ? and TableNameId in ({$fileIds}) order by Priority",
				[$tableName],
				'group'
			);

			foreach ($resFiles as $rowId => $rowFiles) {
				$rowId = (int) $rowId;
				if ($rowId <= 0) {
					continue;
				}
				foreach ($rowFiles as $fileValue) {
					$fieldName = $fileValue['TableNameField'] ?? '';
					if ($fieldName === '') {
						continue;
					}
					$filesByRowId[$rowId][$fieldName][] = $fileValue;
				}
			}
		}

		$fileFieldNames = array_keys($fileFields);
		$resParall = Utils::array($res2, 'TableId');
		$out = [];
		foreach ($res as $baseId => $value) {
			$translated = $resParall[$baseId] ?? null;
			if (empty($translated['Id'])) {
				$out[] = $value;
				continue;
			}
			$row = $translated;
			foreach ($value as $k => $v) {
				if (empty($row[$k]) || $row[$k] == '0.00') {
					$row[$k] = $v;
				}
			}
			foreach (['Id', 'Template', 'NGroup', 'ParentDir', 'Alias', 'Url'] as $keepKey) {
				if (isset($value[$keepKey])) {
					$row[$keepKey] = $value[$keepKey];
				}
			}
			$currentRowId = (int) $translated['Id'];
			$baseRowId = (int) $baseId;
			$defaultRowId = (int) ($defaultRowsByTableId[$baseId]['Id'] ?? 0);
			$candidates = array_values(array_unique([$currentRowId, $baseRowId, $defaultRowId]));
			$rowFiles = [];
			foreach ($fileFieldNames as $fieldName) {
				$filesForCurrent = [];
				foreach ($candidates as $rowId) {
					if ($rowId <= 0) {
						continue;
					}
					$filesForCurrent = $filesByRowId[$rowId][$fieldName] ?? [];
					if ($filesForCurrent !== []) {
						break;
					}
				}
				if ($filesForCurrent === []) {
					continue;
				}
				$rowFiles = array_merge($rowFiles, $filesForCurrent);
				$urls = [];
				foreach ($filesForCurrent as $file) {
					if (!empty($file['FileUrl'])) {
						$urls[] = $file['FileUrl'];
					}
				}
				if ($urls !== []) {
					$row[$fieldName . '_FileUrl'] = implode(':::', $urls);
				}
			}
			if ($rowFiles !== []) {
				$row['Files'] = $rowFiles;
				$row['files'] = $rowFiles;
			}
			$out[] = $row;
		}
		return $out;
	}
	public static function copyListItemLang($list = "", $id = 0, $path = "lists", $lang = 0)
	{
		// $list подставляется в SQL как имя таблицы — белый список закрывает SQL-инъекции
		if (preg_match('/^[A-Za-z0-9_]+$/D', (string) $list) !== 1) {
			return ['html' => ''];
		}
		$id = (int) $id;
		$lang = (int) $lang;
		$list = htmlspecialchars((string) $list, ENT_QUOTES);
		$sql = "select * from $list where Id=?";
		$res2 = Connect::$instance->fetch($sql, [$id]);

		if (!empty($res2[0]['TableId'])) {
			$id = $res2[0]['TableId'];
		}

		if ($lang === 1) {
			$lang = 0;
			$condition = "TableId=0 and Id=? and LanguageId=?";
		} else {
			$condition = "TableId=? and LanguageId=?";
		}
		$sql = "select * from {$list} where $condition";
		$res = Connect::$instance->fetch($sql, [$id, $lang]);
		if (!empty($res[0]['Id'])) {
			$href = "'/_wepps/lists/" . htmlspecialchars((string) $list, ENT_QUOTES) . "/{$res[0]['Id']}/'";
			if ($path == 'navigator') {
				$href = "'/_wepps/navigator/" . htmlspecialchars((string) $res[0]['Url'], ENT_QUOTES) . "/'";
			}
			$js = "
						<script>
						location.href={$href};
						</script>
					";
			return [
				'html' => $js
			];
		}
		return Lists::copyListItem($list, $id, $path, $lang);
	}
}