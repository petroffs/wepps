<?php
namespace WeppsCore\Utils;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsExtensions\Addons\Jwt\JwtWepps;

/**
 * Summary of UtilsWepps
 */
class UtilsWepps
{
	/**
	 * Debug
	 */
	public static function debug($var, int $rule = 0, string $filename = '')
	{
		$filename = (empty($filename)) ? __DIR__ . "/../../debug.conf" : $filename;
		$separator = "\n===================\n";
		$header = "";
		if (ConnectWepps::$projectDev['debug'] == 1) {
			$backtrace = debug_backtrace();
			$header = "" . date('Y-m-d H:i:s') . "\n";
			$header .= "{$backtrace[0]['file']}:{$backtrace[0]['line']}\n";
			if (!empty($backtrace[1]['file'])) {
				$header .= "{$backtrace[1]['file']}:{$backtrace[1]['line']}\n";
			}
			if (!empty($backtrace[2]['file'])) {
				$header .= "{$backtrace[2]['file']}:{$backtrace[2]['line']}\n";
			}
			$header = trim($header) . $separator;
		}
		$val = print_r($var, true);
		switch ($rule) {
			case 2:
				$output = $header . $val;
				file_put_contents($filename, $output);
				break;
			case 21:
				$output = $header . $val;
				file_put_contents($filename, $output);
				ConnectWepps::$instance->close();
				break;
			case 22:
				$output = $header . $val . "\n\n";
				$fp = fopen($filename, 'a+');
				fwrite($fp, $output);
				fclose($fp);
				break;
			case 3:
				$output = $header . $val;
				echo $output . "\n\n";
				break;
			case 31:
				$output = $header . $val;
				echo $output . "\n\n";
				ConnectWepps::$instance->close();
				break;
			case 1:
				$val = htmlspecialchars($val);
				$output = "\n<pre style='font:14px sans-serif;text-align:left;color:black;background: #80FF80;border:1px solid gray;box-sizing:border-box;margin:0;padding: 12px;width:100%;max-width:100%;height:400px;overflow:auto;position:relative;z-index:999;'>\n";
				$output .= $header . $val;
				$output .= "\n</pre>\n";
				echo $output;
				ConnectWepps::$instance->close();
				break;
			default:
				$val = htmlspecialchars($val);
				$output = "\n<pre style='font:14px sans-serif;text-align:left;color:black;background: #80FF80;border:1px solid gray;box-sizing:border-box;margin:0;padding: 12px;width:100%;max-width:100%;height:400px;overflow:auto;position:relative;z-index:999;'>\n";
				$output .= $header . $val;
				$output .= "\n</pre>\n";
				echo $output;
				break;
		}
	}

