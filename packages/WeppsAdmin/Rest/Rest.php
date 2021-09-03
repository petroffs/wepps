<?php
namespace WeppsAdmin\Rest;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class RestWepps {
	protected $myGet;
	protected $myPost;
	protected $input;
	protected $data;
	protected $protocol;
	protected $host;
	protected $root;
	protected $url;
	protected $method;
	protected $param;
	protected $paramValue;
	protected $type;
	public $response;
	public $parent = 1;
	
	public function __construct() {
		$this->type = strtolower($_SERVER['REQUEST_METHOD']);
		$this->myGet = $_GET;
		$this->myPost = $_POST;
		$this->protocol = ConnectWepps::$projectDev['protocol'];
		$this->host = ConnectWepps::$projectDev['host'];
		$this->root = ConnectWepps::$projectDev['root'];
		$this->url = $this->protocol .$this->host . $_SERVER['REQUEST_URI'];
		$this->input = file_get_contents('php://input');
		if (!empty($this->input)) {
			$validate = RestUtilsWepps::_json_validate($this->input);
			if ($validate['status']==200) {
				$this->data = $validate['data'];
			} else {
				echo  $this->setResponse($validate['status'],$validate['message']);
				exit();
			}
		}
		
		$params = (!isset($this->myGet['params'])) ? "" : $this->myGet['params'];
		$ex = explode("/", trim($params,"/"));
		$this->method = $ex[0];
		$this->param = (isset($ex[1])) ? $ex[1] : "";
		$this->paramValue = (isset($ex[2])) ? $ex[2] : "";
		if ($this->parent==0) {
			if (substr($this->method, 0,3) == 'get' && $this->type!='get') {
				header('Content-Type: application/json');
				echo $this->setResponse(500,"Неверный тип запроса");
				exit();
			} elseif (substr($this->method, 0,3) == 'set' && $this->type!='post') {
				header('Content-Type: application/json');
				echo $this->setResponse(500,"Неверный тип запроса");
				exit();
			}
		} else if ($this->parent==1) {
			switch ($this->method) {
				case "getOrders":
				case "setOrders":
					//$obj = new RestOrdersPPS();
					break;
				case "":
					return;
					break;
				case "getList":
					if (!empty($this->param) && !empty($this->paramValue)) {
						$str = (!empty($this->myGet['search'])) ? $this->myGet['search'] : "";
						$page = (empty($this->myGet['page'])) ? 1 : $this->myGet['page'];
						$obj= new RestListsWepps();
						$this->response = $obj->getLists($this->param,$this->paramValue,$str,$page);
						header('Content-Type: application/json');
						echo $obj->response;
						return;
					}
					header('Content-Type: application/json');
					echo $this->setResponse(500,"list error");
					return;
					break;
				default:
					header('Content-Type: application/json');
					echo $this->setResponse(404,"not found");
					exit();
					break;
			}
			if (!empty($obj)) {
				header('Content-Type: application/json');
				echo $obj->response;
				$obj->setLogLocal();
			}
		}
		return;
	}
	protected function setLogLocal() {
		$out = 0;
		$row = array(
				'Name'=>$this->method,
				'Url'=>$this->url,
				'FCategory'=>$this->type,
				'FDate'=>date("Y-m-d H:i:s"),
				'FPost'=> $this->input,
				'FPostResponse'=> $this->response,
				'IP'=> (!empty($_SERVER['REMOTE_ADDR']))?$_SERVER['REMOTE_ADDR']:'N\A',
		);
		$arr = UtilsWepps::getQuery($row);
		$sql = "insert ignore into s_LocalServicesLog {$arr['insert']}";
		ConnectWepps::$instance->query($sql);
		$out = 1;
		$id = ConnectWepps::$db->lastInsertId ();
		$fp = fopen( "files/{$this->method}_{$id}.json", 'w' );
		fwrite ( $fp, $this->input );
		fclose ( $fp );
		return $out;
	}
	
	protected function setLogRemote() {
		$out = 0;
		$row = array(
				'Name'=>$this->method,
				'Url'=>$this->url,
				'FCategory'=>$this->type,
				'FDate'=>date("Y-m-d H:i:s"),
				'FPost'=> $this->input,
				'FPostResponse'=> $this->response,
		);
		$arr = UtilsWepps::getQuery($row);
		$sql = "insert ignore into s_RemoteServicesLog {$arr['insert']}";
		ConnectWepps::$instance->query($sql);
		$out = 1;
		/*
		 $id = ConnectWepps::$db->lastInsertId();
		 $fp = fopen("files/{$type}_{$id}.json", 'w');
		 fwrite($fp, $this->data);
		 fclose($fp);
		 */
		return $out;
	}
	protected function getLogRemote() {
		$url = $this->protocol .$this->host . $_SERVER['REQUEST_URI'];
		$sql = "select * from s_RemoteServicesLog where Url = '{$url}' and FPost= '{$this->input}' order by Id desc limit 0,1";
		$res = ConnectWepps::$instance->fetch($sql);
		if (!empty($res[0]['FPostResponse'])) {
			$this->type = $res[0]['FCategory'];
			return $this->response = RestUtilsWepps::getJsonClear($res[0]['FPostResponse']);
		}
		return null;
	}
	protected function setResponse($errorcode="500",$errormessage = "error") {
		http_response_code($errorcode);
		$output = array('status'=>$errorcode,'message'=>$errormessage);
		$this->response = $this->getJson($output);
		$this->setLogLocal();
		return  $this->response;
	}
	protected function getJson($array=[]) {
		return json_encode($array,JSON_UNESCAPED_UNICODE);
	}
}

?>