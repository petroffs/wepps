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
		$sth = Connect::$db->prepare("update Products set Variations = ? where Id = ?");
		foreach ($res as $value) {
			#$alias = TextTransforms::translit($value['Name'], 2) . '-' . $value['Id'];
			$variations = self::_generateProductVariations($value);
			$sth->execute([$variations['variations'], $value['Id']]);
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
		$variations = '';
		$quantitiyAmount = 0;
		foreach ($selectedColors as $color) {
			$selectedSizes = array_slice($sizes, 0, rand(2, 3));
			sort($selectedSizes);
			foreach ($selectedSizes as $size) {
				$sku = 'S' . $product['Id'] . '-' . mb_strtoupper(mb_substr($color, 0, 3)) . '-' . $size;
				$quantitiy = rand(4, 80);
				$variations .= "{$color}:::{$size}:::{$sku}:::{$quantitiy}\n";
				$quantitiyAmount += $quantitiy;
			}
		}
		$variations = trim($variations);
		return [
			'variations' => $variations,
			'quantitiy' => $quantitiyAmount
		];
	}
	public function setProductsVariations(array $element): void
	{
		$data = Utils::arrayFromString($element['Variations'], ':::');
		$this->upsertVariations((int) $element['Id'], $data);
	}

	public function upsertVariations(int $productId, array $variations, bool $hideExisting = true): array
	{
		if ($hideExisting) {
			Connect::$instance->query("update ProductsVariations set IsHidden=1 where ProductsId=?", [$productId]);
		}

		if (empty($variations)) {
			return [];
		}

		// Подготовим хеши и дані разом
		$aliasesMap = [];
		foreach ($variations as $v) {
			$alias = $this->getProductsVariationsHash($productId, $v);
			$aliasesMap[$alias] = $v;
		}

		$aliases = array_keys($aliasesMap);
		$in = Connect::$instance->in($aliases);

		// Проверим существующие
		$res = Connect::$instance->fetch("select Id, Alias from ProductsVariations where Alias in ($in)", $aliases);
		$existing = array_column($res, 'Alias');
		$existingIds = array_column($res, 'Id', 'Alias');
		$toInsert = array_diff($aliases, $existing);
		$insertedIds = [];

		if (!empty($toInsert)) {
			$stmt = Connect::$db->prepare("insert ignore into ProductsVariations (Alias) values (?)");
			foreach ($toInsert as $alias) {
				$stmt->execute([$alias]);
				$insertedIds[$alias] = Connect::$db->lastInsertId();
			}
		}

		// Подготовим UPDATE
		$rowTemplate = ['Name' => '', 'IsHidden' => 0, 'Priority' => 0, 'ProductsId' => '', 'Field1' => '', 'Field2' => '', 'Field3' => '', 'Field4' => ''];
		$prepare = Connect::$instance->prepare($rowTemplate);
		$stmt = Connect::$db->prepare("update ProductsVariations set {$prepare['update']} where Alias=:Alias");

		// Обновим/вставим данные и собирем ID с типом действия
		$i = 1;
		$resultIds = [];
		foreach ($aliasesMap as $alias => $v) {
			// $v[0] = color, $v[1] = size, $v[2] = sku, $v[3] = stocks
			$stmt->execute([
				'Name' => $v[2] !== '' ? $v[2] : trim($productId . '-' . $v[0] . '-' . $v[1], '-'),
				'IsHidden' => 0,
				'Priority' => $i++,
				'ProductsId' => $productId,
				'Field1' => $v[0], // color
				'Field2' => $v[1], // size
				'Field3' => $v[2], // sku
				'Field4' => $v[3], // stocks
				'Alias' => $alias,
			]);
			// Определяем тип действия (создана новая или обновлена существующая)
			if (isset($insertedIds[$alias])) {
				$resultIds[] = ['id' => (int) $insertedIds[$alias], 'action' => 'created'];
			} else {
				$resultIds[] = ['id' => (int) ($existingIds[$alias] ?? 0), 'action' => 'updated'];
			}
		}

		return $resultIds;
	}
	public function getProductsVariationsHash(int $id, array $value): string
	{
		return md5($id . '_' . ($value[0] ?? '') . '_' . ($value[1] ?? '') . '_' . ($value[2] ?? ''));
	}
	public function resetProductsVariationsAll()
	{
		$res = Connect::$instance->fetch("select * from Products where Variations!=''");
		foreach ($res as $value) {
			$this->setProductsVariations($value);
		}
	}
	public function setProductsFilesAPIFilter(int $id = 0)
	{
		$conditions = "TableName='Products' and TableNameField='ImagesV'";
		$params = [];
		if (!empty($id)) {
			$conditions .= " and TableNameId=?";
			$params = [$id];
		}
		Connect::$instance->query("update s_Files set APIFilter=FileDescription where {$conditions}", $params);
	}
	/**
	 * Обновить вариации по ID (одна или batch).
	 * Переформировывает alias если изменились color/size/sku.
	 * 
	 * @param array $records Массив: [['id' => 123, 'color' => '...', 'size' => '...', 'sku' => '...', 'stocks' => '...'], ...]
	 * @return array Результаты: {'updated': [123], 'skipped': [456], 'conflict': [789], 'notFound': [999]}
	 *         - updated: успешно обновлены
	 *         - skipped: нет изменений в данных
	 *         - conflict: попытка установить alias, который уже используется
	 *         - notFound: ID вариации не существует
	 */
	public function updateVariations(array $records): array
	{
		if (empty($records)) {
			return [];
		}

		// Получить IDs из входных данных
		$ids = array_filter(array_column($records, 'id'));
		if (empty($ids)) {
			return [];
		}

		// Получить текущие данные
		$in = Connect::$instance->in($ids);
		$current = Connect::$instance->fetch(
			"SELECT Id, ProductsId, Field1, Field2, Field3, Field4, Alias FROM ProductsVariations WHERE Id IN ($in)",
			$ids
		);
		$currentMap = [];
		foreach ($current as $row) {
			$currentMap[(int) $row['Id']] = $row;
		}

		// Результаты по группам
		$resultGroups = [
			'updated' => [],
			'skipped' => [],        // Позиции без изменений
			'conflict' => [],       // Конфликты alias
			'notFound' => []
		];

		// Проверяем какие ID не найдены
		foreach ($ids as $id) {
			if (!isset($currentMap[$id])) {
				$resultGroups['notFound'][] = $id;
			}
		}

		// Первый проход: подготовить данные и собрать новые alias
		$updates = [];
		$newAliases = [];
		foreach ($records as $record) {
			$id = (int) ($record['id'] ?? 0);
			if (!$id || !isset($currentMap[$id])) {
				continue;
			}

			$curr = $currentMap[$id];
			$productId = (int) $curr['ProductsId'];

			// Новые значения из запроса (или текущие если не переданы)
			$newColor = trim($record['color'] ?? $curr['Field1'] ?? '');
			$newSize = trim($record['size'] ?? $curr['Field2'] ?? '');
			$newSku = trim($record['sku'] ?? $curr['Field3'] ?? '');
			$newStocks = trim($record['stocks'] ?? $curr['Field4'] ?? '');
			$newName = $newSku !== '' ? $newSku : trim($productId . '-' . $newColor . '-' . $newSize, '-');

			// Старый и новый alias
			$oldAlias = $curr['Alias'];
			$newAlias = $this->getProductsVariationsHash($productId, [$newColor, $newSize, $newSku]);
			$aliasChanged = ($oldAlias !== $newAlias);

			// Проверяем, изменилось ли хотя бы что-то
			$dataChanged = (
				$newColor !== $curr['Field1'] ||
				$newSize !== $curr['Field2'] ||
				$newSku !== $curr['Field3'] ||
				$newStocks !== $curr['Field4'] ||
				$newAlias !== $oldAlias
			);

			// Собираем данные для update
			$updates[] = [
				'id' => $id,
				'productId' => $productId,
				'alias' => $newAlias,
				'oldAlias' => $oldAlias,
				'name' => $newName,
				'color' => $newColor,
				'size' => $newSize,
				'sku' => $newSku,
				'stocks' => $newStocks,
				'aliasChanged' => $aliasChanged,
				'dataChanged' => $dataChanged,
			];

			// Собираем новые alias для проверки
			if ($aliasChanged) {
				$newAliases[$newAlias] = $id;
			}
		}

		// Проверим существующие новые alias один раз
		$existingAliases = [];
		if (!empty($newAliases)) {
			$newAliasesForCheck = array_keys($newAliases);
			$in = Connect::$instance->in($newAliasesForCheck);
			$res = Connect::$instance->fetch(
				"SELECT Alias FROM ProductsVariations WHERE Alias IN ($in) AND Id NOT IN (" . Connect::$instance->in($ids) . ")",
				array_merge($newAliasesForCheck, $ids)
			);
			foreach ($res as $row) {
				$existingAliases[$row['Alias']] = true;
			}
		}

		// Второй проход: обновить или пропустить
		$prepare = Connect::$instance->prepare([
			'Alias' => '',
			'Name' => '',
			'Field1' => '',
			'Field2' => '',
			'Field3' => '',
			'Field4' => '',
			'Id' => ''
		]);
		$stmt = Connect::$db->prepare("UPDATE ProductsVariations SET {$prepare['update']},IsHidden=0 WHERE Id=:Id");
		
		foreach ($updates as $upd) {
			// Если данные не изменились - пропускаем
			if (!$upd['dataChanged']) {
				$resultGroups['skipped'][] = $upd['id'];
				continue;
			}

			// Если alias изменился и уже существует - конфликт
			if ($upd['aliasChanged'] && isset($existingAliases[$upd['alias']])) {
				$resultGroups['conflict'][] = $upd['id'];
				continue;
			}

			$prepare['row']['Alias'] = $upd['alias'];
			$prepare['row']['Name'] = $upd['name'];
			$prepare['row']['Field1'] = $upd['color'];
			$prepare['row']['Field2'] = $upd['size'];
			$prepare['row']['Field3'] = $upd['sku'];
			$prepare['row']['Field4'] = $upd['stocks'];
			$prepare['row']['Id'] = $upd['id'];

			$stmt->execute($prepare['row']);
			$resultGroups['updated'][] = $upd['id'];
		}

		return $resultGroups;
	}

	public function getVariationsFieldMap(): array
	{
		$sql = "SELECT `TableField`, `ApiMapping` FROM s_ConfigFields WHERE `TableName` = 'ProductsVariations' order by Priority asc";
		$rows = Connect::$instance->fetch($sql);
		$reverseMap = []; // API-ключ (lowercase) → DB-поле (PascalCase)
		$forwardMap = []; // DB-поле (PascalCase) → API-ключ
		foreach ($rows as $row) {
			$dbField = $row['TableField'];
			$apiKey = $row['ApiMapping'] ?? null;
			if ($apiKey) {
				$reverseMap[strtolower($apiKey)] = $dbField;
				$forwardMap[$dbField] = $apiKey;
			}
			//$reverseMap[strtolower($dbField)] = $dbField; // fallback: field1 → Field1
		}
		//Utils::debug($forwardMap, 21);
		return ['reverse' => $reverseMap, 'forward' => $forwardMap];
	}
}