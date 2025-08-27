<?php
namespace WeppsCore;
class Language
{
	/**
	 * Текущий язык раздела
	 * ($langLink - например /en/)
	 *
	 * @param string $langLink
	 * @return array
	 */
	public static function getLanguage($langLink = null)
	{

		if ($langLink != '/') {
			$sql = "select * from s_NGroupsLang where DisplayOff='0' and LinkDirectory=? or LinkDirectory='/' order by Priority desc limit 2";
			$langData = Connect::$instance->fetch($sql, [$langLink]);
		} else {
			$sql = "select * from s_NGroupsLang where DisplayOff='0' and LinkDirectory='/' order by Priority desc limit 2";
			$langData = Connect::$instance->fetch($sql);
		}

		/* $langLink = ($langLink != '/') ? "LinkDirectory='" . $langLink . "' or" : "";
			  $sql = "select * from s_NGroupsLang where DisplayOff='0' and {$langLink} LinkDirectory='/' order by Priority desc limit 2";
			  $langData = Connect::$instance->fetch ( $sql ); */
		$url = "";
		if (!empty($_SERVER['REQUEST_URI'])) {
			$url = $_SERVER['REQUEST_URI'];
		}
		if (count($langData) == 1) {
			return [
				'id' => $langData[0]['Id'],
				'defaultId' => $langData[0]['Id'],
				'default' => 1,
				'interface' => "Lang" . substr($langData[0]['Name'], 0, 2),
				'interfaceDefault' => "Lang" . substr($langData[0]['Name'], 0, 2),
				'link' => "",
				'url' => $url
			];
		} else {
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
	}

	/**
	 * Перевод для шаблонов (список "Перевод")
	 *
	 * @param array $langData
	 * @param number $backOffice
	 * @return array
	 */
	public static function getMultilanguage($langData, $backOffice = 0)
	{
		$ppsInterface = [];
		$condition = ($backOffice == 0) ? "Category='front'" : "Category='back'";
		$interfaceLangs = ($langData['default'] == 1) ? $langData['interface'] : $langData['interface'] . "," . $langData['interfaceDefault'];
		foreach (Connect::$instance->fetch("select Name," . $interfaceLangs . " from s_Lang where $condition order by Name") as $v) {
			$ppsInterface[$v['Name']] = ($v[$langData['interface']] != '') ? $v[$langData['interface']] : $v[$langData['interfaceDefault']];
		}
		return $ppsInterface;
	}
	/**
	 * Перевод элементов списка данных
	 * 
	 * @param array $data
	 * @param array $scheme
	 * @param array $lang
	 * @return array
	 */
	public static function getRows($data = [], $scheme = [], $lang = [])
	{
		if (empty($lang) || @$lang['id'] == 1 || !isset($scheme['TableId']) || !isset($scheme['LanguageId'])) {
			return $data;
		}
		$res = Utils::array($data);
		$resKeys = implode(",", array_keys($res));
		if ($resKeys == "") {
			return $data;
		}
		$sql = "select * from {$scheme['TableId'][0]['TableName']} where TableId in ({$resKeys}) and LanguageId='" . @$lang['id'] . "' and DisplayOff=0";
		$res2 = Connect::$instance->fetch($sql);
		if (count($res2) == 0)
			return $data;
		$resParall = Utils::array($res2, 'TableId');
		$resParall2 = [];
		foreach ($res as $key => $value) {
			if (!empty($resParall[$key]['Id'])) {
				$resParall2[$key] = $resParall[$key];
				foreach ($value as $k => $v) {
					$resParall2[$key][$k] = (!isset($resParall[$key][$k]) || $resParall[$key][$k] == '') ? $v : $resParall[$key][$k];
				}
				$resParall2[$key]['Id'] = $value['Id'];
				if (isset($value['Template']))
					$resParall2[$key]['Template'] = $value['Template'];
				if (isset($value['NGroup']))
					$resParall2[$key]['NGroup'] = $value['NGroup'];
				if (isset($value['ParentDir']))
					$resParall2[$key]['ParentDir'] = $value['ParentDir'];
				if (isset($value['Alias']))
					$resParall2[$key]['Alias'] = $value['Alias'];
				if (isset($value['Url']))
					$resParall2[$key]['Url'] = $value['Url'];
			} else {
				$resParall2[$key] = $value;
			}
		}
		return array_merge($resParall2);
	}
}