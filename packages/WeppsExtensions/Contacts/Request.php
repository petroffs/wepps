<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Validator;
use WeppsCore\Navigator;
use WeppsExtensions\Addons\Messages\Mail\Mail;

if (!isset($_SESSION)) {
	@session_start();
}

class RequestContacts extends Request {
	public function request($action="") {
		$navigator = new Navigator(@$this->get['link']);
		$this->assign('multilang', $navigator->multilang);
		$this->assign('language', $navigator->lang);
		switch ($action) {
			case 'feedback':
				/*
				 * Проверка формы
				 */
				$this->errors = [];
				$this->errors['name'] = Validator::isNotEmpty($this->get['name'], "Не заполнено");
				$this->errors['email'] = Validator::isEmail($this->get['email'], "Неверно заполнено");
				$this->errors['phone'] = Validator::isNotEmpty($this->get['phone'], "Не заполнено");
				$this->errors['comment'] = Validator::isNotEmpty($this->get['comment'], "Не заполнено");
				if ($this->get['phone']!='') {
					$phone = Utils::phone($this->get['phone']);
					if (!isset($phone['view2'])) {
						$this->errors['phone'] = "Неверный формат";
					}
				}
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				$this->assign('jscode', $outer['html']);
				if ($outer['count']==0) {
					/*
					 * Отправка E-mail
					 */
					$subject = "Сообщение с сайта";
					$message = "тема сообщения: $subject\n";
					$message .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
					$message .= "---------------\n";
					$message .= "Сообщение: {$this->get['comment']}\n";
					$message .= "---------------\n";
					$message .= "Имя: {$this->get['name']}\n";
					$message .= "Телефон: {$phone['view2']}\n";
					$message .= "Эл. почта: {$this->get['email']}\n";
					//$message .= "Город: {$this->get['city']}\n";
					
					$attachment = [];
					if (isset($_SESSION['uploads'][$this->get['form']]['feedback-upload']) && is_array($_SESSION['uploads'][$this->get['form']]['feedback-upload'])) {
						$attachment = $_SESSION['uploads'][$this->get['form']]['feedback-upload'];
					}
					$mail = new Mail();
					$mail->setAttach($attachment);
					$mail->mail(Connect::$projectInfo['email'], $subject, $message);
					
					/*
					 * Добавление в список
					 */
					$row = array(
							'Name'=>$this->get['name'],
							'SDate'=> date("Y-m-d H:i:s"),
							'Email'=>$this->get['email'],
							'Phone'=>$phone['view2'],
							'Descr'=>$message,
							'SGroup'=>'Обратная связь',
							'SPage'=>$this->get['pageurl'],
					);
					Connect::$instance->insert("FormsData", $row);
					
					/*
					 * Вывод сообщения о добавлении отзыва
					 */
					$arr = Validator::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
					$this->assign('jscode', $arr['html']);
				}
				$this->tpl = "RequestContacts.tpl";
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestContacts($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);