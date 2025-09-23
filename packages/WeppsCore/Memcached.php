<?php
namespace WeppsCore;
/**
 * Класс Memcached
 *
 * Этот класс предоставляет функциональность для работы с Memcached.
 * Он поддерживает как Memcache, так и Memcached.
 */
class Memcached
{
	private $memcache;
	private $memcached;

	/**
	 * Конструктор класса Memcached
	 *
	 * Инициализирует соединение с Memcached сервером.
	 *
	 * @param string $isActive Флаг активности Memcached (yes, no, auto)
	 */
	public function __construct($isActive = 'auto')
	{
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

	/**
	 * Устанавливает значение в Memcached
	 *
	 * Этот метод устанавливает значение по указанному ключу.
	 *
	 * @param string $key Ключ
	 * @param mixed $value Значение
	 * @param int $expire Время жизни значения в секундах
	 * @return bool Возвращает true в случае успеха
	 */
	public function set($key, $value, $expire = 0)
	{
		$expire = ($expire == 0) ? Connect::$projectServices['memcached']['expire'] : $expire;
		if (!empty($this->memcache)) {
			$this->memcache->set($key, $value, false, $expire);
		} else if (!empty($this->memcached)) {
			$this->memcached->set($key, $value, $expire);
		}
		return true;
	}

	/**
	 * Получает значение из Memcached
	 *
	 * Этот метод получает значение по указанному ключу.
	 *
	 * @param string $key Ключ
	 * @return mixed Возвращает значение, если ключ найден, иначе null
	 */
	public function get($key)
	{
		if (!empty($this->memcache) && !empty($this->memcache->get($key))) {
			return $this->memcache->get($key);
		} else if (!empty($this->memcached) && !empty($this->memcached->get($key))) {
			return $this->memcached->get($key);
		}
	}
	/**
	 * Удаляет значение из Memcached
	 *
	 * Этот метод удаляет значение по указанному ключу.
	 *
	 * @param string $key Ключ
	 * @return bool Возвращает true в случае успеха
	 */
	public function delete($key)
	{
		if (!empty($this->memcache)) {
			$this->memcache->delete($key);
		} else if (!empty($this->memcached)) {
			$this->memcached->delete($key);
		}
		return true;
	}
}