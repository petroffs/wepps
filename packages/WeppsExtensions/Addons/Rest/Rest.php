<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Utils;

/**
 * Summary of Rest
 * 
 * ! Need Refactoring
 */
class Rest
{
	protected $get;
	protected $post;
	protected $data;
	protected $settings;
	protected $headers;
	public $request;
	public $response;
	public $status;
	public $log = 1;
	public $parent = 1;
	public function __construct($settings = [])
	{
		$this->settings = $this->getSettings($settings);
		if ($this->parent == 0) {
			return;
		}
		$print = true;
		switch ($this->settings['type']) {
			case 'post':
				switch ($this->settings['method']) {
					case 'test':
						$obj = new RestLists();
						$obj->setTest();
						break;
				}
				break;
			case 'get':
				switch ($this->settings['method']) {
					case 'getList':
						$obj = new RestLists();
						$obj->getLists($this->settings['param'], $this->settings['paramValue']);
						break;
					case 'test':
						$obj = new RestLists();
						$obj->getTest();
						break;
				}
				break;
			case 'delete':
				switch ($this->settings['method']) {
					case 'test':
						$obj = new RestLists();
						$obj->removeTest();
						break;
				}
				break;
			case 'put':
				switch ($this->settings['method']) {
					case 'test':
						$obj = new RestLists();
						$obj->setTest();
						break;
				}
				break;
			case 'cli':
				$obj = new RestCli($settings);
				switch ($this->settings['method']) {
					case "removeLogLocal":
						$obj->removeLogLocal();
						break;
					case 'test':
						$obj->cliTest();
						break;
					default:
						unset($obj);
						$print = false;
						break;
				}
				break;
		}
		if (empty($obj)) {
			$this->status = 404;
			$this->response = ['message' => 'not found'];
			$this->setResponse($this->response, $print);
		}
		return;
	}
	private function getSettings($settings = [])
	{
		if (php_sapi_name() === 'cli') {
			$this->headers = null;
			return [
				'method' => @$settings['cli'][1],
				'type' => 'cli',
				'param' => '',
				'paramValue' => ''
			];
		}
		$this->get = $_GET;
		$this->post = $_POST;
		$this->root = Connect::$projectDev['root'];
		$this->url = Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . $_SERVER['REQUEST_URI'];
		$this->headers = apache_request_headers();
		$this->request = file_get_contents('php://input');
		$params = (!isset($this->get['params'])) ? "" : $this->get['params'];
		$ex = explode("/", trim($params, "/"));
		$method = $ex[0];
		$param = (isset($ex[1])) ? $ex[1] : "";
		$paramValue = (isset($ex[2])) ? $ex[2] : "";
		if (!empty($this->request)) {
			$validate = RestUtils::_json_validate($this->request);
			if ($validate['status'] == 200) {
				$this->data = $validate['data'];
			} else {
				$this->status = $validate['status'];
				$this->settings = [
					'method' => $method,
					'type' => strtolower($_SERVER['REQUEST_METHOD']),
					'param' => $param,
					'paramValue' => $paramValue
				];
				return $this->setResponse(['message' => $validate['message']]);
			}
		}
		return [
			'method' => $method,
			'type' => strtolower($_SERVER['REQUEST_METHOD']),
			'param' => $param,
			'paramValue' => $paramValue
		];
	}
	protected function setLogLocal()
	{
		/**
		 * Использовать Logs
		 */
		return;

		$out = 0;
		if ($this->log == 0) {
			return $out;
		}
		$headers = "";
		if (!empty($this->headers)) {
			foreach ($this->headers as $key => $value) {
				$headers .= "$key => $value\n";
			}
		}

		$rheaders = "";
		$arr = apache_response_headers();
		if (!empty($arr)) {
			foreach (apache_response_headers() as $key => $value) {
				$rheaders .= "$key => $value\n";
			}
		}
		$row = array(
			'Name' => $this->settings['method'],
			'Url' => $this->url,
			'TRequest' => $this->settings['type'],
			'LDate' => date("Y-m-d H:i:s"),
			'BRequest' => $this->request,
			'BResponse' => $this->response,
			'HRequest' => $headers,
			'HResponse' => $rheaders,
			'SResponse' => $this->status,
			'IP' => (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'localhost',
		);
		$prepare = Connect::$instance->prepare($row);
		$sql = "insert ignore into s_LocalServicesLog {$prepare['insert']}";
		Connect::$instance->query($sql, $prepare['row']);








		$id = Connect::$db->lastInsertId();
		/* if (!empty($this->request)) {
			$fp = fopen(filename: __DIR__ . "/files/{$this->settings['method']}_{$id}.json", 'w');
			fwrite($fp, $this->request);
			fclose($fp);
		} */
		return true;
	}
	protected function setLogRemote()
	{
		$out = 0;
		$row = array(
			'Name' => $this->method,
			'Url' => $this->url,
			'TRequest' => $this->type,
			'LDate' => date("Y-m-d H:i:s"),
			'BRequest' => $this->request,
			'BResponse' => $this->response,
		);
		$prepare = Connect::$instance->prepare($row);
		$sql = "insert ignore into s_RemoteServicesLog {$prepare['insert']}";
		Connect::$instance->query($sql, $prepare['row']);
		$out = 1;
		/*
		 $id = Connect::$db->lastInsertId();
		 $fp = fopen("files/{$type}_{$id}.json", 'w');
		 fwrite($fp, $this->data);
		 fclose($fp);
		 */
		return $out;
	}
	protected function getLogRemote()
	{
		$url = $this->protocol . $this->host . $_SERVER['REQUEST_URI'];
		$sql = "select * from s_RemoteServicesLog where Url = '{$url}' and BRequest= '{$this->request}' order by Id desc limit 0,1";
		$res = Connect::$instance->fetch($sql);
		if (!empty($res[0]['BResponse'])) {
			$this->type = $res[0]['FCategory'];
			return $this->response = RestUtils::getJsonClear($res[0]['BResponse']);
		}
		return null;
	}
	protected function setResponse($output, $print = true)
	{
		http_response_code($this->status);
		$output = [
			'type' => $this->settings['type'],
			'status' => $this->status,
			'data' => $output
		];
		$this->response = $this->getJson($output);
		$this->setLogLocal();
		if ($print == true) {
			header('Content-Type: application/json');
			echo $this->response;
			exit();
		}
		return $this->response;
	}
	protected function getJson($array = [])
	{
		return json_encode($array, JSON_UNESCAPED_UNICODE);
	}
}

if (!function_exists('apache_request_headers')) {
	function apache_request_headers()
	{
		$arh = [];
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if (preg_match($rx_http, $key)) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = [];
				$rx_matches = explode('_', $arh_key);
				if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
					foreach ($rx_matches as $ak_key => $ak_val)
						$rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return ($arh);
	}
}

if (!function_exists('apache_response_headers')) {
	function apache_response_headers()
	{
		return null;
	}
}