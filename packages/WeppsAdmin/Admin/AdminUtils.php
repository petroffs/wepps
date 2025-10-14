<?php
namespace WeppsAdmin\Admin;

use WeppsCore\Cli;
use WeppsCore\Utils;
use WeppsCore\Connect;

class AdminUtils extends Utils {
	public static function modal(string $message = '', Cli $cli = null)
	{
		if (!empty($cli)) {
			$cli->info($message);
			Connect::$instance->close();
			return;
		}

		$js = "
			<script>
				var dialogWidth = (window.screen.width<400) ? '90%' : 400;
				$('#dialog').html('<p>{$message}</p>').dialog({
					title:'Сообщение',
					modal: true,
					resizable: false,
      				width: dialogWidth,
   					buttons : [
						{
							text : 'ОК',
							icon : 'ui-icon-check',
							click : function() {
								$(this).dialog('close');
							}
						},{
							text : 'Обновить',
							icon : 'ui-icon-refresh',
							click : function() {
								location.reload();
							}
						}]
				});
			</script>
		";
		echo $js;
		Connect::$instance->close();
		return;
	}
	/**
	 * Компоновка SQL запроса на основе входного массива array('Row1'=>'ROW1','Row2'=>'ROW2');
	 * @param array $row
	 * @return array
	 */
	public static function query(array $row): array
	{
		$strCond = $strUpdate = $strInsert1 = $strInsert2 = $strSelect = "";
		if (empty($row)) {
			return [];
		}
		foreach ($row as $key => $value) {
			$value = self::trim(str_replace(["&gt;", "&lt;", "&quot;"], [">", "<", "\""], $value));
			$value1 = (empty($value)) ? "null" : "'{$value}'";
			if (strstr($key, '@')) {
				$key = str_replace('@', '', $key);
				$value1 = $value;
				$strUpdate .= "{$key}={$value}, ";
				$strInsert2 .= "{$value},";
			} elseif ($value1 == "null" && $key == "GUID") {
				$value1 = "uuid()";
				$strUpdate .= "{$key}=uuid(), ";
				$strInsert2 .= "uuid(),";
			} else {
				$strUpdate .= "{$key}=" . Connect::$db->quote($value) . ", ";
				$strInsert2 .= "" . Connect::$db->quote($value) . ",";
			}
			$strInsert1 .= "$key,";
			$strCond .= "{$key}={$value1} and ";
			$strSelect .= "{$value1} {$key}, ";
		}
		$strCond = ($strCond != "") ? trim($strCond, " and ") : "";
		$strCond = str_replace("\n", "\\n", $strCond);
		$strUpdate = ($strCond != "") ? trim($strUpdate, ", ") : "";
		$strUpdate = str_replace("\r\n", "\\n", $strUpdate);
		$strInsert = ($strCond != "") ? "(" . trim($strInsert1, ",") . ") values (" . trim($strInsert2, ",") . ")" : "";
		$strInsert = str_replace("\n", "\\n", $strInsert) . ";";
		$strSelect = ($strCond != "") ? trim($strSelect, ", ") : "";
		$strSelect = str_replace("\r\n", "\\n", $strSelect);
		$outer = ($strCond != "") ? [
			"insert" => $strInsert,
			"update" => $strUpdate,
			"condition" => $strCond,
			"select" => $strSelect
		] : [];
		return $outer;
	}
}