	/**
	 * Форматирование входной строки
	 * @param (string|array) $value
	 * @return string
	 */
	public static function trim($value, string $chr = '')
	{
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k1 => $v1) {
						$value[$k][$k1] = self::trim($v1, $chr);
					}
				} else {
					$value[$k] = self::trim($v, $chr);
				}
			}
			return $value;
		}
		if (is_string($value)) {
			$value = trim($value, $chr);
		}
		#$value = htmlspecialchars ( $value );
		return $value;
	}

	/**
	 * Форматирование телефонного номера
	 * @param string|array $value
	 * @return array
	 */
	public static function phone(string $string = ''): array
	{
		$num = preg_replace("/[^0-9]/", "", $string);
		$num = preg_replace("/^8(.+)/", "7$1", $num);
		if (strlen($num) == 11 && substr($num, 0, 2) == '79') {
			#РФ
			$part1 = substr($num, 0, 4);
			$part2 = substr($num, 4, 3);
			$part3 = substr($num, 7, 2);
			$part4 = substr($num, 9, 2);
			return [
				'num' => (int) $num,
				'view' => "+" . $part1 . " " . $part2 . "-" . $part3 . "-" . $part4
			];
		} elseif (strlen($num) == 12 && substr($num, 0, 3) == '375') {
			#РБ
			$part1 = substr($num, 0, 5);
			$part2 = substr($num, 5, 3);
			$part3 = substr($num, 8, 2);
			$part4 = substr($num, 10, 2);
			return [
				'num' => (int) $num,
				'view' => "+" . $part1 . " " . $part2 . "-" . $part3 . "-" . $part4
			];
		}
		return [];
	}
	/**
	 * Установка массиву ключей по произвольному полю
	 * @param array $value
	 * @param string $index
	 * @return array
	 */
	public static function array(array $value, string $index = 'Id', string $output = ''): array
	{
		$arr = [];
		if (!is_array($value))
			return [];

		if ($output == '') {
			foreach ($value as $v) {
				$arr[$v[$index]] = $v;
			}
		} else {
			foreach ($value as $v) {
				$arr[$v[$index]] = $v[$output];
			}
		}
		return $arr;
	}

	/**
	 * Получить массив из строки
	 */
	public static function arrayFromString(string $string, string $columns = "\t", string $rows = "\n"): array
	{
		$output = [];
		$string = str_replace("\r", "", $string);
		$string = trim($string);

		$ex = explode($rows, $string);
		foreach ($ex as $i => $value) {
			$tabs = explode($columns, trim($value));
			foreach ($tabs as $j => $v) {
				$output[$i][$j] = $v;
			}
		}
		return $output;
	}
	/**
	 * Поиск элемента в массиве по значению
	 * @param mixed $value
	 * @param mixed $key
	 * @param array $array
	 * @return array
	 */
	public static function arrayFilter($value,$key='Id',array $array) {
		return array_filter($array, fn($v) => $v[$key]===$value, ARRAY_FILTER_USE_BOTH);
	}
	public static function guid(string $string = ''): string
	{
		$charid = ($string == '') ? strtolower(md5(uniqid(rand(), true))) : strtolower(md5($string));
		$guid = substr($charid, 0, 8) . '-' .
			substr($charid, 8, 4) . '-' .
			substr($charid, 12, 4) . '-' .
			substr($charid, 16, 4) . '-' .
			substr($charid, 20, 12);
		return $guid;
	}
	public static function round($number, $scale = 2, $type = 'float')
	{
		for ($i=8;$i>=$scale;$i=$i-2) {
			$number = round($number, $i);
		}
		if ($type == 'str') {
			return number_format($number, $scale, ".", " ");
		}
		return doubleval(number_format($number, $scale, ".", ""));
	}
	public static function getAllHeaders(): array
	{
		$headers = getallheaders();
		#array_map('strtolower', $haystack);
		foreach ($headers as $key => $value) {
			unset($headers[$key]);
			$headers[strtolower($key)] = $value;
		}
		return $headers;
	}
	public static function cookies(string $name, string $value = '', int $lifetime = 86400)
	{
		$settings = [
			'expires' => time() + $lifetime,
			'path' => '/',
			'domain' => ConnectWepps::$projectDev['host'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'strict',
		];
		if (empty($value)) {
			unset($settings['expires']);
		}
		setcookie($name, $value, $settings);
	}
}

/**
 * Запросы AJAX
 */
abstract class RequestWepps
{
	/**
	 * Массив с входными переменными
	 * @var array
	 */
	public $get = [];

	/**
	 * Переменные для шаблонов
	 * @var array
	 */
	private $assign = [];

	/**
	 * Шаблон вывода
	 * @var string
	 */
	public $tpl;

	/**
	 * Инициализация $smarty
	 * @var \Smarty
	 */
	#private $smarty;

	/**
	 * Подключение шаблона, который передается в общий шаблон self::$tpl
	 * @var array
	 */
	private $fetch = [];

	/**
	 * Закрытие соединения с БД
	 */
	public $noclose = 0;

	/**
	 * Ошибки входных данных
	 */
	public $errors = [];

