<?php

if (php_sapi_name() !== 'cli') {
	http_response_code(401);
	exit();
}

if (!is_file(__DIR__ . '/packages/vendor/autoload.php')) {
	if (!is_file(__DIR__ . '/packages/composer.phar')) {
		echo 'Неправильный путь к установщику\n';
		exit();
	}
	$dir = str_replace('\\', '/', __DIR__);
	$message = "Необходимо выполнить composer install\nВыполните следующие команды:\ncd {$dir}/packages && php composer.phar self-update && php composer.phar install && cd ../ && php install.php\n";
	echo $message;
	exit();
}

require_once 'config.php';

if (empty($projectSettings['DB']['host']) || empty($projectSettings['DB']['port']) || empty($projectSettings['DB']['user']) || empty($projectSettings['DB']['dbname'])) {
	echo "error: Секция DB некорректно заполнена\n";
	exit();
}

require_once 'configloader.php';

use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsCore\Cli;

/**
 * Класс для выполнения установки приложения.
 */
class Install
{
	private Cli $cli;
	private array $config;

	/**
	 * Конструктор класса.
	 */
	public function __construct()
	{
		$this->cli = new Cli();
		$this->config = Connect::$projectDB;
		// if (empty($this->config['host']) || empty($this->config['port']) || empty($this->config['user']) || empty($this->config['dbname'])) {
		// 	$this->cli->error('Секция DB некорректно заполнена');
		// 	exit();
		// }
		if (empty($this->config['cnf'])) {
			$content = "[client]\n";
			$content .= "host=" . $this->config['host'] . "\n";
			$content .= "port=" . $this->config['port'] . "\n";
			$content .= "user=" . $this->config['user'] . "\n";
			$content .= "password=" . @$this->config['password'] . "\n";
			$this->cli->put($content, __DIR__ . '/config.conf');
			$this->config['cnf'] = __DIR__ . '/config.conf';
		}

		if (!is_file($this->config['cnf'])) {
			$this->cli->error('Неправильный путь к cnf-файлу: ' . $this->config['cnf']);
			exit();
		}
	}

	/**
	 * Восстановление базы данных из резервной копии.
	 */
	public function restoreDB(): void
	{
		$filename = Connect::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/db.sql';

		if (!is_file($filename)) {
			$this->cli->error('Неправильный путь к файлу базы данных');
			exit();
		}

		$this->cli->success('Заполнение базы данных. Ждите.');
		$cmd = "mysql --defaults-extra-file={$this->config['cnf']} {$this->config['dbname']} < $filename";
		$this->cli->cmd($cmd, false);
		$this->cli->success('База данных заполнена.');
	}

	/**
	 * Отображение финального сообщения после завершения установки.
	 */
	public function displayFinalMessage(): void
	{
		$this->cli->success('Установка завершена.');

		if ($this->config['cnf'] === __DIR__ . '/config.conf') {
			$this->cli->warning("Скопируйте файл {$this->config['cnf']} за пределы корневой директории (../config.cnf)");
			$this->cli->warning('Укажите путь к этому файлу в настройках config.php (DB->cnf)');
		}

		$this->cli->warning('Удалите файл install.php.');
	}
}

$obj = new Install();
$obj->restoreDB();
$obj->displayFinalMessage();