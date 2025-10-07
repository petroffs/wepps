<?php
if (php_sapi_name() !== 'cli') {
	http_response_code(401);
	exit();
}
if (!is_file(__DIR__ . '/packages/vendor/autoload.php')) {
	echo 'no composer install';
}
require_once 'configloader.php';

use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsCore\Cli;

class Install
{
	private $cli;
	private $config;
	public function __construct()
	{
		$this->cli = new Cli();
		/*
		 * Если config.conf не создан - создать и заполнить
		 * Если уже существует
		 * 
		 * Выполнить установку
		 * 
		 * В финальном уведомлении предложить удалить файл install.php и перенести файл config.conf за переделы корневой папки
		 * И переименовать в config.cnf и вписать путь в файле config.php
		 * 
		 */
		$this->config = Connect::$projectDB;
		if (empty($this->config['host']) || empty($this->config['port']) || empty($this->config['user']) || empty($this->config['dbname'])) {
			$this->cli->error('Секция DB некорректно заполнена');
			exit();
		}
		#Utils::debug(is_file('d:/var/home/config.cnf'),31);
		if (empty($this->config['cnf'])) {
			#Utils::debug(is_file('d:/var/home/config.cnf'),31);
			$content = "[client]\n";
			$content .= "host=" . $this->config['host'] . "\n";
			$content .= "port=" . $this->config['port'] . "\n";
			$content .= "user=" . $this->config['user'] . "\n";
			$content .= "password=" . @$this->config['password'] . "\n";
			$this->cli->put($content, __DIR__ . '/config.conf');
			$this->config['cnf'] = __DIR__ . '/config.conf';
		} 
		if (!is_file($this->config['cnf'])) {
			$this->cli->error('wrong cnf-file ' . $this->config['cnf']);
		}
	}
	public function restoreDB()
	{
		/*
		 * exec mysql
		 */
		$filename = Connect::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/db.sql';
		if (!is_file($filename)) {
			$this->cli->error('wrong db-path');
			exit();
		}
		$this->cli->success('Заполнение базы данных. Ждите.');
		$cmd = "mysql --defaults-extra-file={$this->config['cnf']} {$this->config['dbname']} < $filename";
		$this->cli->cmd($cmd, false);
		$this->cli->success('База данных заполнена.');
	}
	public function runComposer()
	{
		$composerFolder = __DIR__ . '/packages';
		if (!is_file($composerFolder . '/composer.phar')) {
			$this->cli->error('wrong composer-path');
			exit();
		}
		$cmd = "cd $composerFolder && php composer.phar self-update --no-interaction && php composer.phar update --no-interaction";
		$this->cli->cmd($cmd);
		$this->cli->success('Composer обновлен/пакеты загружены');
	}
	public function displayFinalMessage()
	{
		$this->cli->success('Установка завершена.');
		if ($this->config['cnf'] == __DIR__ . '/config.conf') {
			$this->cli->warning("Скопируйте файл {$this->config['cnf']} за пределы корневой директории (../config.cnf)");
			$this->cli->warning('Укажите путь к этому файлу в настройках config.php (DB->cnf)');
		}
		$this->cli->warning('Удалите файл install.php.');
	}
}

$obj = new Install();
$obj->restoreDB();
#$obj->runComposer();
$obj->displayFinalMessage();