<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Spell\SpellWepps;

class ProcessingProductsWepps {
	public function __construct() {
		
	}
	public function resetProducts() {
		try {
			ConnectWepps::$db->beginTransaction();
			$sql = "delete from s_PropertiesValues where TableName='Products' and TableNameId in (select p.Id from Products p where p.NavigatorId in (12,9))";
			ConnectWepps::$instance->query($sql);
			$sql = "delete from s_Files where TableName='Products' and TableNameId in (select p.Id from Products p where p.NavigatorId in (12,9))";
			ConnectWepps::$instance->query($sql);
			$sql = "delete from Products where NavigatorId in (12,9)";
			ConnectWepps::$instance->query($sql);
			ConnectWepps::$db->commit();
			$obj = new ProcessingTasksWepps();
			$obj->removeFiles();
		} catch (\Exception $e) {
			ConnectWepps::$db->rollBack();
			echo "Error. See debug.conf";
			UtilsWepps::debug($e,21);
		}
	}
	public function changeProductsNames() {
		return;
		/*
		 * Товары переименовать
		 */
		$t = -1;
		if ($t==0) {
			try {
				ConnectWepps::$db->beginTransaction();
				$sql = "select Id,Name from Products";
				$res = ConnectWepps::$instance->fetch($sql);
				$update = ConnectWepps::$db->prepare("update Products set Name=?,Alias=? where Id=?");
				foreach ($res as $value) {
					$name = $this->shiftLetters($value['Name']);
					$alias = SpellWepps::getTranslit($name."-".$value['Id'],2);
					$update->execute([$name,$alias,$value['Id']]);
				}
				ConnectWepps::$db->commit();
			} catch (\Exception $e) {
				ConnectWepps::$db->rollBack();
				echo "Error. See debug.conf";
				UtilsWepps::debug($e,21);
			}
		}
		
		/*
		 * Бренд в фильтрах переименовать
		 */
		if ($t==1) {
			try {
				ConnectWepps::$db->beginTransaction();
				$sql = "select * from s_PropertiesValues pv where pv.TableName = 'Products' and pv.Name=1";
				$res = ConnectWepps::$instance->fetch($sql);
				$update = ConnectWepps::$db->prepare("update s_PropertiesValues set Alias=?,PValue=?,HashValue=? where Id=?");
				
				$list = $res[0]['TableName'];
				$field = $res[0]['TableNameField'];
				$prop = $res[0]['Name'];
				
				foreach ($res as $value) {
					$id = $value['TableNameId'];
					$v = $this->shiftLetters($value['PValue']);
					$alias = SpellWepps::getTranslit($v,2);
					$hash = md5($list . $field . $id . $prop . $v);
					$update->execute([$alias,$v,$hash,$value['Id']]);
				}
				ConnectWepps::$db->commit();
			} catch (\Exception $e) {
				ConnectWepps::$db->rollBack();
				echo "Error. See debug.conf";
				UtilsWepps::debug($e,21);
			}
		}
		
		
	}
	private function shiftLetters($str) {
		$lower_vowels = ['a', 'e', 'i', 'o', 'u'];
		$upper_vowels = ['A', 'E', 'I', 'O', 'U'];
		
		$lower_consonants = array_values(array_diff(range('a', 'z'), $lower_vowels));
		$upper_consonants = array_values(array_diff(range('A', 'Z'), $upper_vowels));
		
		$result = '';
		
		for ($i = 0; $i < strlen($str); $i++) {
			$char = $str[$i];
			
			if (ctype_alpha($char)) {
				$vowels = null;
				$consonants = null;
				
				if (in_array($char, $lower_vowels)) {
					$vowels = $lower_vowels;
				} elseif (in_array($char, $upper_vowels)) {
					$vowels = $upper_vowels;
				} elseif (in_array($char, $lower_consonants)) {
					$consonants = $lower_consonants;
				} elseif (in_array($char, $upper_consonants)) {
					$consonants = $upper_consonants;
				}
				
				if ($vowels !== null) {
					$index = array_search($char, $vowels);
					$next_index = ($index + 1) % count($vowels);
					$result .= $vowels[$next_index];
				} elseif ($consonants !== null) {
					$index = array_search($char, $consonants);
					$next_index = ($index + 1) % count($consonants);
					$result .= $consonants[$next_index];
				} else {
					$result .= $char;
				}
			} else {
				$result .= $char;
			}
		}
		
		return $result;
	}
}