<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Profile;

use WeppsCore\Connect;
use WeppsCore\Tasks;
use WeppsCore\Navigator;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\Messages\Mail\Mail;

class ProfileUtils
{
	private $navigator = [];
	private $user = [];
	public function __construct(array $user)
	{
		$this->user = $user;
	}
	public function setNavigator(Navigator $navigator): void
	{
		$this->navigator = $navigator;
	}
	/**
	 * @return string[][]
	 *
	 * @psalm-return list{0: array{title: 'Вход'|'Профайл', alias: '', url: '/profile/', event?: ''}, 1: array{title: 'Мои заказы'|'Регистрация', alias: 'orders'|'reg', url: '/profile/orders.html'|'/profile/reg.html', event?: ''}, 2: array{title: 'Восстановление доступа'|'Избранное', alias: 'favorites'|'password', url: '/profile/favorites.html'|'/profile/password.html', event?: ''}, 3?: array{title: 'Настройки', alias: 'settings', url: '/profile/settings.html'}, 4?: array{title: 'Выход', alias: 'signout', url: '/profile/', event: 'sign-out'}}
	 */
	public function getNav($pathItem = ''): array
	{
		if ($this->user !== []) {
			$nav = [
				[
					'title' => 'Профайл',
					'alias' => '',
					'url' => '/profile/',
				],
				[
					'title' => 'Мои заказы',
					'alias' => 'orders',
					'url' => '/profile/orders.html',
				],
				[
					'title' => 'Избранное',
					'alias' => 'favorites',
					'url' => '/profile/favorites.html',
				],
				[
					'title' => 'Настройки',
					'alias' => 'settings',
					'url' => '/profile/settings.html',
				],
				[
					'title' => 'Выход',
					'alias' => 'signout',
					'url' => '/profile/',
					'event' => 'sign-out',
				],
			];
			return $nav;
		}
		$nav = [
			[
				'title' => 'Вход',
				'alias' => '',
				'url' => '/profile/',
				'event' => '',
			],
			[
				'title' => 'Регистрация',
				'alias' => 'reg',
				'url' => '/profile/reg.html',
				'event' => '',
			],
			[
				'title' => 'Восстановление доступа',
				'alias' => 'password',
				'url' => '/profile/password.html',
				'event' => '',
			],
		];
		return $nav;
	}
	public function getFavorites(): void
	{
		// $jdata = json_decode($this->partner['JFav'], true);
		// if (!is_array($jdata)) {
		// 	return false;
		// }
		// $ids = implode(',', array_keys($jdata));
		// if (empty($ids)) {
		// 	return false;
		// }
		// $conditions = "t.Id in ($ids)";
		// $settings = [
		// 	'page' => @$_GET['page'],
		// 	'condition' => $conditions,
		// 	'conditionSelf' => $conditions,
		// 	'orderBy' => "FIELD(t.Id,$ids)"
		// ];
		// $products = Products::getProducts($settings);
		// return $products;
	}

	public function getOrders($id = 0): void
	{

	}
	/**
	 * Обрабатывает задачу на восстановление пароля пользователя.
	 *
	 * Декодирует входные данные, формирует уведомление по электронной почте с ссылкой
	 * для установки нового пароля и обновляет статус задачи в системе.
	 *
	 * @param array $request Массив данных задачи, включая:
	 *                       - BRequest (JSON-строка с токеном, именем и email)
	 *                       - Id (идентификатор задачи)
	 * @param Tasks $tasks Экземпляр класса для управления задачами
	 * @return mixed Результат обновления задачи (вероятно, ответ с HTTP-статусом)
	 */
	public function processPasswordTask(array $request, Tasks $tasks)
	{
		$jdata = json_decode($request['BRequest'], true);
		$url = 'https://' . Connect::$projectDev['host'] . "/profile/?token=" . rawurlencode((string) $jdata['token']);
		$text = "<b>Добрый день, {$jdata['nameFirst']}!</b><br/><br/>Поступил запрос на смену пароля!";
		$text .= "<br/><br/>Для установки нового пароля перейдите по ссылке:";
		$text .= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Установить новый пароль</a></center>";
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail($jdata['email'], "Восстановление доступа", $text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'], $response, 200);
	}
	public function processPasswordConfirmTask(array $request, Tasks $tasks): array
	{
		$jdata = json_decode($request['BRequest'], true);
		$url = 'https://' . Connect::$projectDev['host'] . "/profile/";
		$text = "<b>Добрый день, {$jdata['nameFirst']}!</b><br/><br/>Ваш пароль в Личном кабинете изменен!";
		$text .= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Перейти в Личный кабинет</a></center>";
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail($jdata['email'], "Пароль изменен", $text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'], $response, 200);
	}
	public function processRegConfirmTask(array $request, Tasks $tasks): array
	{
		$jdata = json_decode($request['BRequest'], true);
		$jwt = new Jwt();
		$token = $jwt->token_decode($jdata['token']);
		$token['payload']['tsk'] = $request['Id'];
		$token = $jwt->token_encode($token['payload']);
		$url = 'https://' . Connect::$projectDev['host'] . "/profile/?token={$token}";
		$text = "<b>Добрый день, {$jdata['nameFirst']}!</b><br/><br/>Пожалуйста, завершите регистрацию!";
		$text .= "<br/>После перехода по ссылке и установки пароля - Ваш аккаунт будет активирован.";
		$text .= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Заврешить регистрацию</a></center>";
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail($jdata['email'], "Завершите регистрацию", $text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'], $response, 200);
	}
	public function processRegCompleteTask(array $request, Tasks $tasks): array
	{
		$jdata = json_decode($request['BRequest'], true);
		$url = 'https://' . Connect::$projectDev['host'] . "/profile/";
		$text = "<b>Добрый день, {$jdata['nameFirst']}!</b><br/><br/>Вы завершили регистрацию!";
		$text .= "<br/>Ваш аккаунт активирован, спасибо.";
		$text .= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Перейти в личный кабинет</a></center>";
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail($jdata['email'], "Регистрация прошла успешно", $text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'], $response, 200);
	}
}