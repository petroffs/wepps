<?php

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\CliWepps;

if (php_sapi_name() !== 'cli') {
	http_response_code(401);
	exit();
}

require_once 'config.php';
require_once 'autoloader.php';
require_once 'configloader.php';

class Install {
	private $cli;
	private $cnf;
	private $config;
	public function __construct() {
		$this->cli = new CliWepps();
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
		$this->config = ConnectWepps::$projectDB;
		if (empty($this->config['host']) || empty($this->config['port']) || empty($this->config['user']) || empty($this->config['dbname'])) {
			$this->cli->error('Секция DB некорректно заполнена');
			exit();
		}
		if (empty($this->config['cnf']) || !is_file($this->config['cnf'])) {
			$content = "[client]\n";
			$content .= "host=".$this->config['host']."\n";
			$content .= "port=".$this->config['port']."\n";
			$content .= "user=".$this->config['user']."\n";
			$content .= "password=".@$this->config['password']."\n";
			$this->cli->put($content, __DIR__ . '/config.conf');
			$this->cnf = __DIR__ . '/config.conf';
		} elseif (empty($this->config['cnf']) && is_file($this->config['cnf'])) {
			$this->cnf = $this->config['cnf'];
		}
	}
	public function restoreDB() {
		/*
		 * exec mysql
		 */
		$filename = ConnectWepps::$projectDev['root'].'/packages/WeppsAdmin/ConfigExtensions/Backup/files/db.sql';
		if (!is_file($filename)) {
			$this->cli->error('wrong db-path');
			exit();
		}
		$cmd = "mysql --defaults-extra-file={$this->cnf} {$this->config['dbname']} < $filename";
		$this->cli->cmd($cmd,false);
		$this->cli->success('База данных заполнена');
	}
	public function runComposer() {
		$composerFolder = __DIR__ . '/packages';
		if (!is_file($composerFolder.'/composer.phar')) {
			$this->cli->error('wrong composer-path');
			exit();
		}
		$cmd = "cd $composerFolder && php composer.phar self-update --no-interaction && php composer.phar update --no-interaction";
		$this->cli->cmd($cmd);
		$this->cli->success('Composer обновлен/пакеты загружены');
	}
	public function displayFinalMessage() {
		$this->cli->success('Установка завершена.');
		if ($this->cnf == __DIR__ . '/config.conf') {
			$this->cli->warning('Скопируйте файл {$this->cnf} за пределы корневой директории (../config.cnf)');
			$this->cli->warning('Укажите путь к этому файлу в настройках config.php (DB->cnf)');
		}
		$this->cli->warning('Удалите файл install.php');
	}
}

$obj = new Install();
$obj->restoreDB();
$obj->runComposer();
$obj->displayFinalMessage();