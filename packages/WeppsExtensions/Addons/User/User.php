<?php
namespace WeppsExtensions\Addons\User;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\UtilsWepps;
use Curl\Curl;

class UserWepps {
	public static function getAuth($login='',$password='') {
		$login = UtilsWepps::trim($login);
		$password = UtilsWepps::trim($password);
		$obj = new DataWepps( "s_Users" );
		if ($login=='' && $password=='' && isset($_COOKIE['authLogin']) && isset($_COOKIE['authKey'])) {
			$sql = "Login='".addslashes($_COOKIE['authLogin'])."' and AuthKey regexp '".ConnectWepps::$instance->selectRegx(addslashes($_COOKIE['authKey']))."' and AuthKey!=0 and UserBlock!=1";
			$user = $obj->get($sql)[0];
		} else {
			$password = md5($password);
			$user = $obj->get("(Login='$login' or Phone='$login') and Password='$password'")[0];
		}
		if (isset($user['Id'])) {
			$row = array();
			$row['AuthDate'] = date("Y-m-d H:i:s");
			$row['MyIP'] = $_SERVER['REMOTE_ADDR'];
			$obj->set($user['Id'], $row);
			return self::setAuth($user);
		} else {
			return array();
		}
	}
	public static function removeAuth() {
		if (!isset($_SESSION['user'])) return 0;
		$id = (isset($_SESSION['user']['Id'])) ? $_SESSION['user']['Id'] : 0;
		ConnectWepps::$instance->query("update s_Users set AuthKey='' where Id = '{$id}'");
		$_SESSION['user'] = array();
		unset($_SESSION);
		setcookie('authKey','');
		setcookie('authLogin','');
		return 1;
	}

	private static function setAuth($user) {
		if (isset($_SESSION['user']['Id'])) {
			return $_SESSION['user'];
		}
		if ( isset($user['AuthKey']) || !isset($_COOKIE ['authKey']) || !strstr($user['AuthKey'], $_COOKIE ['authKey']) || $user['AuthKey']=='') {
			$authKey = rand(10101,9999999999);
			$str = $user['AuthKey'];
			$tmp = explode(",",$str);
			array_unshift($tmp,$authKey);
			$tmp = array_unique($tmp);
			$authKey2 = trim(implode(",",$tmp),",");
			
			setcookie ( 'authKey', $authKey, time () + 3600 * 24 * 360, '/' );
			setcookie ( 'authLogin', $user ['Login'], time () + 3600 * 24 * 360, '/' );
			ConnectWepps::$instance->query ( "update s_Users set AuthKey='{$authKey2}' where Id = {$user['Id']}" );
		}
		return $_SESSION['user'] = $user;
	}
	public function addUser($row) {
		if (!isset($row['Name'])) return array('Error'=>1);
		if (!isset($row['Email'])) return array('Error'=>1);
		if (!isset($row['Phone'])) return array('Error'=>1);
	}
	
	public static function getRecapcha($recapcha) {
		$curl = new Curl();
		$curl->post('https://www.google.com/recaptcha/api/siteverify', array(
				'secret' => '6LfVnBgUAAAAACsFTHo2cz40KjbKaAXqyqOWngL2',
				'response' => $recapcha
		));
		$response = json_decode($curl->response);
		return $response;
	}
	/**
	 * Генерация нового пароля
	 * @return string
	 */
	public static function addPassword() {
		$arr1 = array("a","o","u","i","e","y","A","O","U","I","E","Y");
		$arr2 = array("1","2","3","4","5","6","7","8","9","0","w","r","t","k","m","n","b","h","d","s","W","R","N","D","S");
		$arr1Co = count($arr1)-1;
		$arr2Co = count($arr2)-1;
		return $arr2[rand(0,$arr2Co)].$arr1[rand(0,$arr1Co)].rand(0,9).$arr2[rand(0,$arr2Co)].$arr1[rand(0,$arr1Co)].rand(10,99);
	}
	
	public static function setValue($user) {
		if (!isset($user['Id'])) return array('status'=>false);
		if ($user['FieldChange']=='' || $user['FieldChangeKey']=='') return array('status'=>false);
		$row = array();
		$row['FieldChange'] = '';
		$row['FieldChangeKey'] = '';
		$ex = explode(":", $user['FieldChange']);
		switch ($ex[0]) {
			case 'Password':
				$row[trim($ex[0])] = md5($ex[1]);
				break;
			case 'Email':
				$row['Email'] = strtolower(trim($ex[1]));
				$row['Login'] = strtolower(trim($ex[1]));
				setcookie ( 'authLogin', $row['Login'], time () + 3600 * 24 * 360, '/' );
				break;
			default:
				$row[trim($ex[0])] = trim($ex[1]);
				break;
		}
		$users = new DataWepps("s_Users");
		$users->set($user['Id'], $row);
		return array('status'=>true,'value'=>$ex);
	}
	
}
?>