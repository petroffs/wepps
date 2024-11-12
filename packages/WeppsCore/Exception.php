<?php
namespace WeppsCore\Exception;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsExtensions\Template\TemplateWepps;
use WeppsCore\Connect\ConnectWepps;

class ExceptionWepps {
	/**
	 * Выдать сообщение в браузер
	 * @param \Exception $e
	 */
	public static function display(\Exception $e) {
		if (ConnectWepps::$projectDev['debug']==1) {
			$error = [];
			$error['message'] = $e->getMessage();
			$trace = $e->getTrace();
			if ($trace[1]['class']=='WeppsCore\Connect\ConnectWepps') {
				$error['file'] = $trace[1]['file'];
				$error['line'] = $trace[1]['line'];
				$error['args'] = @$trace[1]['args'];
			}
			UtilsWepps::debug($error,0);
			UtilsWepps::debug($trace,1);
		} else {
			echo $e->getMessage();
			exit();
		}
	}
	public static function error404() {
		http_response_code(404);
		$navigator = new NavigatorWepps('/error404/');
		$headers = new TemplateHeadersWepps();
		$obj = new TemplateWepps($navigator, $headers);
		unset($obj);
		exit();
	}
	public static function error($status=404) {
		http_response_code($status);
		echo "Status: $status";
		exit();
	}
	
}

?>