	public $cli;

	public function __construct(array $settings = [])
	{
		$this->get = UtilsWepps::trim($settings);
		if (php_sapi_name() === 'cli') {
			$this->cli = new CliWepps();
			$this->get['action'] = $settings[1];
		}
		$action = (isset($this->get['action'])) ? $this->get['action'] : '';
		$this->request($action);
		if ($this->noclose == 0) {
			if ($this->tpl == '') {
				ConnectWepps::$instance->close();
			}
			$this->cssjs();
			ConnectWepps::$instance->close(0);
		}
		return;
	}

	/**
	 * Обработка запроса (Реализация логики)
	 */
	abstract public function request(string $action = '');

	/**
	 * Подключение стилей и js-сценариев
	 * Подключаются автоматически, при наличии файла:
	 * Для шаблона $this->tpl = "RequestExample.tpl" требуется
	 * файл RequestExample.tpl.css или RequestExample.tpl.js
	 * Шаблон следует определять в self::request()
	 */
	private function cssjs()
	{
		if ($this->tpl == '')
			return;
		$smarty = SmartyWepps::getSmarty();
		$css = (is_file($this->tpl . '.css')) ? 1 : 0;
		$js = (is_file($this->tpl . '.js')) ? 1 : 0;
		foreach ($this->assign as $key => $value) {
			$smarty->assign($key, $value);
		}
		if ($css == 1 || $js == 1) {
			$this->get['cssjs'] = '';
			if ($css == 1)
				$this->get['cssjs'] = "<style>{$smarty->fetch($this->tpl . '.css')}</style>";
			if ($js == 1)
				$this->get['cssjs'] .= "<script type=\"text/javascript\">{$smarty->fetch($this->tpl . '.js')}</script>";
		}
	}
	public function assign($key, $value)
	{
		$this->assign[$key] = $value;
	}
	public function fetch($key, $value)
	{
		$smarty = SmartyWepps::getSmarty();
		foreach ($this->assign as $k => $v) {
			$smarty->assign($k, $v);
		}
		$smarty->assign($key, $smarty->fetch($value));
	}
}

/**
 * Генерация html-кода ссылок на css-таблицы и js-библиотеки для применения в шаблоне сайта
 * Генерация html-кода meta-тегов
 */
class TemplateHeadersWepps
{
	public static $rand;
	private $output = [
		'meta' => '',
		'cssjs' => ''
	];
	private $cssjs = [
		'js' => [],
		'css' => []
	];

