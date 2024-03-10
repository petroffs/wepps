<?php
namespace WeppsExtensions\Contacts;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsExtensions\Addons\Mail\MailWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestContactsWepps extends RequestWepps {
	public function request($action="") {
		$navigator = new NavigatorWepps(@$this->get['link']);
		$this->assign('multilang', $navigator->multilang);
		$this->assign('language', $navigator->lang);
		switch ($action) {
			case 'feedback':
				/*
				 * Проверка формы
				 */
				$errors = array();
				$errors['name'] = ValidatorWepps::isNotEmpty($this->get['name'], "Не заполнено");
				$errors['email'] = ValidatorWepps::isEmail($this->get['email'], "Неверно заполнено");
				$errors['phone'] = ValidatorWepps::isNotEmpty($this->get['phone'], "Не заполнено");
				$errors['comment'] = ValidatorWepps::isNotEmpty($this->get['comment'], "Не заполнено");
				if ($this->get['phone']!='') {
					$phone = UtilsWepps::getPhoneFormatted($this->get['phone']);
					if (!isset($phone['view2'])) {
						$errors['phone'] = "Неверный формат";
					}
				}
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
				$this->assign('jscode', $outer['Out']);
				if ($outer['Co']==0) {
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
					
					$attachment = array();
					if (isset($_SESSION['uploads'][$this->get['form']]['feedback-upload']) && is_array($_SESSION['uploads'][$this->get['form']]['feedback-upload'])) {
						$attachment = $_SESSION['uploads'][$this->get['form']]['feedback-upload'];
					}
					$mail = new MailWepps();
					$mail->setAttach($attachment);
					//UtilsWepps::debug($attachment,1);
					$mail->mail(ConnectWepps::$projectInfo['email'], $subject, $message);
					
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
					ConnectWepps::$instance->insert("FormsData", $row);
					
					/*
					 * Вывод сообщения о добавлении отзыва
					 */
					$arr = ValidatorWepps::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
					$this->assign('jscode', $arr['Out']);
				}
				$this->tpl = "RequestContacts.tpl";
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}

$request = new RequestContactsWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>