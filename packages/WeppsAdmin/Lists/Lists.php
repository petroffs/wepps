<?php
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
		$weppsurl = "/" . $_GET['weppsurl'];
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
				if (strstr($listScheme[$this->get['field']][0]['Type'], 'select')) {
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

	public static function getListItemForm(TemplateHeaders &$headers, $list, $id)
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
		$headers->js("/packages/vendor/tinymce/tinymce/tinymce.min.js");
		$headers->css("/packages/WeppsAdmin/Lists/Lists.{$headers::$rand}.css");
		$headers->css("/packages/WeppsAdmin/Lists/ListsItem.{$headers::$rand}.css");
		$headers->js("/packages/WeppsAdmin/Lists/Lists.{$headers::$rand}.js");
		$headers->js("/packages/WeppsAdmin/Lists/ListsItem.{$headers::$rand}.js");

		$element = self::getListItem($listObj, $listScheme, $id)[0];
		/*
		 * Дополнения Actions
		 */
		if (isset($listSettings['ActionShowId']) && $listSettings['ActionShowId'] != '') {
			$addAction = str_replace(".php", "", $listSettings['ActionShowId']);
			$addActionClass = "\WeppsAdmin\\Lists\\Actions\\{$addAction}";
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
			if (strstr($value[0]['Type'], 'remote')) {
				$ex = explode("::", $value[0]["Type"]);
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
			} elseif (strstr($value[0]['Type'], 'select')) {
				$ex = explode("::", $value[0]["Type"]);
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

			} elseif (strstr($value[0]['Type'], 'dbtable')) {
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

			} elseif (strstr($value[0]['Type'], 'properties') && $id != 'add') {
				$condition = "";
				$ex = explode("::", $value[0]["Type"]);
				if (!empty($ex[1])) {
					$condition = "and {$ex[1]}";
				}
				/*
				 * Характеристики (s_Properties)
				 */
				$sql = "select Id,Name from s_PropertiesGroups where DisplayOff=0 $condition order by Priority";
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
					$sql = "select * from s_Properties where DisplayOff=0 and PGroup in (0,'',{$element[0][$key]}) order by Priority";
					$res = Connect::$instance->fetch($sql);
					if (isset($res[0]['Id'])) {
						/*
						 * Значения полей
						 */
						$sql = "select Name,Id,PValue from s_PropertiesValues where DisplayOff=0 and TableName = '{$value[0]['TableName']}' and TableNameId = '{$element[0]['Id']}' and TableNameField = '{$key}'";
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
			} elseif (strstr($value[0]['Type'], 'minitable')) {
				$ex = explode("::", $value[0]["Type"]);
				$element[0]["{$key}_Headers"] = explode(';', $ex[1]);
				$element[0]["{$key}_Rows"] = [];
				if (!empty($element[0][$key])) {
					$element[0]["{$key}_Rows"] = Utils::arrayFromString($element[0][$key], ':::');
				}
			}
		}
		return $element;
	}

	public static function setListItem($list, $id, $data)
	{
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
					} elseif ($v[0]['Type'] == 'password' && strlen($value) < 60) {
						$value = password_hash(trim($value), PASSWORD_BCRYPT);
					} elseif ($v[0]['Type'] == 'blob') {
						$settings[$key]['fn'] = "compress(:$key)";
					} elseif ($v[0]['Type'] == 'guid' && empty($value)) {
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
			if ($v[0]['Type'] == 'flag' && !isset($data[$k])) {
				$row[$k] = 0;
			} elseif (strstr($v[0]['Type'], '_multi') && !isset($data[$k])) {
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
				$str .= "update s_PropertiesValues set DisplayOff=DisplayOff2;\n";
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
			$addActionRequest = new $addActionClass(['listSettings' => $listSettings, 'listScheme' => $listScheme, 'element' => $row]);
		}
		$path = "/_wepps/lists/{$list}/{$id}/";
		if ($data['w_path'] == 'navigator') {
			$path = "/_wepps/navigator{$addActionRequest->element['Url']}";
		}
		$jslocation = "";
		if (isset($_SESSION['uploads']) || @$data['w_tablename_id'] == 'add') {
			$jslocation = "location.href = '{$path}'";
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

	public static function getUploadFileName($upload, $list, $field, $id)
	{
		$pathinfo = pathinfo($upload['path']);
		$translit = TextTransforms::translit($upload['title']);
		$prefix = sprintf("%06d", $id) . "_{$field}_" . date("U") . "_";
		$lengthMax = 36;
		$translitPos = strrpos($translit, '.');
		if ($translitPos > $lengthMax) {
			$translit = substr($translit, $translitPos - $lengthMax);
		}
		$folder = "/files/lists/{$list}/";
		$destination = $prefix . $translit;
		$url = $folder . $destination;
		$upload['list'] = $list;
		$upload['field'] = $field;
		$upload['ext'] = strtolower($pathinfo['extension']);
		$upload['inner'] = $destination;
		$upload['url'] = $url;
		if (!is_dir(Connect::$projectDev['root'] . $folder)) {
			mkdir(Connect::$projectDev['root'] . $folder, 0777);
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
	 * @return []
	 */
	public static function addUpload($myFiles, $filesfield, $myform)
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

			$filepathinfo = pathinfo($value['name']);
			$filepathinfo['filename'] = strtolower(TextTransforms::translit($filepathinfo['filename'], 2));
			$fileurl = "/packages/WeppsAdmin/Admin/Forms/uploads/{$filepathinfo['filename']}-" . date("U") . ".{$filepathinfo['extension']}";
			$filedest = "{$root}{$fileurl}";
			move_uploaded_file($value['tmp_name'], $filedest);
			if (!isset($_SESSION['uploads'][$myform][$filesfield])) {
				$_SESSION['uploads'][$myform][$filesfield] = [];
			}
			$_SESSION['uploads'][$myform][$filesfield][] = array('path' => $filedest, 'title' => $value['name'], 'url' => $fileurl, 'type' => $value['type'], 'size' => $value['size']);
			/* $_SESSION['uploads'][$myform][$filesfield] = array_unique($_SESSION['uploads'][$myform][$filesfield]);
					 $co = count($_SESSION['uploads'][$myform][$filesfield])-1; */
			$js .= "
					$('input[name=\"{$filesfield}\"]').parent().parent().append($('<p class=\"fileadd w_flex_11\">{$value['name']} <a href=\"\" class=\"file-remove\" rel=\"{$fileurl}\"><i class=\"fa fa-remove\"></i></a></p>'));
			";
		}
		$js = "	<script>
					{$js}
					$('label.{$filesfield}').siblings('.w_error').trigger('click');
					$(document).ready(readyListsItemInit);
				</script>";
		$data = array('success' => 'Form was submitted', 'js' => $js);
		return $data;
	}
	public static function removeUpload($field = '', $filename = '')
	{
		if ($field == '')
			return 'Error.';
		if ($filename == '') {
			foreach ($_SESSION['uploads']['list-data-form'][$field] as $key => $value) {
				unset($_SESSION['uploads']['list-data-form'][$field][$key]);
				if (is_file($_SERVER["DOCUMENT_ROOT"] . $value['url']))
					unlink($_SERVER["DOCUMENT_ROOT"] . $value['url']);
			}
		} else {
			foreach ($_SESSION['uploads']['list-data-form'][$field] as $key => $value) {
				if ($value['url'] == $filename) {
					unset($_SESSION['uploads']['list-data-form'][$field][$key]);
					if (is_file($_SERVER["DOCUMENT_ROOT"] . $value['url']))
						unlink($_SERVER["DOCUMENT_ROOT"] . $value['url']);
					break;
				}
			}
		}
		return "OK";
	}
	public static function addListProperty($list = "", $id = 0, $field = "", $prop = 0, $value = "")
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
		$str = "update s_PropertiesValues set DisplayOff2=1 where {$arr['condition']};\n";
		$ex = explode(":::", $value);
		foreach ($ex as $v) {
			$hash = md5($list . $field . $id . $prop . $v);
			$str .= "insert ignore into s_PropertiesValues (HashValue) values ('{$hash}');\n";
			$row = array(
				'Name' => $prop,
				'TableName' => $list,
				'TableNameId' => $id,
				'TableNameField' => $field,
				'Alias' => TextTransforms::translit($v, 2),
				'PValue' => $v,
				'DisplayOff2' => 0,
			);
			$arr2 = AdminUtils::query($row);
			$str .= "update s_PropertiesValues set {$arr2['update']} where HashValue = '{$hash}';\n";
		}
		return $str;
	}

	public static function addListField($id = 0, $type = "text")
	{
		if ((int) $id == 0) {
			return "";
		}
		$sql = "select Id,TableName,Field,Type from s_ConfigFields where Id='$id'";
		$res = Connect::$instance->fetch($sql);
		if (!isset($res[0]['Id'])) {
			return "";
		}
		$element = $res[0];
		$list = $element['TableName'];
		$field = $element['Field'];
		$sql = "SELECT COLUMN_NAME as Col FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '" . Connect::$projectDB['dbname'] . "' and TABLE_NAME = '$list'
                and COLUMN_NAME = '$field'
                ";
		$schemeReal = Connect::$instance->fetch($sql);

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
			$str = "ALTER TABLE $list CHANGE $field $field $typeReal $alterDefault";
		} else {
			$str = "ALTER TABLE $list ADD $field $typeReal $alterDefault";
		}
		return $str;
	}
	public static function removeListField($list = "", $field = "")
	{
	}

	public static function addList($list = "")
	{
		if ($list == "") {
			return "";
		}
		$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = '" . Connect::$projectDB['dbname'] . "' and TABLE_NAME = '$list'
                ";
		$schemeReal = Connect::$instance->fetch($sql);
		if (count($schemeReal) > 0) {
			return "";
		}
		#GUID char(36) COLLATE utf8_unicode_ci default null,
		$sql = "CREATE TABLE IF NOT EXISTS {$list} (
				Id int(11) NOT NULL auto_increment,
				Name varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL default '',
				Alias varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL default '',
				DisplayOff int(11) NOT NULL default '0',
				Priority int(11) NOT NULL default '0',
				PRIMARY KEY (Id)
			)
				ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
		$sql .= "DELETE FROM s_ConfigFields WHERE TableName = '{$list}';\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,Field,Priority,Required,Type,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','ID','Идентификатор','Id',1,0,'int','hidden','disabled','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,Field,Priority,Required,Type,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Заголовок','','Name',2,0,'text','','','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,Field,Priority,Required,Type,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Ключ','','Alias',3,0,'latin','','','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,Field,Priority,Required,Type,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Скрыть','','DisplayOff',4,0,'flag','','','FieldDefault');\n";
		$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,Field,Priority,Required,Type,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','Приоритет','','Priority',5,0,'int','hidden','','FieldDefault');\n";
		#$sql .= "INSERT ignore INTO s_ConfigFields (Id,TableName,Name,Description,Field,Priority,Required,Type,CreateMode,ModifyMode,FGroup) VALUES (null,'{$list}','GUID','','GUID',6,0,'guid','','','FieldIntegration');\n";
		return $sql;
	}
	public static function copyListItem($list = "", $id = 0, $path = "lists")
	{
		if ($list == "" || (int) $id == 0) {
			return array('status' => '0', 'output' => "");
		}
		$sql = "select * from $list where Id='$id'";
		$res = Connect::$instance->fetch($sql);
		if (!empty($res[0])) {
			$source = $res[0];
			$obj = new Data($list);
			if ($path == 'navigator') {
				$source['Url'] = "/rand" . date("u") . "-" . rand(101, 999) . "/";
			}
			if (isset($source['GUID'])) {
				$source['GUID'] = "";
			}
			/*
			 * Копирование элемента
			 * $newId = 5; - для тестирования
			 */
			unset($source['Id']);
			$newId = $obj->add($source);

			/*
			 * Копирование файлов
			 */
			$sql = "select * from s_Files where TableName='$list' and TableNameId='$id'";
			$res = Connect::$instance->fetch($sql);

			if (!empty($res[0])) {
				$objFiles = new Data("s_Files");
				foreach ($res as $value) {
					$file = $value;
					unset($file['Id']);
					$file['InnerName'] = $newId . substr($value['InnerName'], strpos($value['InnerName'], "_"));
					$file['FileUrl'] = substr($value['FileUrl'], 0, strrpos($value['FileUrl'], "/") + 1) . $file['InnerName'];
					$file['FileDate'] = date('Y-m-d H:i:s');
					$file['TableNameId'] = $newId;

					if (is_file(Connect::$projectDev['root'] . $value['FileUrl'])) {
						copy(Connect::$projectDev['root'] . $value['FileUrl'], Connect::$projectDev['root'] . $file['FileUrl']);
						$objFiles->add($file);
					}
				}
			}

			/*
			 * Копироваине свойств
			 */
			$sql = "select * from s_PropertiesValues where TableName='$list' and TableNameId='$id'";
			$res = Connect::$instance->fetch($sql);

			if (!empty($res[0])) {
				$objProps = new Data("s_PropertiesValues");
				foreach ($res as $value) {
					$pv = $value;
					unset($pv['Id']);
					$pv['TableNameId'] = $newId;
					$pv['HashValue'] = md5($pv['TableName'] . $pv['TableNameField'] . $pv['TableNameId'] . $pv['Name'] . $pv['PValue']);
					$objProps->add($pv);
				}
			}
		}
		$href = "'/_wepps/lists/{$list}/{$newId}/'";
		if ($path == 'navigator') {
			$href = "'/_wepps/navigator/{$source['Url']}/'";
		}
		$js = "
			<script>
			location.href={$href};
			</script>
		";
		return array('status' => '1', 'html' => $js);
	}
	public static function removeListItem($list = "", $id = 0, $path = "lists")
	{

		if ($list == "" || (int) $id == 0) {
			return array('status' => '0', 'html' => "");
		}

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
			$addActionRequest = new $addActionClass(array('listSettings' => $listSettings, 'id' => $id));
		}

		$href = "'/_wepps/lists/{$list}/'";
		if ($path == 'navigator') {
			$sql = "select d1.Id,d1.Url,d1.ParentDir,(select d2.Url from s_Navigator as d2 where d2.Id = d1.ParentDir) as ParentUrl from s_Navigator as d1 where d1.Id = '{$id}'";
			$res = Connect::$instance->fetch($sql);
			$href = "'/_wepps/navigator/{$res[0]['ParentUrl']}/'";
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
	public static function setSearchIndex($list = '', $id = 0)
	{
		switch ($list) {
			case '':
				$str = "truncate s_SearchKeys;\n";
				$conditions = "";
				$conditions2 = "";
				break;
			default:
				$str = "update s_SearchKeys sk set sk.DisplayOff2=1 where sk.Name=$id and sk.Field3 regexp 'List::{$list}::';\n";
				$conditions = "and TableName='$list'";
				$conditions2 = "where Id=$id";
				break;
		}
		$sql = "select * from s_ConfigFields where Type like 'select_multi%' $conditions order by TableName,Priority";
		$res = Connect::$instance->fetch($sql);
		foreach ($res as $value) {
			$tableName = $value['TableName'];
			$fieldName = $value['Field'];
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
					$rowData['DisplayOff2'] = 0;
					$arr = AdminUtils::query($rowData);
					$md5 = md5($rowData['Name'] . "::" . $rowData['Field1'] . "::" . $rowData['Field2'] . "::" . $rowData['Field3']);
					$str .= "insert ignore into s_SearchKeys (Alias) values ('$md5');\n";
					$str .= "update s_SearchKeys set " . $arr['update'] . " where Alias = '$md5';\n";
				}
			}
		}
		$str .= "update s_SearchKeys set DisplayOff=DisplayOff2;\n";
		return $str;
	}
}