	public function js(string $filename): string
	{
		#return $this->cssjs['js'][] = (string) "\n" . '<script type="text/javascript" src="' . $filename . '"></script>';
		return $this->cssjs['js'][] = $filename;
	}
	public function css(string $filename): string
	{
		#return $this->cssjs['css'][] = (string) "\n" . '<link rel="stylesheet" type="text/css" href="' . $filename . '"/>';
		return $this->cssjs['css'][] = $filename;
	}
	public function meta(string $meta): string
	{
		return $this->output['meta'] .= (string) "\n" . $meta;
	}
	public function resetMeta(): string
	{
		return $this->output['meta'] = "";
	}
	public function join(TemplateHeadersWepps $headers): void
	{
		$this->cssjs['js'] = array_merge($this->cssjs['js'], $headers->cssjs['js']);
		$this->cssjs['css'] = array_merge($this->cssjs['css'], $headers->cssjs['css']);
	}
	/**
	 * Установка $this->output - содержит html-код
	 * @return string[]
	 */
	private function prepare(bool $libOnly = false): array
	{
		$this->cssjs['css'] = array_unique($this->cssjs['css']);
		$this->cssjs['js'] = array_unique($this->cssjs['js']);
		$this->output['cssjs'] = "";
		foreach ($this->cssjs['css'] as $filename) {
			if ($libOnly == true && strstr($filename, $this::$rand)) {
				continue;
			}
			$this->output['cssjs'] .= (string) "\n" . '<link rel="stylesheet" type="text/css" href="' . $filename . '"/>';
		}
		foreach ($this->cssjs['js'] as $filename) {
			if ($libOnly == true && strstr($filename, $this::$rand)) {
				continue;
			}
			$this->output['cssjs'] .= (string) "\n" . '<script type="text/javascript" src="' . $filename . '"></script>';
		}
		$this->output['cssjs'] = trim($this->output['cssjs'], "\n");
		return $this->output;
	}
	public function get(): array
	{
		if (ConnectWepps::$projectServices['minify']['active'] === false) {
			return $this->prepare();
		}
		$arr = $this->prepare(true);
		$hash = md5(implode('', $this->cssjs['css']) . implode('', $this->cssjs['js']));
		$filehtml = __DIR__ . '/../../files/tpl/minify/' . $hash;
		if (is_file($filehtml)) {
			$currenttime = time();
			$liftime = ConnectWepps::$projectServices['minify']['lifetime'];
			$filetime = filemtime($filehtml);
			if (($currenttime - $filetime) > $liftime) {
				unlink($filehtml);
			} else {
				$arr['cssjs'] .= "\n" . file_get_contents($filehtml);
				return $arr;
			}
		}
		$minifier = new CSS();
		$output = "<style>";
		foreach ($this->cssjs['css'] as $filename) {
			$filename = ConnectWepps::$projectDev['root'] . str_replace('/ext/', '/packages/WeppsExtensions/', $filename);
			$filename = str_replace($this::$rand . '.', '', $filename);
			if (!is_file($filename) || !strstr($filename, '/Wepps')) {
				continue;
			}
			$minifier->add($filename);
		}
		$output .= $minifier->minify();
		$output .= "</style>\n";
		$minifier = new JS();
		$output .= "<script type=\"text/javascript\">";
		foreach ($this->cssjs['js'] as $filename) {
			$filename = ConnectWepps::$projectDev['root'] . str_replace('/ext/', '/packages/WeppsExtensions/', $filename);
			$filename = str_replace($this::$rand . '.', '', $filename);
			if (!is_file($filename) || !strstr($filename, '/Wepps')) {
				continue;
			}
			$minifier->add($filename);
		}

		/**
		 * Тестирование
		 */
		/* $t = $minifier->minify();
		if (strstr($t, "\n")) {
			echo $t;
			exit();
		}
		echo 'OK';
		exit(); */

		$output .= $minifier->minify();
		$output .= "</script>";
		$cli = new CliWepps();
		$cli->put($output, $filehtml);
		$arr['cssjs'] .= (string) "\n" . $output;
		return $arr;
	}
}

/**
 * Командная строка
 */
