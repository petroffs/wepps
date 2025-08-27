<?php
namespace WeppsCore;

class Memcached {
	private $memcache;
	private $memcached;
	public function __construct($isActive='auto') {
		switch ($isActive) {
			case 'yes':
				$isActive = true;
				break;
			case 'no':
				$isActive = false;
				break;
			default:
				$isActive = Connect::$projectServices['memcached']['active'];
				break;
		}
		if (class_exists('Memcache') && $isActive) {
			$this->memcache = new \Memcache();
			$this->memcache->connect(Connect::$projectServices['memcached']['host'], Connect::$projectServices['memcached']['port']);
		} else if (class_exists('Memcached') && $isActive) {
			$this->memcached = new \Memcached();
			$this->memcached->addServer(Connect::$projectServices['memcached']['host'], Connect::$projectServices['memcached']['port']);
		}
	}
	public function set($key,$value,$expire = 0) {
		$expire = ($expire == 0) ? Connect::$projectServices['memcached']['expire'] : $expire;
		if (!empty($this->memcache)) {
			$this->memcache->set($key, $value, false, $expire);
		} else if (!empty($this->memcached)) {
			$this->memcached->set($key, $value, $expire);
		}
		return true;
	}
	public function get($key)
	{
		if (!empty($this->memcache) && !empty($this->memcache->get($key))) {
			return $this->memcache->get($key);
		} else if (!empty($this->memcached) && !empty($this->memcached->get($key))) {
			return $this->memcached->get($key);
		}
	}
}