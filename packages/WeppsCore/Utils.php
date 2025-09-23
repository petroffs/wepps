<?php
namespace WeppsCore;

/**
 * Класс Utils представляет собой набор вспомогательных функций для
 * обработки данных, форматирования, отладки и работы
 * с HTTP-заголовками/куками.
 */
class Utils
{
	/**
	 * Универсальный метод для отладки и логгирования данных в приложении.
	 *
	 * Позволяет выводить информацию о переменных на экран или записывать в файл с возможностью
	 * указания формата вывода, добавления контекста (место вызова, время) и закрытия соединения.
	 *
	 * @param mixed $var Данные для отладки (переменная, массив, объект и т.д.)
	 * @param int $rule Режим вывода:
	 *                  - 2: Записать данные в файл (перезапись)
	 *                  - 21: Записать данные в файл и закрыть соединение
	 *                  - 22: Дописать данные в файл
	 *                  - 3: Вывести данные на экран (сырой текст)
	 *                  - 31: Вывести данные на экран и закрыть соединение
	 *                  - 1: Вывести данные в HTML-форматированном блоке
	 *                  - default: Аналогичен режиму 1, но без закрытия соединения
	 * @param string $filename Имя файла для записи (по умолчанию `debug.conf`)
	 * @return void
	 */
	public static function debug($var, int $rule = 0, string $filename = '')
	{
		$filename = (empty($filename)) ? __DIR__ . "/../../debug.conf" : $filename;
		$separator = "\n===================\n";
		$header = "";
		if (Connect::$projectDev['debug'] == 1) {
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
				Connect::$instance->close();
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
				Connect::$instance->close();
				break;
			case 1:
				$val = htmlspecialchars($val);
				$output = "\n<pre style='font:14px sans-serif;text-align:left;color:black;background: #80FF80;border:1px solid gray;box-sizing:border-box;margin:0;padding: 12px;width:100%;max-width:100%;height:400px;overflow:auto;position:relative;z-index:999;'>\n";
				$output .= $header . $val;
				$output .= "\n</pre>\n";
				echo $output;
				Connect::$instance->close();
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
	 *
	 * @param string|array $value Входная строка или массив строк для форматирования
	 * @param string $chr Символы для удаления из начала и конца строки
	 * @return string|array Отформатированная строка или массив строк
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
	 *
	 * @param string $string Входная строка с телефонным номером
	 * @return array Массив с форматированным номером и его представлением
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
	 *
	 * @param array $value Входной массив
	 * @param string $index Поле, которое будет использоваться в качестве ключа
	 * @param string $output Поле, которое будет использоваться в качестве значения (опционально)
	 * @return array Массив с ключами, установленными по указанному полю
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
	 *
	 * @param string $string Входная строка
	 * @param string $columns Разделитель столбцов
	 * @param string $rows Разделитель строк
	 * @return array Двумерный массив, полученный из строки
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
	 *
	 * @param mixed $value Значение для поиска
	 * @param array $array Входной массив
	 * @param mixed $key Ключ, по которому будет производиться поиск
	 * @return array Массив, содержащий найденные элементы
	 */
	public static function arrayFilter($value, array $array, $key = 'Id')
	{
		return array_filter($array, fn($v) => $v[$key] === $value, ARRAY_FILTER_USE_BOTH);
	}
	/**
	 * Генерация GUID
	 *
	 * @param string $string Входная строка для генерации GUID (опционально)
	 * @return string Сгенерированный GUID
	 */
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
	/**
	 * Округление числа
	 *
	 * @param float $number Число для округления
	 * @param int $scale Количество знаков после запятой
	 * @param string $type Тип возвращаемого значения ('float' или 'str')
	 * @return float|string Округленное число в виде float или строки
	 */
	public static function round($number, $scale = 2, $type = 'float')
	{
		$number = round($number, $scale);
		if ($type == 'str') {
			return number_format($number, $scale, ".", " ");
		}
		return (float)number_format($number, $scale, ".", "");
	}
	/**
	 * Получение всех HTTP-заголовков
	 *
	 * @return array Ассоциативный массив всех HTTP-заголовков
	 */
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
	/**
	 * Установка куки
	 *
	 * @param string $name Имя куки
	 * @param string $value Значение куки
	 * @param int $lifetime Время жизни куки в секундах
	 * @return void
	 */
	public static function cookies(string $name, string $value = '', int $lifetime = 86400)
	{
		$settings = [
			'expires' => time() + $lifetime,
			'path' => '/',
			'domain' => Connect::$projectDev['host'],
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

if (!function_exists('getallheaders')) {
	/**
	 * Получение всех HTTP-заголовков (если функция не существует)
	 *
	 * @return array Ассоциативный массив всех HTTP-заголовков
	 */
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