class CliWepps
{
	private $display = 0;
	public function __construct()
	{
		$this->display();
	}
	public function display(bool $display = true)
	{
		$this->display = $display;
	}
	public function error(string $text = '')
	{
		return self::outer(self::color("[error] $text", 'e'));
	}
	public function success(string $text = '')
	{
		return self::outer(self::color("[success] $text", 's'));
	}
	public function warning(string $text = '')
	{
		return self::outer(self::color("[warning] $text", 'w'));
	}
	public function info(string $text = '')
	{
		return self::outer(self::color("[info] $text", 'i'));
	}
	public function text(string $text = '')
	{
		return self::outer(self::color($text));
	}
	public function br()
	{
		return self::outer("\n");
	}
	public function progress($done, $total)
	{
		$perc = floor(($done / $total) * 100);
		$left = 100 - $perc;
		$rate = 0.5;
		$perc2 = floor($perc * $rate);
		$left2 = ceil($left * $rate);
		$write = sprintf("\033[0G\033[2K[%'#{$perc2}s#%-{$left2}s] $done/$total [$perc%%]", "", "");
		echo $write;
	}
	public function copy(string $source, string $destination, bool $overwrite = true): bool
	{
		if ($overwrite === false && file_exists($destination)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!copy($source, $destination)) {
			return false;
		}
		return true;
	}
	public function move(string $source, string $destination, bool $overwrite = true)
	{
		if ($overwrite === false && file_exists($destination)) {
			return false;
		} elseif (!file_exists($source)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!rename($source, $destination)) {
			return false;
		}
		return true;
	}
	public function put($content, $destination)
	{
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!file_put_contents($destination, $content)) {
			return false;
		}
		return true;
	}
	public function mkdir(string $dir): bool
	{
		$dir = str_replace('\\', '/', $dir);
		if (!stristr($dir, ConnectWepps::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		return true;
	}
	public function rmdir(string $dir)
	{
		$dir = str_replace('\\', '/', $dir);
		if (!is_dir($dir)) {
			$this->warning('no dir');
			return false;
		} elseif (!stristr($dir, ConnectWepps::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		exec("rm $dir -rf");
		return true;
	}
	public function rmfile(string $file)
	{
		if (!file_exists($file)) {
			return false;
		}
		unlink($file);
		return true;
	}
	public function cmd(string $cmd, bool $silent = false): array
	{
		if (empty($cmd)) {
			$this->warning("cmd is empty");
		}
		$o = [];
		$v = 100;
		exec("$cmd 2>&1", $o, $v);
		if ($v != 0) {
			if (!empty($o[0])) {
				$this->error($o[0]);
			} else {
				$this->error('cmd');
			}
			exit();
		}
		if ($silent == false && !empty($o)) {
			$this->info(implode("\n", $o));
		}
		return $o;
	}
	private function color(string $str = '', string $type = ''): string
	{
		$output = '';
		switch ($type) {
			case 'e': //error
				$output = "\033[0;31;47m$str\033[0m\n";
				break;
			case 's': //success
				$output = "\033[32m$str\033[0m\n";
				break;
			case 'w': //warning
				$output = "\033[33m$str\033[0m\n";
				break;
			case 'i': //info
				$output = "\033[36m$str\033[0m\n";
				break;
			default:
				$output = "$str\n";
				break;
		}
		return $output;
	}
	private function outer(string $text = ''): string
	{
		if ($this->display == true) {
			echo $text;
		}
		return $text;
	}
}

class UsersWepps
{
	private $token = '';
	private $get = [];
	private $errors = [];
	public function __construct(array $settings = [])
	{
		$this->get = $settings;
	}
	public function signIn(): bool
	{
		$sql = "select * from s_Users where Login=? and ShowAdmin=1 and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql, [$this->get['login']]);
		$this->errors = [];
		if (empty($res[0]['Id'])) {
			$this->errors['login'] = 'Неверный логин';
		} elseif (strlen($res[0]['Password']) == 32) {
			if (md5($this->get['password']) != $res[0]['Password']) {
				$this->errors['password'] = 'Неверный пароль';
			}
		} elseif (!password_verify($this->get['password'], $res[0]['Password'])) {
			$this->errors['password'] = 'Неверный пароль';
		}
		if (!empty($this->errors)) {
			return false;
		}
		$lifetime = 3600 * 24 * 180;
		$jwt = new JwtWepps();
		$token = $jwt->token_encode([
			'typ' => 'auth',
			'id' => $res[0]['Id']
		], $lifetime);
		UtilsWepps::cookies('wepps_token', $token, $lifetime);
		ConnectWepps::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?", [date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], password_hash($this->get['password'], PASSWORD_BCRYPT), $res[0]['Id']]);
		return true;
	}
	public function errors()
	{
		return $this->errors;
	}
	public function getAuth(): bool
	{
		$allheaders = ConnectWepps::$projectData['headers'] = UtilsWepps::getAllheaders();
		$token = '';
		if (!empty($allheaders['authorization']) && strstr($allheaders['authorization'], 'Bearer ')) {
			$token = str_replace('Bearer ', '', $allheaders['authorization']);
		}
		$token = (empty($token) && !empty($_COOKIE['wepps_token'])) ? @$_COOKIE['wepps_token'] : $token;
		if (empty($token)) {
			return false;
		}
		$jwt = new JwtWepps();
		$data = $jwt->token_decode($token);
		if (@$data['payload']['typ'] != 'auth' || empty($data['payload']['id'])) {
			UtilsWepps::cookies('wepps_token');
			return false;
		}
		$sql = "select * from s_Users where Id=? and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql, [$data['payload']['id']]);
		ConnectWepps::$projectData['user'] = $res[0];
		return true;
	}
	public function removeAuth(): bool
	{
		if (empty(ConnectWepps::$projectData['user'])) {
			return false;
		}
		UtilsWepps::cookies('wepps_token');
		return true;
	}
	public function password(): string
	{
		$letters = ['a', 'o', 'u', 'i', 'e', 'y', 'A', 'U', 'I', 'E', 'Y', 'w', 'r', 't', 'k', 'm', 'n', 'b', 'h', 'd', 's', 'W', 'R', 'T', 'K', 'M', 'N', 'B', 'H', 'D', 'S'];
		$symbols = ['.', '$', '-', '!'];
		$arr = [];
		for ($i = 1; $i <= 8; $i++) {
			$arr[] = $letters[rand(0, count($letters) - 1)];
		}
		$arr[] = rand(1, 9);
		$arr[] = rand(1, 9);
		$arr[] = $symbols[rand(0, count($symbols) - 1)];
		shuffle($arr);
		return implode('', $arr);
	}
}

class MemcachedWepps {
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
				$isActive = ConnectWepps::$projectServices['memcached']['active'];
				break;
		}
		if (class_exists('Memcache') && $isActive) {
			$this->memcache = new \Memcache();
			$this->memcache->connect(ConnectWepps::$projectServices['memcached']['host'], ConnectWepps::$projectServices['memcached']['port']);
		} else if (class_exists('Memcached') && $isActive) {
			$this->memcached = new \Memcached();
			$this->memcached->addServer(ConnectWepps::$projectServices['memcached']['host'], ConnectWepps::$projectServices['memcached']['port']);
		}
	}
	public function set($key,$value,$expire = 0) {
		$expire = ($expire == 0) ? ConnectWepps::$projectServices['memcached']['expire'] : $expire;
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

if (!function_exists('getallheaders')) {
	function getallheaders()
	{
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}

class LogsWepps {
	public function __construct() {

	}
	public function add(string $name, array $jdata, string $date = '', string $ip = '', string $type = 'cli')
	{
		$row = [
			'Name' => $name,
			'LDate' => ($date != '') ? $date : date('Y-m-d H:i:s'),
			'IP' => ($ip != '') ? $ip : @$_SERVER['REMOTE_ADDR']??'',
			'BRequest' => json_encode($jdata, JSON_UNESCAPED_UNICODE),
			'TRequest' => $type,
		];
		if ($type=='post') {
			$row['Url'] = @$_SERVER['REQUEST_URI'];
		}
		$prepare = ConnectWepps::$instance->prepare($row);
		$insert = ConnectWepps::$db->prepare("insert into s_LocalServicesLog {$prepare['insert']}");
		$insert->execute($row);
	}
	public function update(int $id,array $response,int $status=200) {
		$row = [
			'InProgress' => 1,
			'IsProcessed' => 1,
			'BResponse' => json_encode($response,JSON_UNESCAPED_UNICODE),
			'SResponse' => $status,
		];
		$prepare = ConnectWepps::$instance->prepare($row);
		$sql = "update s_LocalServicesLog set {$prepare['update']} where Id = :Id";
		ConnectWepps::$instance->query($sql,array_merge($prepare['row'],['Id'=>$id]));
		return [
			'id' => $id,
			'response' => $response,
			'status' => $status,
		];
	}
}