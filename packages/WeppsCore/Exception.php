<?
namespace WeppsCore\Exception;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsExtensions\Template\TemplateWepps;
use WeppsCore\Connect\ConnectWepps;

class ExceptionWepps {
	public static function write ($message) {
		UtilsWepps::debug($message,1);
	}
	/**
	 * Выдать сообщение в браузер
	 * @param \Exception $e
	 */
	public static function writeMessage (\Exception $e) {
		if (ConnectWepps::$projectDev['debug']==1) {
			UtilsWepps::debug($e->getTraceAsString());
			UtilsWepps::debug($e->getMessage());
		} else {
			ConnectWepps::$instance->close();
		}
	}
	/**
	 * Записать сообщение в базу данных
	 * 
	 * @param \Exception $e        	
	 */
	public static function logMessage(\Exception $e) {
		// QueryWepps::$db->fetch();
		//echo "Будем писать в бд ошибки";
		
		if ($e->getTrace () [1] ['class'] == 'Wepps\Connect\ConnectWepps')
			UtilsWepps::debug ( $e->getTrace () [1], 0 );
		UtilsWepps::debug($e->getMessage(),1);
	}
	
	public static function error404() {
		header("HTTP/1.0 404 Not Found");
		$navigator = new NavigatorWepps('/error404/');
		$smarty = SmartyWepps::getSmarty();
		$headers = new TemplateHeadersWepps();
		$obj = new TemplateWepps($navigator, $headers);
		unset($obj);
		exit();
	}
}

?>
