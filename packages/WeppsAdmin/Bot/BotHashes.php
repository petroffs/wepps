<?
namespace WeppsAdmin\Bot;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class BotHashesWepps extends BotWepps {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function setHashes() {
		$sql = "select * from s_PropertiesValues;";
		$res = ConnectWepps::$instance->fetch($sql);
		$str = "";
		foreach ($res as $value) {
			$list = $value['TableName'];
			$field = $value['TableNameField'];
			$id = $value['TableNameId'];
			$prop = $value['Name'];
			$v = $value['PValue'];
			$hash = md5($list . $field . $id . $prop . $v);
			$str .= "update s_PropertiesValues set HashValue='{$hash}' where Id='{$value['Id']}';\n";
		}
		UtilsWepps::debugf($str);
	}
}
?>