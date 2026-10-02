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
	private $isSystemCache = false;

	/**
	 * Конструктор класса Memcached
	 *
	 * Инициализирует соединение с Memcached сервером.
	 *
	 * @param string $isActive Флаг активности Memcached (yes, no, auto)
	 * @param bool $isSystemCache Флаг системного кэша - включается независимо от конфига (system cache для метаданных)
	 */
	public function __construct($isActive = 'auto', $isSystemCache = false)
	{
		$this->isSystemCache = $isSystemCache;
		$memcachedConfig = Connect::$projectServices['memcached'] ?? [];
		$configActive = (bool) ($memcachedConfig['active'] ?? false);
		if (!$configActive) {
			return;
		}
		switch ($isActive) {
			case 'yes':
				$isActive = true;
				break;
			case 'no':
				$isActive = false;
				break;
			default:
				$isActive = true;
				break;
		}
		$host = isset(Connect::$projectServices['memcached']['host'])
			? trim((string) Connect::$projectServices['memcached']['host'])
			: '';
		if (!$isActive || $host === '') {
			return;
		}
		$port = (int) (Connect::$projectServices['memcached']['port'] ?? 11211);
		set_error_handler(function ($errno, $errstr) {
			throw new \RuntimeException($errstr, $errno);
		});
		try {
			if (class_exists('Memcache')) {
				$this->memcache = new \Memcache();
				if (!$this->memcache->connect($host, $port)) {
					throw new \RuntimeException("Memcached connection failed: {$host}:{$port}");
				}
			} else if (class_exists('Memcached')) {
				$this->memcached = new \Memcached();
				if (!$this->memcached->addServer($host, $port)) {
					throw new \RuntimeException("Memcached server registration failed: {$host}:{$port}");
				}
			}
		} catch (\Throwable $e) {
			throw new \RuntimeException("Memcached connection error for {$host}:{$port}: {$e->getMessage()}", 0, $e);
		} finally {
			restore_error_handler();
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

	/**
	 * Очистить весь Memcached кэш
	 *
	 * @return bool Возвращает true в случае успеха
	 */
	public function flushAll()
	{
		if (!empty($this->memcache)) {
			$this->memcache->flush();
		} else if (!empty($this->memcached)) {
			$this->memcached->flush();
		}
		return true;
	}
}