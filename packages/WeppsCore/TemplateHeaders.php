<?php
namespace WeppsCore;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
/**
 * Генерация html-кода ссылок на css-таблицы и js-библиотеки для применения в шаблоне сайта
 * Генерация html-кода meta-тегов
 */
class TemplateHeaders
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

	/**
	 * Добавляет путь к JavaScript файлу в список для подключения.
	 *
	 * @param string $filename Путь к JavaScript файлу.
	 * @return string Возвращает путь к JavaScript файлу.
	 */
	public function js(string $filename): string
	{
		#return $this->cssjs['js'][] = (string) "\n" . '<script type="text/javascript" src="' . $filename . '"></script>';
		return $this->cssjs['js'][] = $filename;
	}

	/**
	 * Добавляет путь к CSS файлу в список для подключения.
	 *
	 * @param string $filename Путь к CSS файлу.
	 * @return string Возвращает путь к CSS файлу.
	 */
	public function css(string $filename): string
	{
		#return $this->cssjs['css'][] = (string) "\n" . '<link rel="stylesheet" type="text/css" href="' . $filename . '"/>';
		return $this->cssjs['css'][] = $filename;
	}

	/**
	 * Добавляет meta-тег в список для подключения.
	 *
	 * @param string $meta HTML-код meta-тега.
	 * @return string Возвращает HTML-код meta-тега.
	 */
	public function meta(string $meta): string
	{
		return $this->output['meta'] .= (string) "\n" . $meta;
	}

	/**
	 * Очищает список meta-тегов.
	 *
	 * @return string Возвращает пустую строку.
	 */
	public function resetMeta(): string
	{
		return $this->output['meta'] = "";
	}

	/**
	 * Объединяет списки CSS и JS файлов из другого объекта TemplateHeaders.
	 *
	 * @param TemplateHeaders $headers Объект TemplateHeaders, списки CSS и JS файлов которого будут объединены.
	 * @return void
	 */
	public function join(TemplateHeaders $headers): void
	{
		$this->cssjs['js'] = array_merge($this->cssjs['js'], $headers->cssjs['js']);
		$this->cssjs['css'] = array_merge($this->cssjs['css'], $headers->cssjs['css']);
	}

	/**
	 * Подготавливает HTML-код для подключения CSS и JS файлов.
	 *
	 * @param bool $libOnly Флаг, указывающий, нужно ли подключать только библиотеки.
	 * @return string[] Возвращает массив с HTML-кодом для подключения CSS и JS файлов.
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

	/**
	 * Возвращает HTML-код для подключения CSS и JS файлов, с учетом минификации.
	 *
	 * @return string[] Возвращает массив с HTML-кодом для подключения CSS и JS файлов.
	 */
	public function get(): array
	{
		if (Connect::$projectServices['minify']['active'] === false) {
			return $this->prepare();
		}
		$arr = $this->prepare(true);
		$hash = md5(implode('', $this->cssjs['css']) . implode('', $this->cssjs['js']));
		$filehtml = __DIR__ . '/../../files/tpl/minify/' . $hash;
		if (is_file($filehtml)) {
			$currenttime = time();
			$liftime = Connect::$projectServices['minify']['lifetime'];
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
			$filename = Connect::$projectDev['root'] . str_replace('/ext/', '/packages/WeppsExtensions/', $filename);
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
			$filename = Connect::$projectDev['root'] . str_replace('/ext/', '/packages/WeppsExtensions/', $filename);
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
		$cli = new Cli();
		$cli->put($output, $filehtml);
		$arr['cssjs'] .= (string) "\n" . $output;
		return $arr;
	}
}