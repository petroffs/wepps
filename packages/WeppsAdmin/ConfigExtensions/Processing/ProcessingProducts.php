<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;

class ProcessingProductsWepps
{
	public function __construct()
	{

	}
	public function resetProducts()
	{
		if (ConnectWepps::$projectDev['debug'] == 0) {
			return;
		}
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
			UtilsWepps::debug($e, 21);
		}
	}
	public function resetProductsAliases()
	{
		if (ConnectWepps::$projectDev['debug'] == 0) {
			return;
		}
		$sql = "select Id,Name,NavigatorId from Products";
		$res = ConnectWepps::$instance->fetch($sql);
		$sth = ConnectWepps::$db->prepare("update Products set Alias = ? where Id = ?");
		foreach ($res as $value) {
			$alias = TextTransformsWepps::translit($value['Name'], 2) . '-' . $value['Id'];
			$sth->execute([$alias, $value['Id']]);
		}
	}
	public function resetProductsVariations()
	{
		if (ConnectWepps::$projectDev['debug'] == 0) {
			return;
		}
		$sql = "truncate ProductsVariants";
		$res = ConnectWepps::$instance->query($sql);

		$sql = "select Id,Name,NavigatorId from Products";
		$res = ConnectWepps::$instance->fetch($sql);
		

		$sth = ConnectWepps::$db->prepare("update Products set Variations = ? where Id = ?");
		foreach ($res as $value) {
			#$alias = TextTransformsWepps::translit($value['Name'], 2) . '-' . $value['Id'];
			$variations = self::_generateProductVariations($value);
			$sth->execute([$variations, $value['Id']]);
		}
	}
	/**
	 * Генерирует массив вариаций товара с 3 случайными цветами и чётными размерами (42-48)
	 * 
	 * @return array Массив вариаций в формате ['color' => string, 'size' => int, ...]
	 */
	private function _generateProductVariations(array $product): string
	{
		// Доступные цвета
		$availableColors = ['Красный', 'Синий', 'Зеленый', 'Черный', 'Белый', 'Желтый', 'Фиолетовый', 'Розовый'];

		// 3 случайных цвета (без повторов)
		shuffle($availableColors);
		$selectedColors = array_slice($availableColors, 0, rand(2, 4));

		// размеры
		$sizes = [42, 44, 46, 48];
		if ($product['NavigatorId'] == 8) {
			$sizes = [46, 48, 50, 52, 54, 56];
		}
		shuffle($sizes);
		
		// Генерируем все комбинации цветов и размеров
		##$variations = [];
		$variations2 = '';
		foreach ($selectedColors as $color) {
			$selectedSizes = array_slice($sizes, 0, rand(2, 3));
			sort($selectedSizes);
			foreach ($selectedSizes as $size) {
				$sku = 'S'.$product['Id'].'-' . mb_strtoupper(mb_substr($color, 0, 3)) . '-' . $size;
				$variations[] = [
					'color' => $color,
					'size' => $size,
					'sku' => $sku,
				];
				$variations2 .= "{$color}:::{$size}:::{$sku}\n";
			}
		}
		$variations2 = trim($variations2);
		return $variations2;
	}
}