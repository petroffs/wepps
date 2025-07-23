<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;

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
}