<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use PDO;
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
	public function generateProductsVariations()
	{
		if (ConnectWepps::$projectDev['debug'] == 0) {
			return;
		}
		$sql = "truncate ProductsVariations";
		$res = ConnectWepps::$instance->query($sql);
		$sql = "select Id,Name,NavigatorId from Products";
		$res = ConnectWepps::$instance->fetch($sql);
		$sth = ConnectWepps::$db->prepare("update Products set Variations = ?,Quantity = ? where Id = ?");
		foreach ($res as $value) {
			#$alias = TextTransformsWepps::translit($value['Name'], 2) . '-' . $value['Id'];
			$variations = self::_generateProductVariations($value);
			$sth->execute([$variations['variations'],$variations['quantitiy'], $value['Id']]);
		}
	}
	/**
	 * Генерирует массив вариаций товара с 3 случайными цветами и чётными размерами (42-48)
	 * 
	 * @return array Массив вариаций в формате ['color' => string, 'size' => int, ...]
	 */
	private function _generateProductVariations(array $product): array
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
		$quantitiyAmount = 0;
		foreach ($selectedColors as $color) {
			$selectedSizes = array_slice($sizes, 0, rand(2, 3));
			sort($selectedSizes);
			foreach ($selectedSizes as $size) {
				$sku = 'S'.$product['Id'].'-' . mb_strtoupper(mb_substr($color, 0, 3)) . '-' . $size;
				$quantitiy = rand(4,80);
				$variations[] = [
					'color' => $color,
					'size' => $size,
					'sku' => $sku,
					'quantity' => $quantitiy
				];
				$variations2 .= "{$color}:::{$size}:::{$sku}:::{$quantitiy}\n";
				$quantitiyAmount += $quantitiy;
			}
		}
		$variations2 = trim($variations2);
		return [
			'variations' => $variations2,
			'quantitiy' => $quantitiyAmount
		];
	}
	public function setProductsVariations(array $element) {
		$fn = function(int $id,array $value) : string {
			return md5($id.'_'.@$value[0].'_'.@$value[1].'_'.@$value[2]);
		};
		ConnectWepps::$instance->query("update ProductsVariations set DisplayOff=1 where ProductsId=?",[$element['Id']]);
		$data = UtilsWepps::arrayFromString($element['Variations'],':::');
		if (empty($data)) {
			return;
		}
		foreach ($data as $value) {
			$alias = $fn($element['Id'],$value);
			$ids[] = $alias;
		}
		$in = ConnectWepps::$instance->in($ids);
		$res = ConnectWepps::$instance->fetch("select Alias from ProductsVariations where Alias in ($in)",$ids);
		$existing = array_column($res,'Alias');
		$idsInsert = array_diff($ids, $existing);
		#$idsUpdate = array_intersect($ids, $existing);
		
		if (!empty($idsInsert)) {
			$stmt = ConnectWepps::$db->prepare("insert ignore into ProductsVariations (Alias) values (?)");
			foreach($idsInsert as $value) {
				$stmt->execute([$value]);
			}
		}
		$row = [
				'Name' => '',
				'DisplayOff' => 0,
				'Priority' => 0,
				'ProductsId' => '',
				'Field1' => '',
				'Field2' => '',
				'Field3' => '',
				'Field4' => ''
			];
		$prepare = ConnectWepps::$instance->prepare($row);
		$stmt = ConnectWepps::$db->prepare("update ProductsVariations set {$prepare['update']} where Alias=:Alias");
		$i=1;
		foreach ($data as $value) {
			$alias = $fn($element['Id'],$value);
			$stmt->execute($row = [
				'Name' => $value[2]??trim($element['Id'].'-'.@$value[0].'-'.@$value[1],'-'),
				'DisplayOff' => 0,
				'Priority' => $i++,
				'ProductsId' => $element['Id'],
				'Field1' => @$value[0],
				'Field2' => @$value[1],
				'Field3' => @$value[2],
				'Field4' => @$value[3],
				'Alias' => $alias
			]);
		}
		return;
	}
	public function resetProductsVariationsAll() {
		$res = ConnectWepps::$instance->fetch("select * from Products where Variations!=''");
		foreach ($res as $value) {
			$this->setProductsVariations($value);
		}
	}
	
}