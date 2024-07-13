<?php
namespace WeppsExtensions\Addons\User;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsExtensions\Addons\Mail\MailWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

/**
 * @var \Smarty $smarty
 */

class RequestUserWepps extends RequestWepps {
	public function request($action = "") {
		$users = new DataWepps("s_Users");
		$dateCurr = date("Y-m-d H:i:s");
		switch ($action) {
			case 'getAuth' :
				$errors = array();
				$errors['email'] = ValidatorWepps::isNotEmpty($this->get['email'], "Не заполнено");
				$errors['pass'] = ValidatorWepps::isNotEmpty($this->get['pass'], "Не заполнено");
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
				echo $outer['Out'];
				if ($outer['Co']==0) {
					$user = UserWepps::getAuth($this->get['email'],$this->get['pass']);
					if (count($user)==0) {
						$errors = array();
						$errors['email'] = "Ошибка входа";
						$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
						echo $outer['Out'];
						ConnectWepps::$instance->close();
					}
					$js = "
						<script>
						location.reload();
						</script>
						";
					echo $js;
				}
				ConnectWepps::$instance->close();				
				break;
			case 'addUser' :
				$errors = array();
				$errors['name'] = ValidatorWepps::isNotEmpty($this->get['name'], "Не заполнено");
				$errors['surname'] = ValidatorWepps::isNotEmpty($this->get['surname'], "Не заполнено");
				//$errors['patronymic'] = ValidatorWepps::isNotEmpty($this->get['patronymic'], "Не заполнено");
				$errors['email'] = ValidatorWepps::isEmail($this->get['email'], "Не заполнено");
				$errors['phone'] = ValidatorWepps::isNotEmpty($this->get['phone'], "Не заполнено");
				if ($errors['email']=='') {
					$res = $users->get("Email='{$this->get['email']}'")[0];
					if (count($res)!=0) $errors['email'] = "E-mail уже существует";
				}
				$phone = UtilsWepps::phone($this->get['phone']);
				if ($errors['phone']=='' && !isset($phone['view2'])) {
					$errors['phone'] = "Неверный формат";
				}
				
				if (isset($phone['view2'])) {
					/**
					 * Проверка на существование телефона
					 */
					$res = ConnectWepps::$instance->fetch("select count(*) as Co from s_Users where Phone='".$phone['view2']."'");
					if ($res[0]['Co']!=0) {
						$errors['phone'] = "Уже используется";
					}
				}
				
				$password = "";
				if ($this->get['formtype']=='profile') {
					/**
					 * Проверка введенного пароля
					 */
					$errors['pass'] = ValidatorWepps::isNotEmpty($this->get['pass'], "Не заполнено");
					$errors['pass2'] = ValidatorWepps::isNotEmpty($this->get['pass2'], "Не заполнено");
					if ($errors['pass']=="" && $errors['pass2']=="") {
						if (strlen($this->get['pass'])<5) {
							$errors['pass'] = "Длина пароля от 6 символов";
						} elseif ($this->get['pass']!=$this->get['pass2']) {
							$errors['pass2'] = "Пароли не совпадают";
						}
					}
					$password = $this->get['pass'];
				}
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
				echo $outer['Out'];
				if ($outer['Co']==0) {
					
					setcookie ( 'authKey', '', time (), '/' );
					setcookie ( 'authLogin', '', time (), '/' );
					unset($_COOKIE ['authKey']);
					/*
					 * Добавить пользователя и выслать уведомление
					 * См. валидацию и другое по регистрации в ппс5
					 * */
					$password = ($password=='') ? UserWepps::addPassword() : $password;
					$row = array();
					$row['NameFirst'] = mb_convert_case($this->get['name'], MB_CASE_TITLE, "UTF-8");
					$row['NameSurname'] = mb_convert_case($this->get['surname'], MB_CASE_TITLE, "UTF-8");
					$row['NamePatronymic'] = mb_convert_case($this->get['patronymic'], MB_CASE_TITLE, "UTF-8");
					$row['Name'] = "{$row['NameSurname']} {$row['NameFirst']} {$row['NamePatronymic']} ";
					$row['Email'] = strtolower($this->get['email']);
					$row['Login'] = strtolower($this->get['email']);
					$row['Phone'] = $phone['view2'];
					$row['Password'] = md5($password);
					$row['Subscriber'] = 1;
					$row['UserPermissions'] = 3;
					$row['DateCreate'] = $dateCurr;
					
					$users->add($row);
					$mess = "";
					$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
					$mess .= "---------------\n";
					$mess .= "ФИО: ".$row['Name']."\n";
					$mess .= "Контактный телефон: ".$row['Phone']."\n";
					$mess .= "Электронная почта: ".$row['Email']."\n";
					$mess .= "---------------\n\n";
					$mess .= "ДОСТУП НА САЙТЕ:"."\n";
					$mess .= "http://".$_SERVER['HTTP_HOST']."\n";
					$mess .= "логин: ".$row['Email']."\n";
					$mess .= "пароль: ".$password."\n\n";
					$mess.= "С уважением, ".ConnectWepps::$projectInfo['name']."\n";
					$mess = nl2br($mess);
					$obj = new MailWepps('html');
					$obj->mail($row['Email'],"Регистрация на сайте",$mess);
					//$obj->setDebug();
					$user = UserWepps::getAuth($this->get['email'],$password);
					
					
					/*
					 * Выйти здесь, при тестировании и проблемах
					 */
					//exit();
					
					
					$js = "
						<script>
						location.reload();
						</script>
						";
					echo $js;
				}
				ConnectWepps::$instance->close();
				break;
			case 'removeAuth':
				UserWepps::removeAuth();
				$js = "
						<script>
						location.reload();
						</script>
						";
				echo $js;
				ConnectWepps::$instance->close();
				break;
			case "setPassback":
				$errors = array();
				$errors['email'] = ValidatorWepps::isEmail($this->get['email'], "Неверное значение");
				if ($errors['email']=='') {
					$user = $users->get("Email='{$this->get['email']}'")[0];
					if (!isset($user['Id'])) $errors['email'] = "E-mail не найден";
				}
				
				$recapcha = UserWepps::getRecapcha($this->get['g-recaptcha-response']);
				if ($recapcha->success!=1) {
					$errors['capchadub'] = 'Код неверный, обновите страницу';
				}
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
				echo $outer['Out'];
				if ($outer['Co']==0) {
					/**
					 * Логика и отправка сообщения
					 * $user - известен
					 */
					$password = UserWepps::addPassword();
					$row = array();
					$row['FieldChange'] = "Password:".$password;
					$row['FieldChangeKey'] = strtoupper(rand(10000,99999) . UserWepps::addPassword());
					$users->set($user['Id'], $row);
					$mess = "";
					$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
					$mess .= "---------------\n";
					$mess .= "День добрый!\nМы получили запрос на восстановление доступа к вашему аккаунту на сайте http://".$_SERVER['HTTP_HOST']."\n\n";
					$mess .= "Для подтверждения запроса перейдите по ссылке: http://".$_SERVER['HTTP_HOST']."/profile/update.html?key1={$user['Email']}&key2={$row['FieldChangeKey']}\n\n";
					$mess .= "Ваши текущие данные:\n";
					$mess .= "Контактный телефон: ".$user['Phone']."\n";
					$mess .= "Электронная почта: ".$user['Email']."\n";
					$mess .= "---------------\n\n";
					$mess .= "ДОСТУП НА САЙТЕ:"."\n";
					$mess .= "http://".$_SERVER['HTTP_HOST']."\n";
					$mess .= "логин: ".$user['Email']."\n";
					$mess.= "\nС уважением, ".ConnectWepps::$projectInfo['name']."\n";
					$mess = nl2br($mess);
					$obj = new MailWepps('html');
					$obj->setDebug();
					$obj->mail($user['Email'],"Восстановление доступа",$mess);
					$arr = ValidatorWepps::setFormSuccess("На электронную почту отправлено сообщение для подтверждения запроса. Спасибо.", $this->get['form']);
					echo $arr['Out'];
					
				}
				ConnectWepps::$instance->close();
				break;
			case 'setUser':
				if (!isset($_SESSION['user']['Id'])) ExceptionWepps::error404();
				$errors = array();
				$errors['name'] = ValidatorWepps::isNotEmpty($this->get['name'], "Неверное значение");
				$errors['surname'] = ValidatorWepps::isNotEmpty($this->get['surname'], "Неверное значение");
				$obj = new DataWepps("GeoCities");
				$city = $obj->get("Name='{$this->get['city']}'")[0];
				if (!isset($city['Id'])) {
					$errors['city'] = 'Неверное значение';
				}
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
				echo $outer['Out'];
				if ($outer['Co']==0) {
					$row = array();
					$row['NameFirst'] = mb_convert_case($this->get['name'], MB_CASE_TITLE, "UTF-8");
					$row['NameSurname'] = mb_convert_case($this->get['surname'], MB_CASE_TITLE, "UTF-8");
					if (isset($this->get['patronymic']) && $this->get['patronymic']!='') $row['NamePatronymic'] = mb_convert_case($this->get['patronymic'], MB_CASE_TITLE, "UTF-8");
					$row['Name'] = "{$row['NameSurname']} {$row['NameFirst']} {$row['NamePatronymic']} ";
					if (isset($this->get['addressIndex']) && $this->get['addressIndex']!='') $row['AddressIndex'] = $this->get['addressIndex'];
					if (isset($this->get['address']) && $this->get['address']!='') $row['Address'] = $this->get['address'];
					if (isset($city['Name'])) {
						$row['City'] = $city['Name'];
					} else if (isset($this->get['city']) && $this->get['city']!='') {
						$row['City'] = $this->get['city'];
					}
					$users->set($_SESSION['user']['Id'], $row);
					$arr = ValidatorWepps::setFormSuccess ( 'Ваши персональные данные обновлены', 'message', true);
					echo $arr['Out'];
				}
				ConnectWepps::$instance->close();
				break;
			case 'setSettings':
				if (!isset($_SESSION['user']['Id'])) ExceptionWepps::error404();
				$user = $users->get($_SESSION['user']['Id'])[0];
				if (!isset($user['Id'])) ExceptionWepps::error404();
				$errors = array();
				switch ($this->get ['form']) {
					case 'emailForm' :
						$errors['email'] = ValidatorWepps::isEmail($this->get['email'], "Неверное значение");
						if ($errors['email']=='' && $this->get['email'] == $user['Email']) {
							//$errors['email'] = 'E-mail не изменен';
						} elseif ($errors['email']=='') {
							$usertest = $users->get("Email='{$this->get['email']}'")[0];
							if (isset($usertest['Id'])) {
								$errors['email'] = "E-mail занят";
							}
						}
						
						
						
						//UtilsWepps::debug($this->get,1);
						
						if ($errors['email']=='' && $this->get['code']=='') {
							
							$_SESSION['userAddons']['EmailCode'] = rand(10000,99909);
							$mess = "";
							$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
							$mess .= "---------------\n";
							$mess .= "День добрый!\nМы получили запрос на обновление настроек аккаунта на сайте http://".$_SERVER['HTTP_HOST']."\n\n";
							$mess .= "Код: {$_SESSION['userAddons']['EmailCode']}\n\n";
							$mess.= "С уважением, ".ConnectWepps::$projectInfo['name']."\n";
							$mess = nl2br($mess);
							$obj = new MailWepps('html');
							
							/**
							 * Временно установлен mail@petroffs.com
							 * Потому что mail.ru не принимает почту с тестового сервера.
							*/
							
							$obj->mail('mail@petroffs.com',"Обновление аккаунта",$mess);
							$js = "
									<script>
									$('#setEmailCode').closest('.settings').find('.form').addClass('pps_hide');
									$('#setEmailCode').removeClass('pps_hide');
									$('#setEmailCode').find('i').html('{$this->get['email']}');
									</script>
									";
							echo $js;
							ConnectWepps::$instance->close();
						}
						
						if (isset($this->get['code']) && $_SESSION['userAddons']['EmailCode']!=$this->get['code']) {
							$errors['code'] = 'Ошибка';
							$errors['email'] = 'Ошибка';
						}
						
						if ($errors['email']=='') {
							$row = array();
							$row['FieldChange'] = "Email:".$this->get['email'];
							$row['FieldChangeKey'] = strtoupper(rand(10000,99999) . UserWepps::addPassword());
							$users->set($user['Id'], $row);
						}
						break;
					case 'phoneForm' :
						$phone = UtilsWepps::phone($this->get['phone']);
						$errors['phone'] = '';
						if ($errors['phone']=='' && !isset($phone['view2'])) {
							$errors['phone'] = "Неверный формат";
						}
						if ($errors['phone']=='') {
							$row = array();
							$row['FieldChange'] = "Phone:".$phone['view2'];
							$row['FieldChangeKey'] = strtoupper(rand(10000,99999) . UserWepps::addPassword());
							$users->set($user['Id'], $row);
						}
						break;
					case 'passForm' :
						$errors['pass'] = ValidatorWepps::isNotEmpty($this->get['pass'], "Не заполнено");
						$errors['pass2'] = ValidatorWepps::isNotEmpty($this->get['pass2'], "Не заполнено");
						if ($errors['pass']=="" && $errors['pass2']=="") {
							if (strlen($this->get['pass'])<5) {
								$errors['pass'] = "Длина пароля от 6 символов";
							} elseif ($this->get['pass']!=$this->get['pass2']) {
								$errors['pass2'] = "Пароли не совпадают";
							}
						}
						
						if ($errors['pass']=='') {
							$row = array();
							$row['FieldChange'] = "Password:".$this->get['pass'];
							$row['FieldChangeKey'] = strtoupper(rand(10000,99999) . UserWepps::addPassword());
							$users->set($user['Id'], $row);
						}
						
						break;
					default :
						ExceptionWepps::error404 ();
						break;
				}
				
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, $this->get['form']);
				echo $outer['Out'];
				if ($outer['Co']==0) {
					$mess = "";
					$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
					$mess .= "---------------\n";
					$mess .= "День добрый!\nМы получили запрос на обновление настроек аккаунта на сайте http://".$_SERVER['HTTP_HOST']."\n\n";
					$mess .= "Для подтверждения запроса перейдите по ссылке: http://".$_SERVER['HTTP_HOST']."/profile/update.html?key1={$user['Email']}&key2={$row['FieldChangeKey']}\n\n";
					$mess .= "Ваши текущие данные:\n";
					$mess .= "Контактный телефон: ".$user['Phone']."\n";
					$mess .= "Электронная почта: ".$user['Email']."\n";
					$mess .= "---------------\n\n";
					$mess .= "ДОСТУП НА САЙТЕ:"."\n";
					$mess .= "http://".$_SERVER['HTTP_HOST']."\n";
					$mess .= "логин: ".$user['Email']."\n\n";
					$mess.= "С уважением, ".ConnectWepps::$projectInfo['name']."\n";
					$mess = nl2br($mess);
					$obj = new MailWepps('html');
					$obj->mail($user['Email'],"Обновление аккаунта",$mess);
					$arr = ValidatorWepps::setFormSuccess ( '<h3>Вам отправлено электронное письмо со ссылкой для завершения операции.<br/>Проверьте почту.</h3>', 'message');
					echo $arr['Out'];
				}
				ConnectWepps::$instance->close();
				break;
			case "ulogin":
				if (isset($_REQUEST ['token'])) {
					$s = file_get_contents ( 'http://ulogin.ru/token.php?token=' . $_REQUEST ['token'] . '&host=' . $_SERVER ['HTTP_HOST'] );
				} else {
					ConnectWepps::$instance->close();
					echo "test.";
					$s = '{"profile":"http:\/\/vk.com\/id135368","first_name":"\u0410\u043b\u0435\u043a\u0441\u0435\u0439","email":"mail@petroffs.com","network":"vkontakte","identity":"http:\/\/vk.com\/id135368","city":"\u0421\u0430\u043d\u043a\u0442-\u041f\u0435\u0442\u0435\u0440\u0431\u0443\u0440\u0433","photo_big":"https:\/\/pp.vk.me\/c622517\/v622517368\/3ffe2\/5BDwvVXe-qw.jpg","last_name":"\u041f\u0435\u0442\u0440\u043e\u0432","bdate":"29.12.1979","uid":"135368","sex":"2","verified_email":"1"}';
				}
				$userJSON = json_decode($s);
				$user = $users->get ( "Email='{$userJSON->email}'" ) [0];
				
				if (isset( $user['Id'] )) {
					// авторизуем
					$authKey = rand(10101,9999999999);
					$str = $user['AuthKey'];
					$tmp = explode(",",$str);
					array_unshift($tmp,$authKey);
					$tmp = array_unique($tmp);
					$authKey2 = trim(implode(",",$tmp),",");
						
					$_COOKIE['authKey'] = $authKey;
					$_COOKIE['authLogin'] = $user ['Login'];
					setcookie ( 'authKey', $authKey, time () + 3600 * 24 * 360, '/' );
					setcookie ( 'authLogin', $user ['Login'], time () + 3600 * 24 * 360, '/' );
					$row = array(
							'AuthKey'=>$authKey2,
							'ProfileAuth'=>$s,
							'ProfileImage'=>$userJSON->photo_big,
							'ProfileNetwork'=>$userJSON->network,
					);
					$users->set($user['Id'], $row);
					UserWepps::getAuth();
					
					
					/*
					 * При тестировании ulogin`а скрываем location.href
					 */
					//exit();
					
					
					echo "
							<script>
							setTimeout(function() {
							location.href='{$_GET['back']}';
							},500);
							</script>
							";
				} else {
					// Регистрируем
					if (isset ( $userJSON->phone )) {
						$phone = UtilsWepps::phone($userJSON->phone);
						if (isset($phone['view2'])) {
						}
					}
					
					$password = UserWepps::addPassword();
					$row = array();
					$row['NameFirst'] = mb_convert_case($userJSON->first_name, MB_CASE_TITLE, "UTF-8");
					$row['NameSurname'] = (isset($userJSON->last_name)) ? mb_convert_case($userJSON->last_name, MB_CASE_TITLE, "UTF-8") : '';
					//$row['NamePatronymic'] = mb_convert_case($this->get['patronymic'], MB_CASE_TITLE, "UTF-8");
					$row['Name'] = "{$row['NameSurname']} {$row['NameFirst']}";
					$row['Email'] = strtolower($userJSON->email);
					$row['Login'] = strtolower($userJSON->email);
					$row['Phone'] = (isset($phone['view2'])) ? $phone['view2'] : '';
					$row['Password'] = md5($password);
					$row['Subscriber'] = 1;
					$row['UserPermissions'] = 3;
					$row['DateCreate'] = $dateCurr;
					$row['ProfileAuth'] = $s;
					$row['ProfileImage'] = $userJSON->photo_big;
					$row['ProfileNetwork'] = $userJSON->network;
					$users->add($row);
					$mess = "";
					$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
					$mess .= "---------------\n";
					$mess .= "ФИО: ".$row['Name']."\n";
					$mess .= "Контактный телефон: ".$row['Phone']."\n";
					$mess .= "Электронная почта: ".$row['Email']."\n";
					$mess .= "---------------\n\n";
					$mess .= "ДОСТУП НА САЙТЕ:"."\n";
					$mess .= "http://".$_SERVER['HTTP_HOST']."\n";
					$mess .= "логин: ".$row['Email']."\n";
					$mess .= "пароль: ".$password."\n\n";
					$mess.= "С уважением, ".ConnectWepps::$projectInfo['name']."\n";
					$mess = nl2br($mess);
					$obj = new MailWepps('html');
					$obj->mail($row['Email'],"Регистрация на сайте",$mess);
					$user = UserWepps::getAuth($userJSON->email,$password);
					
					
					
					/*
					 * При тестировании ulogin`а скрываем location.href 
					 */
					//exit();
					
					echo "
							<script>
							location.href='/profile/'
							</script>
							";
				}
				ConnectWepps::$instance->close();
				break;
			default :
				ExceptionWepps::error404();
				break;
		}
	}
}

$request = new RequestUserWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>