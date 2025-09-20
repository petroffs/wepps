<?php
namespace WeppsCore;
abstract class Request
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
		$this->get = Utils::trim($settings);
		if (php_sapi_name() === 'cli') {
			$this->cli = new Cli();
			$this->get['action'] = $settings[1];
		}
		$action = (isset($this->get['action'])) ? $this->get['action'] : '';
		$this->request($action);
		if ($this->noclose == 0) {
			if ($this->tpl == '') {
				Connect::$instance->close();
			}
			$this->cssjs();
			Connect::$instance->close(0);
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
		$smarty = Smarty::getSmarty();
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
		$smarty = Smarty::getSmarty();
		foreach ($this->assign as $k => $v) {
			$smarty->assign($k, $v);
		}
		$smarty->assign($key, $smarty->fetch($value));
	}
	public function outer(string $message = '', bool $print = true): array
	{
		$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
		if ($print === true) {
			echo $outer['html'];
		}
		if ($outer['count'] == 0) {
			$outer = Validator::setFormSuccess($message, $this->get['form']);
			if ($print === true) {
				echo $outer['html'];
			}
		}
		return $outer;
	}
}