<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use PDO;
use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsCore\TextTransforms;

class ProcessingProducts
{
	public function __construct()
	{

	}
	public function resetProducts()
	{
		if (Connect::$projectDev['debug'] == 0) {
			return;
		}
		try {
			Connect::$db->beginTransaction();
			$sql = "delete from s_PropertiesValues where TableName='Products' and TableNameId in (select p.Id from Products p where p.NavigatorId in (12,9))";
			Connect::$instance->query($sql);
			$sql = "delete from s_Files where TableName='Products' and TableNameId in (select p.Id from Products p where p.NavigatorId in (12,9))";
			Connect::$instance->query($sql);
			$sql = "delete from Products where NavigatorId in (12,9)";
			Connect::$instance->query($sql);
			Connect::$db->commit();
			$obj = new ProcessingTasks();
			$obj->removeFiles();
		} catch (\Exception $e) {
			Connect::$db->rollBack();
			echo "Error. See debug.conf";
			Utils::debug($e, 21);
		}
	}
	public function resetProductsAliases()
	{
		if (Connect::$projectDev['debug'] == 0) {
			return;
		}
		$sql = "select Id,Name,NavigatorId from Products";
		$res = Connect::$instance->fetch($sql);
		$sth = Connect::$db->prepare("update Products set Alias = ? where Id = ?");
		foreach ($res as $value) {
			$alias = TextTransforms::translit($value['Name'], 2) . '-' . $value['Id'];
			$sth->execute([$alias, $value['Id']]);
		}
	}
	public function generateProductsVariations()
	{
		if (Connect::$projectDev['debug'] == 0) {
			return;
		}
		$sql = "truncate ProductsVariations";
		$res = Connect::$instance->query($sql);
		$sql = "select Id,Name,NavigatorId from Products";
		$res = Connect::$instance->fetch($sql);
		$sth = Connect::$db->prepare("update Products set Variations = ?,Quantity = ? where Id = ?");
		foreach ($res as $value) {
			#$alias = TextTransforms::translit($value['Name'], 2) . '-' . $value['Id'];
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
	public function setProductsVariations(array $element): void
	{
		$data = Utils::arrayFromString($element['Variations'], ':::');
		$structured = array_map(fn($v) => [
			'field1' => trim($v[0] ?? ''),
			'field2' => trim($v[1] ?? ''),
			'field3' => trim($v[2] ?? ''),
			'field4' => trim($v[3] ?? ''),
		], $data);
		$this->upsertVariations((int) $element['Id'], $structured);
	}

	public function upsertVariations(int $productId, array $variations): array
	{
		Connect::$instance->query("update ProductsVariations set IsHidden=1 where ProductsId=?", [$productId]);

		if (empty($variations)) {
			return [];
		}

		// Строим маппинг из s_ConfigFields для таблицы ProductsVariations
		['reverse' => $reverseMap, 'forward' => $forwardMap] = $this->getVariationsFieldMap();

		// Нормализуем входные данные: API-ключи → DB-поля → индексированный массив
		$normalized = [];
		foreach ($variations as $v) {
			$dbRow = [];
			foreach ($v as $key => $value) {
				$dbField = $reverseMap[strtolower($key)] ?? null;
				if ($dbField !== null) {
					$dbRow[$dbField] = trim((string) $value);
				}
			}
			$indexed = [
				$dbRow['Field1'] ?? '',
				$dbRow['Field2'] ?? '',
				$dbRow['Field3'] ?? '',
				$dbRow['Field4'] ?? '',
			];
			$alias = $this->getProductsVariationsHash($productId, $indexed);
			$normalized[] = ['indexed' => $indexed, 'alias' => $alias];
		}

		$aliases = array_column($normalized, 'alias');
		$in = Connect::$instance->in($aliases);

		$res = Connect::$instance->fetch("select Alias from ProductsVariations where Alias in ($in)", $aliases);
		$existing = array_column($res, 'Alias');
		$toInsert = array_diff($aliases, $existing);

		if (!empty($toInsert)) {
			$stmt = Connect::$db->prepare("insert ignore into ProductsVariations (Alias) values (?)");
			foreach ($toInsert as $alias) {
				$stmt->execute([$alias]);
			}
		}

		$rowTemplate = ['Name' => '', 'IsHidden' => 0, 'Priority' => 0, 'ProductsId' => '', 'Field1' => '', 'Field2' => '', 'Field3' => '', 'Field4' => ''];
		$prepare = Connect::$instance->prepare($rowTemplate);
		$stmt = Connect::$db->prepare("update ProductsVariations set {$prepare['update']} where Alias=:Alias");

		$i = 1;
		foreach ($normalized as $item) {
			$v = $item['indexed'];
			$stmt->execute([
				'Name'      => $v[2] !== '' ? $v[2] : trim($productId . '-' . $v[0] . '-' . $v[1], '-'),
				'IsHidden'  => 0,
				'Priority'  => $i++,
				'ProductsId' => $productId,
				'Field1'    => $v[0],
				'Field2'    => $v[1],
				'Field3'    => $v[2],
				'Field4'    => $v[3],
				'Alias'     => $item['alias'],
			]);
		}

		// Возвращаем ID и поля с обратным маппингом DB → API-ключи
		$res = Connect::$instance->fetch(
			"select Id, Alias, Field1, Field2, Field3, Field4 from ProductsVariations where Alias in ($in)",
			$aliases
		);
		$byAlias = array_column($res, null, 'Alias');

		$result = [];
		foreach ($normalized as $item) {
			$row = $byAlias[$item['alias']] ?? null;
			if ($row) {
				$entry = ['id' => (int) $row['Id']];
				foreach (['Field1', 'Field2', 'Field3', 'Field4'] as $dbField) {
					if (isset($row[$dbField]) && $row[$dbField] !== '') {
						$apiKey = $forwardMap[$dbField] ?? strtolower($dbField);
						$entry[$apiKey] = $row[$dbField];
					}
				}
				$result[] = $entry;
			}
		}
		return $result;
	}

	public function getVariationsFieldMap(): array
	{
		$sql = "SELECT `Field`, `ApiMapping` FROM s_ConfigFields WHERE `TableName` = 'ProductsVariations'";
		$rows = Connect::$instance->fetch($sql);
		$reverseMap = []; // API-ключ (lowercase) → DB-поле (PascalCase)
		$forwardMap = []; // DB-поле (PascalCase) → API-ключ
		foreach ($rows as $row) {
			$dbField = $row['Field'];
			$apiKey  = $row['ApiMapping'] ?? null;
			if ($apiKey) {
				$reverseMap[strtolower($apiKey)] = $dbField;
				$forwardMap[$dbField] = $apiKey;
			}
			$reverseMap[strtolower($dbField)] = $dbField; // fallback: field1 → Field1
		}
		return ['reverse' => $reverseMap, 'forward' => $forwardMap];
	}
	public function getProductsVariationsHash(int $id, array $value): string
	{
		return md5($id . '_' . ($value[0] ?? '') . '_' . ($value[1] ?? '') . '_' . ($value[2] ?? ''));
	}

	/**
	 * Создать одну вариацию товара (без скрытия существующих).
	 * Если вариация с таким Alias уже есть — возвращает 409 с её ID.
	 *
	 * @param int $productId
	 * @param array $item данные вариации в API-формате (camelCase)
	 * @return array {status, message, data: {id, ...apiFields}}
	 */
	public function createVariation(int $productId, array $item): array
	{
		['reverse' => $reverseMap, 'forward' => $forwardMap] = $this->getVariationsFieldMap();

		// Маппируем API-поля в DB-поля
		$dbRow = [];
		foreach ($item as $key => $value) {
			$dbField = $reverseMap[strtolower($key)] ?? null;
			if ($dbField && !in_array($dbField, ['ProductsId', 'IsHidden', 'Priority', 'Alias', 'Name'])) {
				$dbRow[$dbField] = trim((string) $value);
			}
		}

		$field1 = $dbRow['Field1'] ?? '';
		$field2 = $dbRow['Field2'] ?? '';
		$field3 = $dbRow['Field3'] ?? '';
		$field4 = $dbRow['Field4'] ?? '';
		$alias  = $this->getProductsVariationsHash($productId, [$field1, $field2, $field3]);

		// Проверяем на дубликат
		$existing = Connect::$instance->fetch(
			"SELECT Id FROM ProductsVariations WHERE Alias = ?",
			[$alias]
		);
		if (!empty($existing)) {
			return [
				'status'  => 409,
				'message' => 'Duplicate Alias: ' . $alias,
				'data'    => ['id' => (int) $existing[0]['Id']],
			];
		}

		// Вставляем
		$sth = Connect::$db->prepare(
			"INSERT INTO ProductsVariations (Alias, ProductsId, Name, IsHidden, Priority, Field1, Field2, Field3, Field4)
			 VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?)"
		);
		$name = $field3 !== '' ? $field3 : trim($productId . '-' . $field1 . '-' . $field2, '-');
		$sth->execute([$alias, $productId, $name, $field1, $field2, $field3, $field4]);
		$id = (int) Connect::$db->lastInsertId();

		if ($id === 0) {
			// Рейс кондиция: INSERT сработал, но запись уже есть
			$row = Connect::$instance->fetch("SELECT Id FROM ProductsVariations WHERE Alias = ?", [$alias]);
			return [
				'status'  => 409,
				'message' => 'Duplicate Alias',
				'data'    => ['id' => (int) ($row[0]['Id'] ?? 0)],
			];
		}

		// Формируем ответ с API-ключами
		$responseData = ['id' => $id];
		foreach (['Field1' => $field1, 'Field2' => $field2, 'Field3' => $field3, 'Field4' => $field4] as $dbField => $val) {
			if ($val !== '') {
				$apiKey = $forwardMap[$dbField] ?? strtolower($dbField);
				$responseData[$apiKey] = $val;
			}
		}

		return ['status' => 201, 'message' => 'Created', 'data' => $responseData];
	}
	public function resetProductsVariationsAll() {
		$res = Connect::$instance->fetch("select * from Products where Variations!=''");
		foreach ($res as $value) {
			$this->setProductsVariations($value);
		}
	}
	public function setProductsFilesAPIFilter(int $id=0) {
		$conditions = "TableName='Products' and TableNameField='ImagesV'";
		$params = [];
		if (!empty($id)) {
			$conditions .= " and TableNameId=?";
			$params = [$id];
		}
		Connect::$instance->query("update s_Files set APIFilter=FileDescription where {$conditions}", $params);
	}
}