<?
namespace PPSExtensions\Contacts;

use PPS\Utils\RequestPPS;
use PPS\Utils\UtilsPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Connect\ConnectPPS;
use PPS\Validator\ValidatorPPS;
use PPSExtensions\Mail\MailPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

if (!session_start()) session_start();

class RequestContactsPPS extends RequestPPS {
	public function request($action="") {
		switch ($action) {
			case 'feedback':
				/**
				 * Проверка формы
				 */
				$errors = array();
				$errors['name'] = ValidatorPPS::isNotEmpty($this->get['name'], "Не заполнено");
				$errors['email'] = ValidatorPPS::isEmail($this->get['email'], "Неверно заполнено");
				$errors['phone'] = ValidatorPPS::isNotEmpty($this->get['phone'], "Не заполнено");
				$errors['comment'] = ValidatorPPS::isNotEmpty($this->get['comment'], "Не заполнено");
				if ($this->get['phone']!='') {
					$phone = UtilsPPS::getPhoneFormatted($this->get['phone']);
					if (!isset($phone['view2'])) {
						$errors['phone'] = "Неверный формат";
					}
				}
				$outer = ValidatorPPS::setFormErrorsIndicate($errors, $this->get['form']);
				$this->assign('jscode', $outer['Out']);
				if ($outer['Co']==0) {
					/**
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
					$mail = new MailPPS();
					$mail->setAttach($attachment);
					//UtilsPPS::debug($attachment,1);
					$mail->mail(ConnectPPS::$projectInfo['email'], $subject, $message);
					
					/**
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
					ConnectPPS::$instance->insert("FormsData", $row);
					
					/**
					 * Вывод сообщения о добавлении отзыва
					 */
					$arr = ValidatorPPS::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
					$this->assign('jscode', $arr['Out']);
				}
				$this->tpl = "RequestContacts.tpl";
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
	}
}

$request = new RequestContactsPPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>