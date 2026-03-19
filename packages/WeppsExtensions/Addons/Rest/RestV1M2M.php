<?php
namespace WeppsExtensions\Addons\Rest;

/**
 * REST обработчик для M2M-методов API v1
 * Маршрутизирует вызовы к специализированным классам (RestV1M2MUsers итп)
 * 
 * Users (machine-to-machine)
 */
class RestV1M2M extends RestV1
{
	// -------------------------------------------------------------------------
	// USERS (M2M)
	// -------------------------------------------------------------------------

	/**
	 * GET v1/m2m/users — список пользователей
	 */
	public function getUsers(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$users = new RestV1M2MUsers();
		return $users->getUsers();
	}

	/**
	 * GET v1/m2m/users.item — пользователь по id
	 */
	public function getUsersItem(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$users = new RestV1M2MUsers();
		return $users->getUsersItem();
	}

	/**
	 * POST v1/m2m/users — создание пользователя
	 */
	public function postUsers($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$users = new RestV1M2MUsers();
		
		// Передать данные из $data в RestM2MUsers
		if ($data && isset($data['data']) && is_array($data['data'])) {
			$users->setRequestData($data['data']);
		}
		
		return $users->postUsers();
	}

	/**
	 * PUT v1/m2m/users — обновление пользователя
	 */
	public function putUsers($data = null): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$users = new RestV1M2MUsers();
		
		// Передать данные из $data в RestM2MUsers
		if ($data && isset($data['data']) && is_array($data['data'])) {
			$users->setRequestData($data['data']);
		}
		
		return $users->putUsers();
	}

	/**
	 * DELETE v1/m2m/users — удаление пользователя
	 */
	public function deleteUsers(): array
	{
		/** @used Метод вызывается динамически через Rest::executeHandler() */
		$users = new RestV1M2MUsers();
		return $users->deleteUsers();
	}
}
