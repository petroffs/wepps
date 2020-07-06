<?
namespace WeppsExtensions\Profile;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsExtensions\User\UserWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Mail\MailWepps;

class ProfileWepps {
	public $get;
	public $tpl;
	public $outer;
	public $pathItem;
	function __construct(NavigatorWepps $navigator, $ppsUrl,$get = array()) {
		if (count($get)) {
			foreach ( $get as $key => $value ) {
				$this->get [$key] = UtilsWepps::getStringFormatted ( $value );
			}
		}
		$smarty = SmartyWepps::getSmarty ();
		$rand = $this->headers::$rand;
		$this->pathItem = $ppsUrl;
		$users = new DataWepps("s_Users");
		$user = array();
		if (isset($_SESSION['user']['Id'])) {
			$users->setJoin("left outer join GeoCities as c on c.Id = t.City");
			$users->setConcat("c.Name as City_Name");
			$user = $users->getMax($_SESSION['user']['Id'])[0];
			$smarty->assign('user',$user);
			$this->get['user'] = $user;
			$this->get['title'] = 'Личный кабинет';
			$this->tpl = 'packages/WeppsExtensions/Profile/ProfileSummary.tpl';
		} else {
			$this->tpl = 'packages/WeppsExtensions/Profile/ProfileWelcome.tpl';
		}
		switch ($ppsUrl) {
			case "/profile/reg.html" :
				$this->get['title'] = 'Регистрация';
				if (!isset($user['Id'])) {
					$this->tpl = 'packages/WeppsExtensions/Profile/ProfileRegistration.tpl';
				}
				break;
			case "/profile/passback.html":
				$this->get['title'] = 'Обновление аккаунта';
				//if (!isset($user['Id'])) {
					if (isset($get['key1']) && isset($get['key2'])) {
						$login = UtilsWepps::getStringFormatted($get['key1']);
						$loginKey = UtilsWepps::getStringFormatted($get['key2']);
						$user = $users->getMax("binary t.Login = '{$login}' and t.FieldChangeKey='{$loginKey}'")[0];
						if (isset($user['Id'])) {
							$this->get['title'] = 'Личный кабинет';
							$change = UserWepps::setValue($user);
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
								$mess.= "\nС уважением, ".ConnectWepps::$projectInfo['name']."\n";
								$mess = nl2br($mess);
								$obj = new MailWepps('html');
								$obj->setDebug();
								$obj->mail($user['Email'],"Обновление аккаунта",$mess);
							}
							$this->tpl = 'packages/WeppsExtensions/Profile/ProfilePassbackConfirm.tpl';
						} else {
							ExceptionWepps::error404();
						}
					} else {
						$this->get['title'] = 'Восстановление доступа';
						$this->tpl = 'packages/WeppsExtensions/Profile/ProfilePassback.tpl';
					}
				//}
				break;
			case "/profile/orders.html" :
				$this->get['title'] = 'Мои заказы';
				if (isset($user['Id'])) {
					$obj = new DataWepps("TradeOrders");
					$page = (isset($get['page'])) ? $get['page'] : 1;
					$orders = $obj->getMax("t.DisplayOff=0 and t.UserId = '{$user['Id']}'",20,1,"t.Id desc");
					
					if (isset($orders[0]['Id'])) {
						$smarty->assign("orders",$orders);
						//$smarty->assign("paginator",$obj->paginator);
					}
					$this->tpl = 'packages/WeppsExtensions/Profile/ProfileOrders.tpl';
				}
				break;
			case "/profile/settings.html" :
				if (isset($_SESSION['userAddons'])) unset($_SESSION['userAddons']);
				$this->get['title'] = 'Настройки';
				if (isset($user['Id'])) {
					$this->tpl = 'packages/WeppsExtensions/Profile/ProfileSettings.tpl';
				}
				break;
			case "/profile/personal.html" :
				$this->get['title'] = 'Личные данные';
				if (isset($user['Id'])) {
// 					UtilsWepps::debug($user);
					$this->tpl = 'packages/WeppsExtensions/Profile/ProfilePersonal.tpl';
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
	$obj = new ProfileWepps($navigator,$ppsUrl,$_GET);
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