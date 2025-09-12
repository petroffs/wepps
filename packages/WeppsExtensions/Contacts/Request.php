<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Validator;
use WeppsCore\Navigator;
use WeppsExtensions\Addons\Files\Files;
use WeppsExtensions\Addons\Messages\Mail\Mail;

if (!session_id()) {
	session_start();
}

class RequestContacts extends Request
{
	public function request($action = '')
	{
		$navigator = new Navigator(@$this->get['link']);
		$this->assign('multilang', $navigator->multilang);
		$this->assign('language', $navigator->lang);
		switch ($action) {
			case 'feedback':
				$this->feedback();
				break;
			default:
				Exception::error404();
				break;
		}
	}
	private function feedback()
	{
		$this->errors = [];
		$this->errors['name'] = Validator::isNotEmpty($this->get['name'], "Не заполнено");
		$this->errors['email'] = Validator::isEmail($this->get['email'], "Неверно заполнено");
		$this->errors['phone'] = Validator::isNotEmpty($this->get['phone'], "Не заполнено");
		$this->errors['comment'] = Validator::isNotEmpty($this->get['comment'], "Не заполнено");
		if ($this->get['phone'] != '') {
			$phone = Utils::phone($this->get['phone']);
			if (!isset($phone['view'])) {
				$this->errors['phone'] = "Неверный формат";
			}
		}
		$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
		$this->assign('jscode', $outer['html']);
		if ($outer['count'] == 0) {
			$this->get['email'] = strtolower($this->get['email']);
			$subject = "Сообщение с сайта";
			$message = "тема сообщения: $subject\n";
			$message .= "дата: " . date("d.m.Y") . " время: " . date("H:i") . "\n\n";
			$message .= "---------------\n";
			$message .= "Сообщение: {$this->get['comment']}\n";
			$message .= "---------------\n";
			$message .= "Тест:\n";
			if (!empty($this->get['checkboxtest'])) {
				if (is_array($this->get['checkboxtest'])) {
					$message .= "Test Checkbox: " . implode(', ', $this->get['checkboxtest']) . "\n";
				} else {
					$message .= "Test Checkbox: {$this->get['checkboxtest']}\n";
				}
			}
			if (!empty($this->get['radiotest'])) {
				$message .= "Test Radio: {$this->get['radiotest']}\n";
			}
			if (!empty($this->get['selecttest'])) {
				$message .= "Test Select: {$this->get['selecttest']}\n";
			}
			if (!empty($this->get['selectmultipletest'])) {
				if (is_array($this->get['selectmultipletest'])) {
					$message .= "Test Select multiple: " . implode(', ', $this->get['selectmultipletest']) . "\n";
				} else {
					$message .= "Test Select multiple: {$this->get['selectmultipletest']}\n";
				}
			}
			$message .= "---------------\n";
			$message .= "Имя: {$this->get['name']}\n";
			$message .= "Телефон: {$phone['view']}\n";
			$message .= "Эл. почта: {$this->get['email']}\n";
			$row = [
				'Name' => trim($this->get['name']),
				'SDate' => date("Y-m-d H:i:s"),
				'Email' => $this->get['email'],
				'Phone' => $phone['num'],
				'Descr' => trim($message),
				'SGroup' => 'Обратная связь',
				'SPage' => @$this->get['link'],
				'Priority' => 0,
			];
			Connect::$instance->insert("FormsData", $row);
			$message = nl2br($message);
			$mail = new Mail('html');
			$files = new Files();
			if (is_array($filesAttach = $files->getUploaded($this->get['form'], 'feedback-upload'))) {
				$mail->setAttach($filesAttach);
			}
			$mail->mail(Connect::$projectInfo['email'], $subject, $message);
			$files->removeUploaded($this->get['form'], 'feedback-upload');
			$arr = Validator::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
			$this->assign('jscode', $arr['html']);
		}
		$this->tpl = "RequestContacts.tpl";
	}
}
$request = new RequestContacts($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);