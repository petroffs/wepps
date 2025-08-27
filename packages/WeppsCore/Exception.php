<?php
namespace WeppsCore;

use WeppsExtensions\Template\Template;
use WeppsCore\Connect;

class Exception {
	/**
	 * Выдать сообщение в браузер
	 * @param \Exception $e
	 */
	public static function display(\Exception $e) {
		if (Connect::$projectDev['debug']==1) {
			$error = [];
			$error['message'] = $e->getMessage();
			$trace = $e->getTrace();
			if ($trace[1]['class']=='WeppsCore\Connect\Connect') {
				$error['file'] = $trace[1]['file'];
				$error['line'] = $trace[1]['line'];
				$error['args'] = @$trace[1]['args'];
			}
			if (php_sapi_name() == 'cli') {
				Utils::debug($error, 3);
				Utils::debug($trace, 31);
			} else {
				Utils::debug($error, 0);
				Utils::debug($trace, 1);
			}
		} else {
			echo $e->getMessage();
			exit();
		}
	}
	public static function error404() {
		http_response_code(404);
		$navigator = new Navigator('/error404/');
		$headers = new TemplateHeaders();
		$obj = new Template($navigator, $headers);
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
