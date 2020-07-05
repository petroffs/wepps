<?
namespace PPSExtensions\Profile;

use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Utils\UtilsPPS;
use PPS\Core\DataPPS;
use PPS\Exception\ExceptionPPS;
use PPSExtensions\User\UserPPS;
use PPS\Connect\ConnectPPS;
use PPSExtensions\Mail\MailPPS;

class ProfilePPS {
	public $get;
	public $tpl;
	public $outer;
	public $pathItem;
	function __construct(NavigatorPPS $navigator, $ppsUrl,$get = array()) {
		if (count($get)) {
			foreach ( $get as $key => $value ) {
				$this->get [$key] = UtilsPPS::getStringFormatted ( $value );
			}
		}
		$smarty = SmartyPPS::getSmarty ();
		$rand = $this->headers::$rand;
		$this->pathItem = $ppsUrl;
		$users = new DataPPS("s_Users");
		$user = array();
		if (isset($_SESSION['user']['Id'])) {
			$users->setJoin("left outer join GeoCities as c on c.Id = t.City");
			$users->setConcat("c.Name as City_Name");
			$user = $users->getMax($_SESSION['user']['Id'])[0];
			$smarty->assign('user',$user);
			$this->get['user'] = $user;
			$this->get['title'] = 'Личный кабинет';
			$this->tpl = 'packages/PPSExtensions/Profile/ProfileSummary.tpl';
		} else {
			$this->tpl = 'packages/PPSExtensions/Profile/ProfileWelcome.tpl';
		}
		switch ($ppsUrl) {
			case "/profile/reg.html" :
				$this->get['title'] = 'Регистрация';
				if (!isset($user['Id'])) {
					$this->tpl = 'packages/PPSExtensions/Profile/ProfileRegistration.tpl';
				}
				break;
			case "/profile/passback.html":
				$this->get['title'] = 'Обновление аккаунта';
				//if (!isset($user['Id'])) {
					if (isset($get['key1']) && isset($get['key2'])) {
						$login = UtilsPPS::getStringFormatted($get['key1']);
						$loginKey = UtilsPPS::getStringFormatted($get['key2']);
						$user = $users->getMax("binary t.Login = '{$login}' and t.FieldChangeKey='{$loginKey}'")[0];
						if (isset($user['Id'])) {
							$this->get['title'] = 'Личный кабинет';
							$change = UserPPS::setValue($user);
							$user = $users->getMax($user['Id'])[0];
							if ($change['status'] == true) {
								$mess = "";
								$mess .= "дата: ".date("d.m.Y")." время: ".date("H:i")."\n\n";
								$mess .= "---------------\n";
								$mess .= "День добрый!\nНастройки вашего аккаунта на сайте http://".$_SERVER['HTTP_HOST']." обновлены.\n\n";
								$mess .= "Ваши текущие данные:\n";
								$mess .= "Контактный телефон: ".$user['Phone']."\n";
								$mess .= "Электронная почта: ".$user['Email']."\n";
								$mess .= "---------------\n\n";
								$mess .= "ДОСТУП НА САЙТЕ:"."\n";
								$mess .= "http://".$_SERVER['HTTP_HOST']."\n";
								$mess .= "логин: ".$user['Email']."\n";
								if ($change['value'][0]=='Password') $mess .= "пароль: ".$change['value'][1]."\n";
								$mess.= "\nС уважением, ".ConnectPPS::$projectInfo['name']."\n";
								$mess = nl2br($mess);
								$obj = new MailPPS('html');
								$obj->setDebug();
								$obj->mail($user['Email'],"Обновление аккаунта",$mess);
							}
							$this->tpl = 'packages/PPSExtensions/Profile/ProfilePassbackConfirm.tpl';
						} else {
							ExceptionPPS::error404();
						}
					} else {
						$this->get['title'] = 'Восстановление доступа';
						$this->tpl = 'packages/PPSExtensions/Profile/ProfilePassback.tpl';
					}
				//}
				break;
			case "/profile/orders.html" :
				$this->get['title'] = 'Мои заказы';
				if (isset($user['Id'])) {
					$obj = new DataPPS("TradeOrders");
					$page = (isset($get['page'])) ? $get['page'] : 1;
					$orders = $obj->getMax("t.DisplayOff=0 and t.UserId = '{$user['Id']}'",20,1,"t.Id desc");
					
					if (isset($orders[0]['Id'])) {
						$smarty->assign("orders",$orders);
						//$smarty->assign("paginator",$obj->paginator);
					}
					$this->tpl = 'packages/PPSExtensions/Profile/ProfileOrders.tpl';
				}
				break;
			case "/profile/settings.html" :
				if (isset($_SESSION['userAddons'])) unset($_SESSION['userAddons']);
				$this->get['title'] = 'Настройки';
				if (isset($user['Id'])) {
					$this->tpl = 'packages/PPSExtensions/Profile/ProfileSettings.tpl';
				}
				break;
			case "/profile/personal.html" :
				$this->get['title'] = 'Личные данные';
				if (isset($user['Id'])) {
// 					UtilsPPS::debug($user);
					$this->tpl = 'packages/PPSExtensions/Profile/ProfilePersonal.tpl';
				}
				break;
			case "/profile/" :
				break;
			default:
				$this->pathItem = '';
				break;
		}
		$this->outer = $smarty->fetch($this->tpl);
		return;
	}
}

if (isset ( $navigator )) {
	$smarty->assign ( 'way', $navigator->way );
	$obj = new ProfilePPS($navigator,$ppsUrl,$_GET);
	if ($obj->pathItem!='') {
		$extension->extensionData['element'] = array();
	}
	$smarty->assign('get',$obj->get);
	$smarty->assign('tpl',$obj->outer);
	$headers->css ( "/ext/Profile/Profile.{$rand}.css" );
	$headers->js ( "/ext/Profile/Profile.{$rand}.js" );
	$headers->js('/packages/vendor/components/jqueryui/jquery-ui.min.js');
	$headers->css('/packages/vendor/components/jqueryui/themes/base/jquery-ui.min.css');
	$headers->js ( "https://www.google.com/recaptcha/api.js" );
	/**
	 * Нормальное представление
	 */
	$smarty->assign ( 'normalHeader1', 0 );
	$smarty->assign ( 'normalView', 0 );
	$navigator->content ['Text'] = '';
}


?>