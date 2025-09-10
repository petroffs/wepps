<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect;
use WeppsCore\Tasks;
use WeppsCore\Navigator;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Messages\Mail\Mail;

class ProfileUtils
{
	private $settings = [];
	private $navigator = [];
	private $user = [];
	public function __construct(array $user)
	{
		$this->user = $user;
	}
	public function setNavigator(Navigator $navigator)
	{
		$this->navigator = $navigator;
	}
	public function getNav($pathItem = '')
	{
		if (!empty($this->user)) {
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
	public function getFavorites()
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

	public function getOrders($id = 0)
	{

	}
	public function processPasswordTask(array $request,Tasks $tasks) {
		$jdata = json_decode($request['BRequest'],true);
		$url = 'https://'.Connect::$projectDev['host']."/profile/password.html?token={$jdata['token']}";
		$text = "<b>Добрый день, {$jdata['nameFirst']}!</b><br/><br/>Поступил запрос на смену пароля!";
		$text.= "<br/><br/>Для установки нового пароля перейдите по ссылке:";
		$text.= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Установить новый пароль</a></center>";
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail($jdata['email'],"Восстановление доступа",$text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'],$response,200);
	}
	public function processPasswordConfirmTask(array $request,Tasks $tasks) {
		$jdata = json_decode($request['BRequest'],true);
		$url = 'https://'.Connect::$projectDev['host']."/profile/";
		$text = "<b>Добрый день, {$jdata['nameFirst']}!</b><br/><br/>Ваш пароль в Личном кабинете изменен!";
		$text.= "<br/><br/><center><a href=\"{$url}\" class=\"button\">Перейти в Личный кабинет</a></center>";
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail($jdata['email'],"Пароль изменен",$text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'],$response,200);
	}
}