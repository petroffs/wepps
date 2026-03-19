<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;

/**
 * REST обработчик для M2M-методов API v1
 * Users (machine-to-machine)
 */
class RestV1M2M extends RestV1
{
	// -------------------------------------------------------------------------
	// USERS (M2M)
	// -------------------------------------------------------------------------

	/**
	 * GET v1/users — список пользователей
	 */
	public function getUsers(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$page = max(1, (int)($this->get['page'] ?? 1));
		$limit = min(100, max(1, (int)($this->get['limit'] ?? 20)));
		$search = $this->get['search'] ?? '';

		$conditions = "IsHidden = 0";
		$params = [];

		if ($search !== '') {
			$conditions .= " AND (lower(Login) LIKE lower(?) OR lower(Name) LIKE lower(?))";
			$params[] = '%' . $search . '%';
			$params[] = '%' . $search . '%';
		}

		$offset = ($page - 1) * $limit;
		$countRes = Connect::$instance->fetch("SELECT COUNT(*) as cnt FROM s_Users WHERE {$conditions}", $params);
		$total = (int)($countRes[0]['cnt'] ?? 0);

		$res = Connect::$instance->fetch(
			"SELECT Id, Login, Name, Phone, UserPermissions FROM s_Users WHERE {$conditions} ORDER BY Id DESC LIMIT ? OFFSET ?",
			array_merge($params, [$limit, $offset])
		);

		return ['status' => 200, 'message' => 'OK', 'data' => $res ?? [], 'count' => $total];
	}

	/**
	 * GET v1/users.item — пользователь по id
	 */
	public function getUsersItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int)($this->get['id'] ?? 0);

		$res = Connect::$instance->fetch(
			"SELECT Id, Login, Name, Phone, UserPermissions FROM s_Users WHERE Id = ? AND IsHidden = 0",
			[$id]
		);

		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'User not found', 'data' => null];
		}

		return ['status' => 200, 'message' => 'OK', 'data' => $res[0]];
	}

	/**
	 * POST v1/users — создание пользователя
	 */
	public function postUsers($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$login = strtolower(trim($data['data']['login'] ?? ''));
		$password = $data['data']['password'] ?? '';
		$name = $data['data']['name'] ?? '';
		$phone = $data['data']['phone'] ?? '';

		$exists = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Login = ?", [$login]);
		if (!empty($exists[0])) {
			return ['status' => 409, 'message' => 'User with this email already exists', 'data' => null];
		}

		$hash = password_hash($password, PASSWORD_DEFAULT);
		Connect::$instance->query(
			"INSERT INTO s_Users (Login, Password, Name, Phone, IsHidden) VALUES (?, ?, ?, ?, 0)",
			[$login, $hash, $name, $phone]
		);
		$id = Connect::$db->lastInsertId();

		return ['status' => 200, 'message' => 'User created', 'data' => ['id' => (int)$id]];
	}

	/**
	 * PUT v1/users — обновление пользователя
	 */
	public function putUsers($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$id = (int)($data['data']['id'] ?? 0);
		$name = $data['data']['name'] ?? null;
		$phone = $data['data']['phone'] ?? null;
		$email = $data['data']['email'] ?? null;

		$res = Connect::$instance->fetch("SELECT Id FROM s_Users WHERE Id = ? AND IsHidden = 0", [$id]);
		if (empty($res[0])) {
			return ['status' => 404, 'message' => 'User not found', 'data' => null];
		}

		$set = [];
		$params = [];

		if ($name !== null) { $set[] = 'Name = ?'; $params[] = $name; }
		if ($phone !== null) { $set[] = 'Phone = ?'; $params[] = $phone; }
		if ($email !== null) { $set[] = 'Login = ?'; $params[] = strtolower($email); }

		if (empty($set)) {
			return ['status' => 400, 'message' => 'No fields to update', 'data' => null];
		}

		$params[] = $id;
		Connect::$instance->query("UPDATE s_Users SET " . implode(', ', $set) . " WHERE Id = ?", $params);

		return ['status' => 200, 'message' => 'User updated', 'data' => null];
	}
}
