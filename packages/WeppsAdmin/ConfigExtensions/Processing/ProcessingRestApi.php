<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WeppsCore\Connect;
use WeppsCore\Utils;

class ProcessingRestApi
{
	public function mappingTypes(): void
	{
		$sql = "UPDATE s_ConfigFields SET ApiFieldType = CASE 
			WHEN `Type` = 'int' THEN 'int'
			WHEN `Type` = 'flag' THEN 'int'
			WHEN `Type` = 'guid' THEN 'guid'
			WHEN `Type` = 'date' THEN 'date'
			WHEN `Type` = 'email' THEN 'email'
			WHEN `Type` = 'digit' THEN 'string' -- В основном для финансовых даннвх, поэтому оставляем как string, чтобы не было проблем с точностью
			ELSE 'string'
		END
		WHERE ApiFieldType IS NULL OR ApiFieldType = ''";
		Connect::$instance->query($sql);
	}
	public function mappingNames(): void
	{
		$sql = "SELECT Id, Field FROM s_ConfigFields WHERE ApiMapping IS NULL OR ApiMapping = '' ORDER BY TableName, Field";
		$fields = Connect::$instance->fetch($sql);

		if (empty($fields)) {
			return;
		}

		$updateSql = "UPDATE s_ConfigFields SET ApiMapping = ? WHERE Id = ?";
		$updatedCount = 0;

		foreach ($fields as $field) {
			$apiMapping = $this->fieldApiMappingToCamelCase($field['Field']);
			$result = Connect::$instance->query($updateSql, [$apiMapping, $field['Id']]);
			if ($result > 0) {
				$updatedCount++;
			}
		}
	}

	/**
	 * Преобразует имя поля БД в camelCase формат для REST API
	 * 
	 * @param string $key Имя поля из БД
	 * @return string Преобразованное имя в camelCase
	 */
	private function fieldApiMappingToCamelCase(string $key): string
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