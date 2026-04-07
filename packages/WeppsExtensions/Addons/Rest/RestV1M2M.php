<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;

/**
 * RestV1M2M - M2M API для работы с таблицами через CRUD операции
 * 
 * Использует упрощённый подход - явные методы для каждой таблицы:
 * - getUsers, postUsers, putUsers, deleteUsers
 * - getProducts, postProducts, putProducts, deleteProducts
 * - getOrders, postOrders, putOrders, deleteOrders
 * 
 * Все методы используют единый helper для работы с БД.
 * Конфигурация берётся из s_Config и s_ConfigFields.
 * Валидация данных берётся из s_ConfigFields через RestV1M2MUtils.
 */
class RestV1M2M extends RestV1
{
	/**
	 * Utils для CRUD операций
	 */
	private ?RestV1M2MUtils $utils = null;

	/**
	 * Получить utils (lazy init)
	 */
	protected function getUtils(): RestV1M2MUtils
	{
		if ($this->utils === null) {
			$this->utils = new RestV1M2MUtils();
		}
		return $this->utils;
	}

	/**
	 * Валидировать GET параметры на основе s_ConfigFields
	 * 
	 * @param string $tableName - имя таблицы для получения правил
	 * @param array $data - данные для валидации
	 * @throws \Exception если валидация не пройдена
	 */
	protected function validateQueryParams(string $tableName, array $data): void
	{
		$rules = $this->getUtils()->getFieldRules($tableName);
		if (empty($rules)) {
			// Если правил нет в БД, используем ослабленную валидацию
			return;
		}

		// Валидация через родительский класс Rest
		$this->rest->validateData($data, $rules);
	}

	/**
	 * Валидировать POST данные на основе s_ConfigFields
	 * 
	 * @param string $tableName - имя таблицы для получения правил
	 * @param array $data - данные для валидации
	 * @throws \Exception если валидация не пройдена
	 */
	protected function validatePostData(string $tableName, array $data): void
	{
		$rules = $this->getUtils()->getFieldRules($tableName);
		if (empty($rules)) {
			// Если правил нет в БД, используем ослабленную валидацию
			return;
		}

		// Валидация через родительский класс Rest
		$this->rest->validateData($data, $rules);
	}

	// ========================================================================
	// USERS
	// ========================================================================

	public function getUsers(): array
	{
		// GET параметры - служебные (page, limit, search, sort)
		// Валидация минимальна, правила берутся из s_ConfigFields при необходимости
		return $this->getUtils()->fetch('s_Users', $this->get);
	}

	public function getUsersItem(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->item('s_Users', $id);
	}

	public function postUsers($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		
		// Валидировать POST данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('s_Users', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
		
		return $this->getUtils()->add('s_Users', $data);
	}

	public function putUsers($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		$id = $data['id'] ?? $data['Id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		
		// Валидировать PUT данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('s_Users', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
		
		return $this->getUtils()->set('s_Users', $id, $data);
	}

	public function deleteUsers(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->remove('s_Users', $id);
	}

	// ========================================================================
	// PRODUCTS
	// ========================================================================

	public function getProducts(): array
	{
		return $this->getUtils()->fetch('Products', $this->get);
	}

	public function getProductsItem(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->item('Products', $id);
	}

	public function postProducts($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		
		// Валидировать POST данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Products', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
		
		return $this->getUtils()->add('Products', $data);
	}

	public function putProducts($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		$id = $data['id'] ?? $data['Id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		
		// Валидировать PUT данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Products', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
		
		return $this->getUtils()->set('Products', $id, $data);
	}

	public function deleteProducts(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->remove('Products', $id);
	}

	// ========================================================================
	// ORDERS
	// ========================================================================

	public function getOrders(): array
	{
		return $this->getUtils()->fetch('Orders', $this->get);
	}

	public function getOrdersItem(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->item('Orders', $id);
	}

	public function postOrders($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		
		// Валидировать POST данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Orders', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
		
		return $this->getUtils()->add('Orders', $data);
	}

	public function putOrders($data = null): array
	{
		$data = $this->extractRequestData($data);
		if (!$data) {
			return ['status' => 400, 'message' => 'No data', 'data' => null];
		}
		$id = $data['id'] ?? $data['Id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		
		// Валидировать PUT данные по правилам из s_ConfigFields
		try {
			$this->validatePostData('Orders', $data);
		} catch (\Exception $e) {
			return ['status' => 400, 'message' => $e->getMessage(), 'data' => null];
		}
		
		return $this->getUtils()->set('Orders', $id, $data);
	}

	public function deleteOrders(): array
	{
		$id = $this->get['id'] ?? 0;
		if (!$id) {
			return ['status' => 400, 'message' => 'ID required', 'data' => null];
		}
		return $this->getUtils()->remove('Orders', $id);
	}

	// ========================================================================
	// UTILITIES
	// ========================================================================

	protected function extractRequestData($data): array
	{
		if ($data && isset($data['data']) && is_array($data['data'])) {
			return $data['data'];
		}

		$input = file_get_contents('php://input');
		if ($input) {
			$decoded = @json_decode($input, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}

		return [];
	}
}
