<?php

namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsAdmin\Lists\Lists;
use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsExtensions\Addons\Bot\BotSystem;

class UploadsExcelFields
{
	private $settings;
	private $validator;
	private $tableName = 's_ConfigFields';

	public function __construct($settings)
	{
		$this->settings = $settings;
		$this->validator = $this->getValidator();
	}

	public function setData()
	{
		if ($this->validator['status'] == 200) {
			// Шаг 1: собрать DDL и данные для вставки
			$ddlStatements = [];
			$rows = [];
			unset($this->settings['data'][1]);
			foreach ($this->settings['data'] as $value) {
				if (!empty($value['A']) && !empty($value['B']) && !empty($value['C']) && !empty($value['D'])) {
					$row = [
						"TableName" => $value['A'],
						"Name" => $value['B'],
						"Description" => (!empty($value['F'])) ? $value['F'] : '',
						"TableField" => $value['C'],
						"FType" => $value['D'],
						"FGroup" => $value['E'],
					];
					$rows[] = $row;
					$ddlStatements[] = Lists::addListFieldDdl($row['TableName'], $row['TableField'], $row['FType']);
				}
			}

			if (empty($ddlStatements)) {
				return ['status' => 400, 'message' => '❌ Нет полей для добавления'];
			}

			// Шаг 2: выполнить DDL (ALTER TABLE) — если упадёт, данные не запишутся
			$ddlSql = implode(";\n", $ddlStatements) . ";";
			try {
				Connect::$db->exec($ddlSql);
			} catch (\Exception $e) {
				return [
					'status' => 500,
					'message' => '❌ Ошибка SQL: ' . $e->getMessage(),
				];
			}

			// Шаг 3: INSERT'ы в s_ConfigFields — в транзакции
			try {
				$insertedIds = Connect::$instance->transaction(function ($args) use ($rows) {
					$obj = new Data($this->tableName);
					$ids = [];
					foreach ($rows as $row) {
						$sql = "delete from s_ConfigFields where TableName='' and TableField=''";
						Connect::$instance->query($sql);
						$res = $obj->fetchmini("TableName = '{$row['TableName']}' and TableField = '{$row['TableField']}'");
						if (!isset($res[0]['Id'])) {
							$id = $obj->add($row);
							if ((int) $id != 0) {
								$ids[] = $id;
							}
						}
					}
					return $ids;
				}, []);
			} catch (\Exception $e) {
				return [
					'status' => 500,
					'message' => '❌ Ошибка при добавлении полей: ' . $e->getMessage(),
				];
			}

			// Шаг 4: обновить ApiMapping/ApiFieldType для новых полей
			foreach ($insertedIds as $id) {
				Lists::updateListFieldApi($id);
			}

			$obj = new BotSystem();
			$obj->clearCache();
			return ['status' => 200, 'message' => '✅ Новые поля добавлены'];
		}
		return $this->validator;
	}

	private function getValidator()
	{
		if (!empty($this->settings['data'][1])) {
			if (
				$this->settings['data'][1]['A'] == 'Список' &&
				$this->settings['data'][1]['B'] == 'Наименование' &&
				$this->settings['data'][1]['C'] == 'Alias' &&
				$this->settings['data'][1]['D'] == 'Тип' &&
				$this->settings['data'][1]['E'] == 'Группа' &&
				$this->settings['data'][1]['F'] == 'Описание'
			) {
				return [
					'status' => 200,
					'message' => '✅ Формат OK'
				];
			}
		}
		$out = [
			'status' => 400,
			'message' => '❌ Формат некорректный'
		];
		return $out;
	}
}