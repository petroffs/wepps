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

namespace WeppsAdmin\Lists;
use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Smarty;
use WeppsCore\Utils;
use WeppsCore\Connect;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsAdmin\Admin\Admin;
use WeppsCore\Data;
use WeppsCore\TextTransforms;
use WeppsCore\Validator;

if (!session_id() && php_sapi_name() !== 'cli') {
	session_start();
}

class Lists
{
	private $content;
	private $get;
	public $headers;
	public function __construct(TemplateHeaders &$headers)
	{
		$smarty = Smarty::getSmarty();
		$headers->js("/packages/WeppsAdmin/Lists/Lists.{$headers::$rand}.js");
		$headers->css("/packages/WeppsAdmin/Lists/Lists.{$headers::$rand}.css");
		$this->get = Utils::trim($_GET);
		$weppsurl = "/" . htmlspecialchars((string) $_GET['weppsurl'], ENT_QUOTES);
		$weppsurlEx = explode("/", trim($weppsurl, '/'));
		$tpl2 = "../Admin/AdminError.tpl";
		$perm = Admin::getPermissions(Connect::$projectData['user']['UserPermissions']);
		$translate = Admin::getTranslate();
		$smarty->assign('translate', $translate);

		/*
		 * Списки с учетом прав доступа
		 */
		$fcond = "'" . implode("','", $perm['lists']) . "'";
		$sql = "select * from s_Config as t where TableName in ($fcond) order by t.Category,t.Priority";
		$res = Connect::$instance->fetch($sql);
		/* $str = "";
		foreach ($res as $value) {
			$str .= "union (select '{$value['TableName']}' as TableName,count(*) as `Rows`,  (select count(*) from s_ConfigFields as cf where cf.TableName='{$value['TableName']}') as Fields from {$value['TableName']} as t)\n";
		}
		$str = trim($str, "union ");
		$stat = Connect::$instance->fetch($str, array(), 'group'); */
		$arr = [];
		foreach ($res as $value) {
			/* $value['RowsCount'] = $stat[$value['TableName']][0]['Rows'];
			$value['FieldsCount'] = $stat[$value['TableName']][0]['Fields']; */
			$arr[$value['Category']][] = $value;
		}
		$smarty->assign('lists', $arr);
		$smarty->assign('listsNavTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsNav.tpl'));
		if ($weppsurl == '/lists/') {
			/*
			 * Список списков
			 */
			$tpl2 = "Lists.tpl";
			$content['MetaTitle'] = "Все списки — Списки данных";
			$content['Name'] = "Все списки";
			$content['NameNavItem'] = "Списки данных";
			$smarty->assign('content', $content);
			$smarty->assign('listsNavTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsNav.tpl'));
		} elseif (count($weppsurlEx) == 2 && in_array($weppsurlEx[1], $perm['lists'])) {
			/*
			 * Элементы списка
			 */
			$permConfig = Admin::getPermissions(Connect::$projectData['user']['UserPermissions'], array('list' => "s_Config"));
			$smarty->assign('permConfig', $permConfig['status']);
			/*
			 * Свойства списка (Конфигурация и поля)
			 */
			$sql = "select * from s_Config as t where TableName in ('{$weppsurlEx[1]}') order by t.Category";
			$listSettings = Connect::$instance->fetch($sql)[0];
			$listObj = new Data($weppsurlEx[1]);
			if (!empty($listSettings['ItemsFields'])) {
				$listObj->setFields($listSettings['ItemsFields']);
			}
			$listScheme = $listObj->getScheme();

			/*
			 * Настройки шаблона
			 */
			$tpl2 = "ListsItems.tpl";
			$content['MetaTitle'] = "{$listSettings['Name']} — Списки данных";
			$content['Name'] = "{$listSettings['Name']}";
			$content['NameNavItem'] = "Списки данных";
			$smarty->assign('content', $content);
			$smarty->assign('listScheme', $listScheme);
			$smarty->assign('listSettings', $listSettings);

			/*
			 * Список
			 */
			$listSettings['ItemsOnPage'] = ($listSettings['ItemsOnPage'] != '') ? $listSettings['ItemsOnPage'] : 100;
			$listSettings['IsOrderBy'] = ($listSettings['IsOrderBy'] != '') ? $listSettings['IsOrderBy'] : 'Priority';
			if (isset($this->get['orderby']) && $this->get['orderby'] != '') {
				$listSettings['IsOrderBy'] = $this->get['orderby'];
			}
			$orderField = str_ireplace(" desc", "", $listSettings['IsOrderBy']);
			$page = (isset($this->get['page'])) ? $this->get['page'] : 1;

			$listCondition = "";

			/*
			 * Дополнения Actions
			 */

			if (isset($listSettings['ActionShow']) && $listSettings['ActionShow'] != '') {
				$addAction = str_replace(".php", "", $listSettings['ActionShow']);
				$addActionClass = "\WeppsAdmin\\Lists\\Actions\\{$addAction}";
				// Имя класса — из внутренней конфигурации списка (поле ActionShow в s_Config)
				/** @psalm-suppress TaintedInput */
				$addActionRequest = new $addActionClass(array('listSettings' => $listSettings, 'listScheme' => $listScheme));
				$listCondition = $addActionRequest->condition;
				if (!empty($addActionRequest->fields)) {
					if (!empty($addActionRequest->scheme)) {
						$listScheme = $addActionRequest->scheme;
						$smarty->assign('listScheme', $listScheme);
					}
					$listObj->setFields($addActionRequest->fields);
					$listScheme = $listObj->getScheme(1);
					$smarty->assign('listScheme', $listScheme);
				}
			}

			if (isset($this->get['field']) && isset($this->get['search'])) {
				$listCondition = "t.{$this->get['field']} like '%{$this->get['search']}%'";
			} elseif (isset($this->get['field']) && isset($this->get['filter'])) {
				if (strstr($listScheme[$this->get['field']][0]['FType'], 'select')) {
					/*
					 * Затем подсветка иконки, чтобы было ясно что данные отфильтрованы
					 */
					$listCondition = "t.{$this->get['field']} regexp '" . Connect::$instance->selectRegx($this->get['filter']) . "'";
				} else {
					$listCondition = "t.{$this->get['field']} = '{$this->get['filter']}'";
				}
			}
			$listObj->truncate = 160;
			$listItems = $listObj->fetch($listCondition, $listSettings['ItemsOnPage'], $page, "t." . $listSettings['IsOrderBy']);

			if (isset($listItems[0]['Id'])) {
				$smarty->assign('listItems', $listItems);
				$smarty->assign('paginator', $listObj->paginator);
				$smarty->assign('paginatorUrl', "/_wepps/lists/{$weppsurlEx[1]}/");
				$smarty->assign('paginatorTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Admin/Paginator/PaginatorLists.tpl'));
				$smarty->assign('orderField', $orderField);
				$headers->css("/packages/WeppsAdmin/Admin/Paginator/Paginator.{$headers::$rand}.css");
			}
			$smarty->assign('listsNavTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsNav.tpl'));
		} elseif (count($weppsurlEx) == 3) {
			/*
			 * Элемент списка
			 */
			$listForm = self::getListItemForm($headers, $weppsurlEx[1], $weppsurlEx[2]);
			$element = $listForm['element'];
			$listSettings = $listForm['listSettings'];
			$tpl2 = "ListsItem.tpl";
			$headers = &$listForm['headers'];

			if ($weppsurlEx[2] == 'add') {
				$content['MetaTitle'] = "Новый элемент — {$listSettings['Name']} — Списки данных";
				$content['Name'] = "Новый элемент";
				$element['Id'] = 'add';
				$content['NameNavItem'] = "Списки данных";
				$smarty->assign('listMode', 'CreateMode');
				foreach ($_GET as $key => $value) {
					if (isset($listForm['element'][$key]) && !empty($value)) {
						$listForm['element'][$key] = $value;
					}
				}
			} elseif (!empty($element)) {
				$content['MetaTitle'] = "{$element['Name']} — {$listSettings['Name']} — Списки данных";
				$content['Name'] = $element['Name'];
				$content['NameNavItem'] = "Списки данных";
				$smarty->assign('listMode', 'ModifyMode');
			} else {
				Exception::error404();
			}

			/*
			 * Вывод данных
			 */
			$smarty->assign('weppspath', 'lists');
			$smarty->assign('permFields', $listForm['permFields']);
			$smarty->assign('element', $listForm['element']);
			$smarty->assign('content', $content);
			$smarty->assign('listScheme', $listForm['listScheme']);
			$smarty->assign('listSettings', $listForm['listSettings']);
			$smarty->assign('tabs', $listForm['tabs']);
			$smarty->assign('controlsTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsItemControls.tpl'));
			if (isset($_SESSION['uploads']['list-data-form'])) {
				$smarty->assign('uploaded', $_SESSION['uploads']['list-data-form']);
			}
			$smarty->assign('listItemFormTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsItemForm.tpl'));
			$smarty->assign('listsNavTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsNav.tpl'));
		} else {
			Exception::error404();
		}
		$tpl = $smarty->fetch(__DIR__ . '/' . $tpl2);
		$smarty->assign('extension', $tpl);
	}

	/**
	 * @return (TemplateHeaders|array|mixed)[]
	 *
	 * @psalm-return array{permFields: mixed, tabs: array, element: mixed, listScheme: mixed, listSettings: mixed, headers: TemplateHeaders|mixed}
	 */
	public static function getListItemForm(TemplateHeaders &$headers, $list, $id): array
	{
		$translate = Admin::getTranslate();
		$perm = Admin::getPermissions(Connect::$projectData['user']['UserPermissions'], array('list' => $list));
		if ($perm['status'] == 0) {
			Exception::error404();
		}
		$permFields = Admin::getPermissions(Connect::$projectData['user']['UserPermissions'], array('list' => "s_ConfigFields"));

		/*
		 * Свойства списка (Конфигурация и поля)
		 */
		$sql = "select * from s_Config as t where TableName in ('{$list}') order by t.Category,t.Priority";
		$listSettings = Connect::$instance->fetch($sql)[0];
		$listObj = new Data($listSettings['TableName']);
		$listScheme = $listObj->getScheme();

		/*
		 * Табы
		 */
		$tabs = [];
		foreach ($listScheme as $value) {
			foreach ($value as $v) {
				if (!isset($tabs[$v['FGroup']])) {
					$tabs[$v['FGroup']] = (isset($translate[$v['FGroup']])) ? $translate[$v['FGroup']] : $v['FGroup'];
				}
			}
		}
		/*
		 * Настройки шаблона
		 */
		$headers->js("/packages/vendor/components/jqueryui/ui/i18n/datepicker-ru.js");
		$headers->css("/packages/vendor/xdan/jodit/build/jodit.min.css");
		$headers->js("/packages/vendor/xdan/jodit/build/jodit.min.js");
		$headers->css("/packages/WeppsAdmin/Lists/Lists.{$headers::$rand}.css");
		$headers->css("/packages/WeppsAdmin/Lists/ListsItem.{$headers::$rand}.css");
		// Настройки UI редактора — после jodit.min.css (перебивает его правила)
		$headers->css("/packages/WeppsAdmin/Lists/ListsItemJodit.{$headers::$rand}.css");
		$headers->js("/packages/WeppsAdmin/Lists/Lists.{$headers::$rand}.js");
		$headers->js("/packages/WeppsAdmin/Lists/ListsItem.{$headers::$rand}.js");

		$element = self::getListItem($listObj, $listScheme, $id)[0];
		/*
		 * Дополнения Actions
		 */
		if (isset($listSettings['ActionShowId']) && $listSettings['ActionShowId'] != '') {
			$addAction = str_replace(".php", "", $listSettings['ActionShowId']);
			$addActionClass = "\WeppsAdmin\\Lists\\Actions\\{$addAction}";
			// Имя класса — из внутренней конфигурации списка (поле ActionShowId в s_Config)
			/** @psalm-suppress TaintedInput */
			new $addActionClass(array('listSettings' => &$listSettings, 'listScheme' => &$listScheme, 'element' => &$element, 'headers' => &$headers));
		}
		return [
			'permFields' => $permFields['status'],
			'tabs' => $tabs,
			'element' => $element,
			'listScheme' => $listScheme,
			'listSettings' => $listSettings,
			'headers' => $headers
		];
	}

	public static function getListItem(&$obj, &$listScheme, $id)
	{
		if ($id == 'add') {
			$arr = array_fill_keys(array_keys($listScheme), '');
			$arr['Name'] = "Новый элемент";
			$element = array(0 => $arr);
		} elseif ((int) $id == 0) {
			Exception::error404();
		} else {
			$element = $obj->fetch($id);
		}
		if (!isset($element[0]['Id'])) {
			Exception::error404();
		}

		/*
		 * Файлы
		 */
		$sql = "select TableNameField,Id,Name,FileDescription,Priority,TableNameId,InnerName,TableName,FileDate,FileSize,FileExt,FileType,FileUrl
				from s_Files where TableName = '{$obj->tableName}' and TableNameId = '{$id}'
				order by Priority";
		$files = Connect::$instance->fetch($sql, [], 'group');
		if (count($files) != 0) {
			$element[0] = array_merge($element[0], $files);
		}

		/*
		 * Select|Select-multi
		 */
		foreach ($listScheme as $key => $value) {
			if (strstr($value[0]['FType'], 'remote')) {
				$ex = explode("::", $value[0]["FType"]);
				$ex[2] = (strstr($ex[2], ",")) ? substr($ex[2], 0, strpos($ex[2], ",")) : $ex[2];
				$tablename = $ex[1];

				/*
				 * Выбранные элементы
				 */
				$where = (!empty($element[0][$key])) ? "where Id in ({$element[0][$key]})" : "where Id in (0)";
				$sql = "select Id,{$ex[2]} from {$tablename} {$where} order by {$ex[2]}";
				$res = Connect::$instance->fetch($sql);
				$selected = [];
				foreach ($res as $k => $v) {
					$selected[$v['Id']] = "{$v[$ex[2]]} ({$v['Id']})";
				}
				$element[0]["{$key}_SelectChecked"] = $selected;
			} elseif (strstr($value[0]['FType'], 'select')) {
				$ex = explode("::", $value[0]["FType"]);
				$ex[2] = (strstr($ex[2], ",")) ? substr($ex[2], 0, strpos($ex[2], ",")) : $ex[2];
				$tablename = $ex[1];

				/*
				 * Выбранные элементы
				 */
				$where = (!empty($element[0][$key])) ? "where Id in ({$element[0][$key]})" : "where Id in (0)";
				$sql = "select Id,{$ex[2]} from {$tablename} {$where} order by {$ex[2]}";
				$res = Connect::$instance->fetch($sql);
				$selected = [];

				foreach ($res as $k => $v) {
					$selected[$v['Id']] = $v['Id'];
				}

				/*
				 * Список элементов условия
				 */
				$where = (!empty($ex[3])) ? "where t.{$ex[3]}" : "";
				$recurciveId = (isset($ex[4])) ? "concat(t2.Name,' (',t2.Id,')') as RecursiveName," : "";
				$where = str_replace("t.(", "(t.", $where);

				if (isset($ex[4])) {
					$sql = "select {$recurciveId}t.Id,concat(t.{$ex[2]},' (',t.Id,') ') as {$ex[2]} from {$tablename} as t
					left join {$ex[1]} as t2 on t2.Id = t.{$ex[4]}
					{$where} group by t.{$ex[2]} order by t2.Priority,{$ex[2]}";
					$res = Connect::$instance->fetch($sql, [], 'group');
					$options = [];
					$options[0] = "-";
					foreach ($res as $k => $v) {
						$options[$k] = [];
						foreach ($v as $v2) {
							$options[$k][$v2['Id']] = $v2[$ex[2]];
						}
					}

				} else {
					$sql = "select t.Id,concat(t.{$ex[2]},' (',t.Id,') ') as {$ex[2]} from {$tablename} as t
					{$where} order by {$ex[2]}";
					$res = Connect::$instance->fetch($sql);
					$options = [];
					$options[0] = "-";
					foreach ($res as $k => $v) {
						$options[$v['Id']] = $v[$ex[2]];
					}
				}

				$element[0]["{$key}_SelectOptions"] = $options;
				$element[0]["{$key}_SelectChecked"] = $selected;
				$element[0]["{$key}_Table"] = $tablename;

				$optionsCounter = count($options);
				if ($optionsCounter >= 20) {
					$optionsCounter = 20;
				} elseif ($optionsCounter <= 12) {
					$optionsCounter = 12;
				}
				$element[0]["{$key}_SelectOptionsSizeView"] = $optionsCounter;

			} elseif (strstr($value[0]['FType'], 'dbtable')) {
				$sql = "select TableName,Name from s_ConfigFields order by TableName";
				$res = Connect::$instance->fetch($sql, [], 'group');
				$options = [];
				$options[0] = "-";
				foreach ($res as $k => $v) {
					$options[$k] = $k;
				}
				$selected = [];
				if (isset($element[0][$key])) {
					$res = explode(',', $element[0][$key]);
					foreach ($res as $k => $v) {
						$selected[$v] = $v;
					}
				}
				$element[0]["{$key}_SelectOptions"] = $options;
				$element[0]["{$key}_SelectChecked"] = $selected;
				$element[0]["{$key}_Table"] = "s_Config";

				$optionsCounter = count($options);
				if ($optionsCounter >= 20) {
					$optionsCounter = 20;
				} elseif ($optionsCounter <= 12) {
					$optionsCounter = 12;
				}
				$element[0]["{$key}_SelectOptionsSizeView"] = $optionsCounter;

			} elseif (strstr($value[0]['FType'], 'properties') && $id != 'add') {
				$condition = "";
				$ex = explode("::", $value[0]["FType"]);
				if (!empty($ex[1])) {
					$condition = "and {$ex[1]}";
				}
				/*
				 * Характеристики (s_Properties)
				 */
				$sql = "select Id,Name from s_PropertiesGroups where IsHidden=0 $condition order by Priority";
				$res = Connect::$instance->fetch($sql);
				$options = [];
				$options[0] = "-";
				foreach ($res as $k => $v) {
					$options[$v['Id']] = $v['Name'];
				}
				$selected = [];
				$properties = [];
				$options2 = [];
				$selected2 = [];
				if (isset($element[0][$key]) && (int) $element[0][$key] != 0) {
					$selected[$element[0][$key]] = $element[0][$key];
					/*
					 * Свойства выбранной группы
					 */
					$sql = "select * from s_Properties where IsHidden=0 and PGroup in (0,'',{$element[0][$key]}) order by Priority";
					$res = Connect::$instance->fetch($sql);
					if (isset($res[0]['Id'])) {
						/*
						 * Значения полей
						 */
						$sql = "select Name,Id,PValue from s_PropertiesValues where IsHidden=0 and TableName = '{$value[0]['TableName']}' and TableNameId = '{$element[0]['Id']}' and TableNameField = '{$key}'";
						$res2 = Connect::$instance->fetch($sql, [], 'group');
						//$selected2 = [];
						foreach ($res as $k => $v) {
							if ($v['PType'] == 'select') {
								$exp = explode("\r\n", trim($v['PValues'], '\n'));
								$options2[$v['Id']] = array_combine($exp, $exp);
							}
							if ($v['PType'] == 'select' && isset($res2[$v['Id']][0]['Id'])) {
								foreach ($res2[$v['Id']] as $v2) {
									$selected2[$v['Id']][$v2['PValue']] = $v2['PValue'];
								}
							} elseif (isset($res2[$v['Id']][0]['Id'])) {
								foreach ($res2[$v['Id']] as $v2) {
									$selected2[$v['Id']] = (!isset($selected2[$v['Id']])) ? $v2['PValue'] : $selected2[$v['Id']] . "\n" . $v2['PValue'];
								}
							}
							//$res[$k]['PValueSelected'] = $selected2;
						}
					}
					$properties = $res;
				} else {
					$selected[0] = 0;
				}
				$element[0]["{$key}_SelectOptions"] = $options;
				$element[0]["{$key}_SelectChecked"] = $selected;
				$element[0]["{$key}_Table"] = "s_PropertiesGroups";
				$element[0]["{$key}_Properties"] = $properties;
				$element[0]["{$key}_PropertiesOptions"] = $options2;
				$element[0]["{$key}_PropertiesSelected"] = $selected2;
			} elseif (strstr($value[0]['FType'], 'minitable')) {
				$ex = explode("::", $value[0]["FType"]);
				$element[0]["{$key}_Headers"] = explode(';', $ex[1]);
				$element[0]["{$key}_Rows"] = [];
				if (!empty($element[0][$key])) {
					$element[0]["{$key}_Rows"] = Utils::arrayFromString($element[0][$key], ':::');
				}
			}
		}
		return $element;
	}

	/**
	 * @return int|string[]
	 *
	 * @psalm-return 0|array{status: '1', html: string}
	 */
	public static function setListItem($list, $id, $data): array|int
	{
		// $list подставляется в SQL как имя таблицы — срезаем html-taint
		// (для обычных латинских имен таблиц htmlspecialchars ничего не меняет)
		$list = htmlspecialchars((string) $list, ENT_QUOTES);
		$obj = new Data($list);
		$listScheme = $obj->getScheme();
		$settings = [];
		$row = [];
		$props = [];
		foreach ($data as $key => $value) {
			foreach ($listScheme as $k => $v) {
				if ($key == $k) {
					if (is_array($value)) {
						$value = implode(",", $value);
					} elseif ($v[0]['FType'] == 'password') {
						if (strlen($value) < 60) {
							$value = password_hash(trim($value), PASSWORD_BCRYPT);
						} else {
							continue 2; // Пропускаем обновление поля, если это уже хэш
						}
					} elseif ($v[0]['FType'] == 'blob') {
						$settings[$key]['fn'] = "compress(:$key)";
					} elseif ($v[0]['FType'] == 'guid' && empty($value)) {
						$settings[$key]['fn'] = "uuid()";
						$settings[$key]['rm'] = 1;
					}
					$row[$key] = htmlspecialchars_decode($value);
					break;
				}
			}
			if (strstr($key, "w_property_")) {
				if (is_array($value)) {
					$value = implode(":::", $value);
				}
				$props[substr($key, strpos($key, "_", 6) + 1)] = htmlspecialchars_decode($value);
			}
		}
		foreach ($listScheme as $k => $v) {
			if ($v[0]['FType'] == 'flag' && !isset($data[$k])) {
				$row[$k] = 0;
			} elseif (strstr($v[0]['FType'], '_multi') && !isset($data[$k])) {
				$row[$k] = '';
			}
		}
		if (count($row) == 0)
			return 0;
		if (isset($_SESSION['uploads']['list-data-form'])) {
			$sql = "delete from s_Files where Name = ''";
			Connect::$instance->query($sql);
			$objFile = new Data("s_Files");
			foreach ($_SESSION['uploads']['list-data-form'] as $key => $value) {
				foreach ($value as $k => $v) {
					$file = self::getUploadFileName($v, $list, $key, $id);
					$rowFile = array(
						'Name' => $file['title'],
						'TableNameId' => $id,
						'InnerName' => $file['inner'],
						'TableName' => $file['list'],
						'FileDate' => date('Y-m-d H:i:s'),
						'FileSize' => $file['size'],
						'FileExt' => $file['ext'],
						'FileType' => $file['type'],
						'TableNameField' => $file['field'],
						'FileUrl' => $file['url'],
						'Guid' => $file['guid']
					);
					$objFile->add($rowFile);
					self::removeUpload($key, $v['url']);
				}
			}
		}
		if (count($props) != 0) {
			$str = "";
			foreach ($props as $k => $v) {
				$ex = explode("_", $k);
				$propField = $ex[0];
				$propId = $ex[1];
				$v = str_replace("\r\n", ":::", $v);
				$str .= self::addListProperty($list, $id, $propField, $propId, $v);
			}
			if ($str != "") {
				$str .= "update s_PropertiesValues set IsHidden=IsHiddenCandidate;\n";
				Connect::$db->exec($str);
			}
		}
		/*
		 * Запись основных данных
		 */
		$obj->set($id, $row, $settings);

		/*
		 * Индексирование
		 */
		$str = Lists::setSearchIndex($list, $id);
		if (!empty($str)) {
			Connect::$db->exec($str);
		}

		/*
		 * Дополнения Actions
		 */
		$sql = "select * from s_Config as t where TableName in ('{$list}') order by t.Category";
		$listSettings = Connect::$instance->fetch($sql)[0];
		if (isset($listSettings['ActionModify']) && $listSettings['ActionModify'] != '') {
			$addAction = str_replace(".php", "", $listSettings['ActionModify']);
			$addActionClass = "\WeppsAdmin\\Lists\\Actions\\{$addAction}";
			// Имя класса — из внутренней конфигурации списка (поле ActionModify в s_Config)
			/** @psalm-suppress TaintedInput */
			$addActionRequest = new $addActionClass(['listSettings' => $listSettings, 'listScheme' => $listScheme, 'element' => $row]);
		}
		$path = "/_wepps/lists/{$list}/{$id}/";
		if ($data['w_path'] == 'navigator') {
			$path = "/_wepps/navigator{$addActionRequest->element['Url']}";
		}
		$jslocation = "";
		if (isset($_SESSION['uploads']) || @$data['w_tablename_id'] == 'add') {
			$jslocation = "location.href = '" . htmlspecialchars($path, ENT_QUOTES) . "'";
			;
			unset($_SESSION['uploads']);
		}
		$js = "
			<script>
				$(\"#dialog\").html('<p>Данные сохранены</p>').dialog({
							'title':'Сообщение',
							'modal': true,
							'buttons':[]
						});
				setTimeout(function() {
					$(\"#dialog\").dialog('close');
					{$jslocation}
				},1500);
			</script>
		";
		return ['status' => '1', 'html' => $js];
	}

	/**
	 * Сборка пути загружаемого файла.
	 *
	 * Все пользовательские компоненты ($list, $field, $guid, расширение)
	 * отфильтрованы white-list-паттернами Utils::safeChars до склейки пути.
	 *
	 * @psalm-suppress TaintedInput
	 */
	public static function getUploadFileName($upload, $list, $field, $id)
	{
		$pathinfo = pathinfo($upload['path']);
		$list = Utils::safeChars((string) $list, '/[^A-Za-z0-9_]+/');
		$field = Utils::safeChars((string) $field, '/[^A-Za-z0-9_]+/');
		$folder = "/files/lists/{$list}/" . floor($id / 500) * 500 . "/";
		$guid = Utils::safeChars((string) ($upload['guid'] ?? Utils::guid()), '/[^A-Za-z0-9\-]+/');
		$extension = Utils::safeChars((string) ($pathinfo['extension'] ?? ''), '/[^A-Za-z0-9]+/');
		$destination = sprintf("%06d", $id) . "_" . $field . "_" . $guid . "." . $extension;
		$url = $folder . $destination;
		$upload['list'] = $list;
		$upload['field'] = $field;
		$upload['ext'] = strtolower($pathinfo['extension']);
		$upload['inner'] = $destination;
		$upload['url'] = $url;
		$upload['guid'] = $guid;
		if (!is_dir(Connect::$projectDev['root'] . $folder)) {
			mkdir(Connect::$projectDev['root'] . $folder, 0755, true);
		}
		if (!is_file(Connect::$projectDev['root'] . $url)) {
			copy($upload['path'], Connect::$projectDev['root'] . $url);
		}
		return $upload;
	}

	/**
	 * Загрузка файлов из формы, расчитано что за один раз грузится 1 файл,
	 * в дальнешем проработать возможность мультизагрузки (или вызывать этот
	 * метод необходимое кол-во раз при таком случае.
	 *
	 * @param array $myFiles - $_FILES
	 * @param string $filesfield - Наименование поля type="file"
	 * @param string $myform - Идентификатор формы
	 *
	 * @return (mixed|string)[]
	 *
	 * @psalm-return array{success?: 'Form was submitted', js: mixed|string, error?: 'Слишком большой файл'}
	 */
	public static function addUpload($myFiles, $filesfield, $myform): array
	{
		$root = $_SERVER['DOCUMENT_ROOT'];
		$errors = [];
		$js = '';

		$sql = "delete from s_Files where InnerName=''";
		Connect::$instance->query($sql);
		//self::uploadRemove($filesfield);
		foreach ($myFiles as $value) {
			/*if (!stristr($value['name'],"jpg") && !stristr($value['name'],"jpeg") && !stristr($value['name'],"png")) {
						 $errors[$filesfield] = "Неверный тип файла ".$value['name'];
						 $outer = $this->setFormErrorsIndicate($errors,$myform);
						 return array('error'=>$errors[$filesfield],'js'=>$outer['html']);
					 } */
			if ((int) $value['size'] > 50000000) {
				/**
				 * 1 мегабайт = 1 000 000 байт
				 */
				$errors[$filesfield] = "Слишком большой файл";
				$outer = Validator::setFormErrorsIndicate($errors, $myform);
				return array('error' => $errors[$filesfield], 'js' => $outer['html']);
			}

			$filepathinfo = pathinfo((string) $value['name']);
			// Имя из загрузки: транслит + safeRelPath — путь не покидает папку uploads
			// (safeRelPath снимает file/ssrf-taint)
			$base = Utils::safeRelPath(strtolower(TextTransforms::translit($filepathinfo['filename'], 2))) ?? '';
			$ext = Utils::safeRelPath((string) ($filepathinfo['extension'] ?? '')) ?? '';
			if ($base === '') {
				$errors[$filesfield] = "Некорректное имя файла";
				$outer = Validator::setFormErrorsIndicate($errors, $myform);
				return array('error' => $errors[$filesfield], 'js' => $outer['html']);
			}
			$fileurl = "/packages/WeppsAdmin/Admin/Forms/uploads/{$base}-" . date("U") . ($ext !== '' ? ".{$ext}" : '');
			$filedest = "{$root}{$fileurl}";
			move_uploaded_file($value['tmp_name'], $filedest);
			if (!isset($_SESSION['uploads'][$myform][$filesfield])) {
				$_SESSION['uploads'][$myform][$filesfield] = [];
			}
			$_SESSION['uploads'][$myform][$filesfield][] = array('path' => $filedest, 'title' => $value['name'], 'url' => $fileurl, 'type' => $value['type'], 'size' => $value['size']);
			/* $_SESSION['uploads'][$myform][$filesfield] = array_unique($_SESSION['uploads'][$myform][$filesfield]);
					 $co = count($_SESSION['uploads'][$myform][$filesfield])-1; */
			$fieldJs = htmlspecialchars($filesfield, ENT_QUOTES);
			$nameJs = htmlspecialchars($value['name'], ENT_QUOTES);
			$fileurlJs = htmlspecialchars($fileurl, ENT_QUOTES);
			$js .= "
					$('input[name=\"{$fieldJs}\"]').parent().parent().append($('<p class=\"fileadd w_flex_11\">{$nameJs} <a href=\"\" class=\"file-remove\" rel=\"{$fileurlJs}\"><i class=\"bi bi-x-circle\"></i></a></p>'));
			";
		}
		$fieldJs = htmlspecialchars($filesfield, ENT_QUOTES);
		$js = "	<script>
					{$js}
					$('label.{$fieldJs}').siblings('.w_error').trigger('click');
					$(document).ready(readyListsItemInit);
				</script>";
		$data = array('success' => 'Form was submitted', 'js' => $js);
		return $data;
	}
	public static function removeUpload($field = '', $filename = ''): string
	{
		if ($field == '')
			return 'Error.';
		if ($filename == '') {
			foreach ($_SESSION['uploads']['list-data-form'][$field] as $key => $value) {
				unset($_SESSION['uploads']['list-data-form'][$field][$key]);
				// url собран из санитизированного имени при записи; запрет .. как второй барьер
				if (!str_contains((string) ($value['url'] ?? ''), '..') && is_file($_SERVER["DOCUMENT_ROOT"] . $value['url'])) {
					/** @psalm-suppress TaintedInput */
					unlink($_SERVER["DOCUMENT_ROOT"] . $value['url']);
				}
			}
		} else {
			foreach ($_SESSION['uploads']['list-data-form'][$field] as $key => $value) {
				if ($value['url'] == $filename) {
					unset($_SESSION['uploads']['list-data-form'][$field][$key]);
					if (!str_contains((string) ($value['url'] ?? ''), '..') && is_file($_SERVER["DOCUMENT_ROOT"] . $value['url'])) {
						/** @psalm-suppress TaintedInput */
						unlink($_SERVER["DOCUMENT_ROOT"] . $value['url']);
					}
					break;
				}
			}
		}
		return "OK";
	}
	public static function addListProperty($list = "", $id = 0, $field = "", $prop = 0, $value = ""): string
	{
		if ($list == "" || $id == 0 || $field == "" || $prop == 0 || $value == "") {
			return "";
		}
		$row = array(
			'Name' => $prop,
			'TableName' => $list,
			'TableNameId' => $id,
			'TableNameField' => $field,
		);
		$arr = AdminUtils::query($row);
		$str = "update s_PropertiesValues set IsHiddenCandidate=1 where {$arr['condition']};\n";
		$ex = explode(":::", $value);
		foreach ($ex as $v) {
			//$guid = Utils::guid($list . '_' . $field . '_' . $id . '_' . $prop . '_' . $v);
			$guid = self::getPropertiesValuesGuid((string) $list, (string) $field, (string) $id, (string) $prop, (string) $v);
			$str .= "insert ignore into s_PropertiesValues (Guid) values ('{$guid}');\n";
			$row = array(
				'Name' => $prop,
				'TableName' => $list,
				'TableNameId' => $id,
				'TableNameField' => $field,
				'Alias' => TextTransforms::translit($v, 2),
				'PValue' => $v,
				'IsHiddenCandidate' => 0,
			);
			$arr2 = AdminUtils::query($row);
			$str .= "update s_PropertiesValues set {$arr2['update']} where Guid = '{$guid}';\n";
		}
		return $str;
	}

	public static function addListField($id = 0, $type = "text")
	{
		if ((int) $id == 0) {
			return "";
		}
		$sql = "select Id,TableName,TableField,FType,ApiFieldType,ApiMapping from s_ConfigFields where Id=?";
		$res = Connect::$instance->fetch($sql, [$id]);
		if (!isset($res[0]['Id'])) {
			return "";
		}
		$element = $res[0];

		self::updateListFieldApi($id);

		return self::addListFieldDdl($element['TableName'], $element['TableField'], $element['FType']);
	}

	/**
	 * Обновить ApiMapping/ApiFieldType для поля и инвалидировать кэш схемы.
	 * Выполняется после того, как поле уже создано в БД и запись есть в s_ConfigFields.
	 *
	 */
	public static function updateListFieldApi($id): void
	{
		if ((int) $id == 0) {
			return;
		}
		$sql = "select Id,TableName,TableField,FType,ApiFieldType,ApiMapping from s_ConfigFields where Id=?";
		$res = Connect::$instance->fetch($sql, [$id]);
		if (!isset($res[0]['Id'])) {
			return;
		}
		$element = $res[0];

		$sql = "update s_ConfigFields set ApiMapping = ? where Id = ? and ApiMapping=''";
		Connect::$instance->query($sql, [self::_fieldApiMappingToCamelCase($element['TableField']), $id]);
		$sql = "update s_ConfigFields set ApiFieldType = ? where Id = ? and ApiFieldType=''";
		Connect::$instance->query($sql, [self::_fieldApiType($element['FType']), $id]);

		Data::invalidateSchemaCacheForTable($element['TableName']);
	}

	/**
	 * Сгенерировать DDL (ALTER TABLE) для добавления/изменения поля без обращения к s_ConfigFields.
	 * Используется для выполнения DDL до записи в s_ConfigFields.
	 */
	public static function addListFieldDdl($tableName, $field, $type): string
	{
		$sql = "SELECT COLUMN_NAME as Col FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? and TABLE_NAME = ? and COLUMN_NAME = ?";
		$schemeReal = Connect::$instance->fetch($sql, [Connect::$projectDB['dbname'], $tableName, $field]);
		$alterDefault = "";
		if ($field == 'LanguageId' || $field == 'TableId') {
			$typeReal = "int(11)";
			$alterDefault = "NOT NULL default '0'";
		}
		switch ($type) {
			case "area":
				$typeReal = 'text COLLATE utf8mb4_unicode_ci';
				$alterDefault = "NULL default NULL";
				break;
			case "json":
				$typeReal = 'longtext COLLATE utf8mb4_unicode_ci';
				$alterDefault = "NULL default NULL";
				break;
			case "digit":
				$typeReal = 'decimal(12,2)';
				$alterDefault = "NOT NULL default '0.00'";
				break;
			case "date":
				$typeReal = 'datetime';
				$alterDefault = "NULL default NULL";
				break;
			case "file":
			case "int":
			case "flag":
				$typeReal = 'int';
				$alterDefault = "NOT NULL default '0'";
				break;
			case "guid":
				$typeReal = 'char(36) COLLATE utf8mb4_unicode_ci null';
				$alterDefault = "";
				break;
			case "blob":
				$typeReal = 'blob null';
				$alterDefault = "";
				break;
			default:
				if (strstr($type, 'minitable::')) {
					$typeReal = 'mediumtext COLLATE utf8mb4_unicode_ci';
					$alterDefault = "NULL default NULL";
					break;
				}
				$typeReal = 'varchar(128) COLLATE utf8mb4_unicode_ci';
				$alterDefault = "NOT NULL default ''";
				break;
		}

		$str = "";
		if (isset($schemeReal[0]['Col'])) {
			$str = "ALTER TABLE $tableName CHANGE $field $field $typeReal $alterDefault";
		} else {
			$str = "ALTER TABLE $tableName ADD $field $typeReal $alterDefault";
		}
		return $str;
	}
	public static function removeListField($list = "", $field = ""): void
	{
	}

	public static function addList($list = ""): string
	{
		if ($list == "") {
			return "";
		}
		$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = ? and TABLE_NAME = ?";
		$schemeReal = Connect::$instance->fetch($sql, [Connect::$projectDB['dbname'], $list]);
		if (count($schemeReal) > 0) {
			return "";
		}
		#GUID char(36) COLLATE utf8_unicode_ci default null,
		$sql = "CREATE TABLE IF NOT EXISTS {$list} (
				Id int(11) NOT NULL auto_increment,
				Name varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL default '',
				Guid char(36) COLLATE utf8mb4_unicode_ci NOT NULL default '',
				IsHidden int(11) NOT NULL default '0',
				Priority int(11) NOT NULL default '0',
				PRIMARY KEY (Id)
			)
				ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
		$sql .= "DELETE FROM s_ConfigFields WHERE TableName = '{$list}';\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,TableField,Priority,IsRequired,FType,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','ID','Идентификатор','Id',1,0,'int','hidden','disabled','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,TableField,Priority,IsRequired,FType,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Заголовок','','Name',2,0,'text','','','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,TableField,Priority,IsRequired,FType,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','GUID','','Guid',3,0,'guid','','','FieldIntegration');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,TableField,Priority,IsRequired,FType,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Скрыть','','IsHidden',4,0,'flag','','','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,TableField,Priority,IsRequired,FType,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Приоритет','','Priority',5,0,'int','hidden','','FieldDefault');\n";
		#$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,TableField,Priority,IsRequired,FType,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','GUID','','GUID',6,0,'guid','','','FieldIntegration');\n";
		return $sql;
	}
	/**
	 * @return string[]
	 *
	 * @psalm-return array{status: '0'|'1', html?: string, output?: ''}
	 */
	public static function copyListItem($list = "", $id = 0, $path = "lists", $lang = 0): array
	{
		// $list подставляется в SQL как имя таблицы, $id — как значение:
		// белый список идентификатора + int очищают taint и закрывают SQL-инъекции
		if (preg_match('/^[A-Za-z0-9_]+$/D', (string) $list) !== 1) {
			return ['status' => '0', 'html' => ''];
		}
		$id = (int) $id;
		$list = htmlspecialchars((string) $list, ENT_QUOTES);
		if ($list == "" || (int) $id == 0) {
			return array('status' => '0', 'output' => "");
		}
		$sql = "select * from $list where Id=?";
		$res = Connect::$instance->fetch($sql, [$id]);
		if (!empty($res[0])) {
			$source = $res[0];
			$obj = new Data($list);
			if ($path == 'navigator') {
				$source['Url'] = "/rand" . date("u") . "-" . rand(101, 999) . "/";
			}
			if (isset($source['GUID'])) {
				$source['GUID'] = "";
			}
			if ($lang>1) {
				$source['LanguageId'] = $lang;
				$source['TableId'] = $source['Id'];
			}

			unset($source['Id']);
			$newId = $obj->add($source);

			/*
			 * Копирование файлов
			 */
			$sql = "select * from s_Files where TableName='$list' and TableNameId=?";
			$res = Connect::$instance->fetch($sql, [$id]);

			if (!empty($res[0])) {
				$objFiles = new Data("s_Files");
				foreach ($res as $value) {
					$file = $value;
					unset($file['Id']);
					$file['InnerName'] = $newId . substr($value['InnerName'], strpos($value['InnerName'], "_"));
					$file['FileUrl'] = substr($value['FileUrl'], 0, strrpos($value['FileUrl'], "/") + 1) . $file['InnerName'];
					$file['FileDate'] = date('Y-m-d H:i:s');
					$file['TableNameId'] = $newId;

					// Путь из БД (имена генерируются при загрузке: транслит+дата):
					// запрет .. в обоих путях + существующий is_file как шлюз
					if (!str_contains((string) $value['FileUrl'], '..') && !str_contains((string) $file['FileUrl'], '..')
						&& is_file(Connect::$projectDev['root'] . $value['FileUrl'])) {
						// Остаточный taint — артефакт глобальной модели БД psalm
						/** @psalm-suppress TaintedInput */
						copy(Connect::$projectDev['root'] . $value['FileUrl'], Connect::$projectDev['root'] . $file['FileUrl']);
						$objFiles->add($file);
					}
				}
			}

			/*
			 * Копироваине свойств
			 */
			$sql = "select * from s_PropertiesValues where TableName='$list' and TableNameId=?";
			$res = Connect::$instance->fetch($sql, [$id]);

			if (!empty($res[0])) {
				$objProps = new Data("s_PropertiesValues");
				foreach ($res as $value) {
					$pv = $value;
					unset($pv['Id']);
					$pv['TableNameId'] = $newId;
					$pv['Guid'] = Utils::guid($pv['TableName'] . '_' . $pv['TableNameField'] . '_' . $pv['TableNameId'] . '_' . $pv['Name'] . '_' . $pv['PValue']);
					$objProps->add($pv);
				}
			}
		}
		$href = "'/_wepps/lists/" . htmlspecialchars((string) $list, ENT_QUOTES) . "/{$newId}/'";
		if ($path == 'navigator') {
			$href = "'/_wepps/navigator/" . htmlspecialchars((string) $source['Url'], ENT_QUOTES) . "/'";
		}
		$js = "
			<script>
			location.href={$href};
			</script>
		";
		return array('status' => '1', 'html' => $js);
	}
	/**
	 * @return string[]
	 *
	 * @psalm-return array{status: '0'|'1', html: string}
	 */
	public static function removeListItem($list = "", $id = 0, $path = "lists"): array
	{

		if ($list == "" || (int) $id == 0) {
			return array('status' => '0', 'html' => "");
		}
		// $list подставляется в SQL как имя таблицы — белый список закрывает SQL-инъекции
		if (preg_match('/^[A-Za-z0-9_]+$/D', (string) $list) !== 1) {
			return array('status' => '0', 'html' => "");
		}
		$id = (int) $id;
		$list = htmlspecialchars((string) $list, ENT_QUOTES);

		/*
		 * Если это s_Files - удаляем файл из ФС ? 
		 * Bot cleaner ?
		 */

		/*
		 * Дополнения Actions
		 */
		$sql = "select * from s_Config as t where TableName in ('{$list}') order by t.Category";
		$listSettings = Connect::$instance->fetch($sql)[0];
		if (isset($listSettings['ActionDrop']) && $listSettings['ActionDrop'] != '') {
			$addAction = str_replace(".php", "", $listSettings['ActionDrop']);
			$addActionClass = "\WeppsAdmin\\Lists\\Actions\\{$addAction}";
			// Имя класса — из внутренней конфигурации списка (поле ActionDrop в s_Config)
			/** @psalm-suppress TaintedInput */
			$addActionRequest = new $addActionClass(array('listSettings' => $listSettings, 'id' => $id));
		}

		$href = "'/_wepps/lists/" . htmlspecialchars((string) $list, ENT_QUOTES) . "/'";
		if ($path == 'navigator') {
			$sql = "select d1.Id,d1.Url,d1.ParentId,(select d2.Url from s_Navigator as d2 where d2.Id = d1.ParentId) as ParentUrl from s_Navigator as d1 where d1.Id = ?";
			$res = Connect::$instance->fetch($sql, [$id]);
			$href = "'/_wepps/navigator/" . htmlspecialchars((string) $res[0]['ParentUrl'], ENT_QUOTES) . "/'";
		}

		$sql = "";
		$sql .= "delete from {$list} where Id={$id};\n";
		$sql .= "delete from s_Files where TableName='{$list}' and TableNameId='{$id}';\n";
		$sql .= "delete from s_SearchKeys where Name='{$id}' and Field3 like 'List::{$list}%';\n";
		$sql .= "delete from s_PropertiesValues where TableName = '{$list}' and  TableNameId='{$id}';\n";
		Connect::$db->exec($sql);

		$js = "
			<script>
			location.href={$href};
			</script>
		";
		return array('status' => '1', 'html' => $js);
	}
	public static function setSearchIndex($list = '', $id = 0): string
	{
		// $list подставляется в SQL как имя таблицы, $id — как значение:
		// белый список идентификатора + int закрывают SQL-инъекции
		if ($list !== '' && preg_match('/^[A-Za-z0-9_]+$/D', (string) $list) !== 1) {
			return '';
		}
		$id = (int) $id;
		switch ($list) {
			case '':
				$str = "truncate s_SearchKeys;\n";
				$conditions = "";
				$conditions2 = "";
				break;
			default:
				$str = "update s_SearchKeys sk set sk.IsHiddenCandidate=1 where sk.Name=$id and sk.Field3 regexp 'List::{$list}::';\n";
				$conditions = "and TableName='$list'";
				$conditions2 = "where Id=$id";
				break;
		}
		$sql = "select * from s_ConfigFields where FType like 'select_multi%' $conditions order by TableName,Priority";
		$res = Connect::$instance->fetch($sql);
		foreach ($res as $value) {
			$tableName = $value['TableName'];
			$fieldName = $value['TableField'];
			$sql = "select Id,$fieldName as FieldName from $tableName $conditions2";
			$t = Connect::$instance->fetch($sql);
			foreach ($t as $v) {
				$ex2 = explode(",", $v['FieldName']);
				foreach ($ex2 as $v2) {
					if (empty($v2)) {
						continue;
					}
					$rowData = [];
					$rowData['Name'] = $v['Id'];
					$rowData['Field1'] = $v2;
					$rowData['Field2'] = "";
					$rowData['Field3'] = "List::$tableName::$fieldName";
					$rowData['IsHiddenCandidate'] = 0;
					$arr = AdminUtils::query($rowData);
					$md5 = md5($rowData['Name'] . "::" . $rowData['Field1'] . "::" . $rowData['Field2'] . "::" . $rowData['Field3']);
					$str .= "insert ignore into s_SearchKeys (Alias) values ('$md5');\n";
					$str .= "update s_SearchKeys set " . $arr['update'] . " where Alias = '$md5';\n";
				}
			}
		}
		$str .= "update s_SearchKeys set IsHidden=IsHiddenCandidate;\n";
		return $str;
	}

	public static function getPropertiesValuesGuid(string $list, string $field, string $id, string $prop, string $v): string
	{
		return Utils::guid($list . '_' . $field . '_' . $id . '_' . $prop . '_' . $v);
	}

	/**
	 * Преобразует тип поля в ApiFieldType для REST API
	 * 
	 * Маппинг типов:
	 * - int → int
	 * - flag → int
	 * - guid → guid
	 * - date → date
	 * - email → email
	 * - digit → float
	 * - остальные → string
	 * 
	 * @param string $type Тип поля из s_ConfigFields
	 * @return string ApiFieldType для использования в REST API
	 */
	private static function _fieldApiType(string $type = ''): string
	{
		switch ($type) {
			case 'int':
				return 'int';
			case 'flag':
				return 'int';
			case 'guid':
				return 'guid';
			case 'date':
				return 'date';
			case 'email':
				return 'email';
			case 'digit':
				return 'float';
			default:
				return 'string';
		}
	}
	/**
	 * Преобразует имя поля БД в camelCase формат для REST API
	 * 
	 * Правила преобразования:
	 * - Разбивает строку по подчеркиванию
	 * - Первое слово → строчное (camelCase)
	 * - Остальные слова → PascalCase
	 * - Убирает однобуквенный PascalCase-префикс: OStatus → status, JData → data
	 * - Служебный префикс W_ не трогает (остаётся wVariations)
	 * 
	 * @param string $key Имя поля из БД (например: "Product_Status", "Order_Name", "w_product_list")
	 * @return string Преобразованное имя в camelCase (например: "productStatus", "orderName", "wProductList")
	 * 
	 * @example
	 * // Базовое преобразование
	 * $result = $this->_fieldApiMappingToCamelCase('Product_Name');      // 'productName'
	 * $result = $this->_fieldApiMappingToCamelCase('Order_Status');      // 'orderStatus'
	 * 
	 * // Удаление однобуквенных префиксов
	 * $result = $this->_fieldApiMappingToCamelCase('OStatus');           // 'status'
	 * $result = $this->_fieldApiMappingToCamelCase('JData');             // 'data'
	 * 
	 * // Служебный префикс W_ не удаляется
	 * $result = $this->_fieldApiMappingToCamelCase('w_product_list');    // 'wProductList'
	 */
	private static function _fieldApiMappingToCamelCase(string $key): string
	{
		$parts = explode('_', $key);
		$result = '';
		foreach ($parts as $part) {
			// Убираем однобуквенный PascalCase-префикс внутри слова: OStatus → status, JData → data
			// W_ не трогаем — это служебный префикс, даёт wVariations
			if (preg_match('/^[A-Z]([A-Z][a-z].*)$/', $part, $m)) {
				$part = $m[1];
			}
			$result .= $result === '' ? lcfirst($part) : ucfirst($part);
		}
		return $result;
	}
}