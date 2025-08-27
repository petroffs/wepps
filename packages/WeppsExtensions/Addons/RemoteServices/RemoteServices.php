<?php
namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsCore\Utils;
use WeppsCore\Connect;

/**
 * Summary of RemoteServices
 * ! Deprecated
 */
class RemoteServices {
	public $curl;
	public $settings;
	public $cache = 1;
	
	public function __construct($settings=[]) {
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->settings = $settings;
	}
	
	/*
	 * Настроить кэширование в БД, чтобы изначально проверять кэш, а потом только обращаться к Remote API
	 */
	public function getResponse($url,$body="") : array
	{
	    $cache = ($this->cache==1) ? self::getCache(md5($url.$body)) : null;
	    if (!empty($cache)) {
	        return $cache;
	    }
		if ($body=="") {
			$response = $this->curl->get($url);
		} else {
			$response = $this->curl->post($url, $body);
		}
		$output = array('response'=>json_decode($response->response,true),'url'=>$url,'body'=>$body);
		if ($this->cache==1) {
		    self::addCache($url,$body,json_encode(json_decode($response->response),JSON_UNESCAPED_UNICODE));
		}
		
		return $output;
	}

	/*
	 * Получить расширение
	 * Для реализации там, где это необходимо
	 * Например ПВЗ для СДЕК
	 */
	public function getExtension() {
	    return [];
	}
	
	private function getCache($hash) {
	    $sql = "select * from RemoteServicesCache where binary Alias = '$hash'";
	    $res = Connect::$instance->fetch($sql);
	    if (!empty($res[0]['Id'])) {
	        $url = (!empty($res[0]['Url'])) ? $res[0]['Url'] : '';
	        $body = (!empty($res[0]['Descr'])) ? $res[0]['Descr'] : '';
	        $response = (!empty($res[0]['DescrResponse'])) ? $res[0]['DescrResponse'] : '';
	        $output = array('response'=>json_decode($response,true),'url'=>$url,'body'=>$body);
	        return $output;
	    }
	    return '';
	}
	
	private function addCache($url='',$body='',$response='') {
	    $className = get_class($this);
	    
		$prepare = Connect::$instance->prepare([
	        'Name' => substr($className, strrpos($className, '\\')+1),
	        'Alias' => md5($url.$body),
	        'Url' => $url,
	        'Descr' => $body,
	        'DescrResponse' => $response,
		]);
	    $sql = "insert ignore RemoteServicesCache {$prepare['insert']}";
	    Connect::$instance->query($sql,$prepare['row']);
	    return 1;
	}
}

?>