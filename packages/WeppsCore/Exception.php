<?
namespace WeppsCore\Exception;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\NavigatorWepps;
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
		if (ConnectWepps::$projectDev['debug']==1) {
			$error = [];
			$error['message'] = $e->getMessage();
			$trace = $e->getTrace();
			if ($trace[1]['class']=='WeppsCore\Connect\ConnectWepps') {
				$error['file'] = $trace[1]['file'];
				$error['line'] = $trace[1]['line'];
				$error['args'] = $trace[1]['args'];
			}
			UtilsWepps::debugf($error,1);
		} else {
			exit();
		}
	}
	
	public static function error404() {
		http_response_code(404);
		$navigator = new NavigatorWepps('/error404/');
		//$smarty = SmartyWepps::getSmarty();
		$headers = new TemplateHeadersWepps();
		$obj = new TemplateWepps($navigator, $headers);
		unset($obj);
		exit();
	}
}

?>
