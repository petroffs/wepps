<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

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
	 *                  - 1: Вывести данные на экран и закрыть соединение
	 *                  - default: Вывести данные на экран
	 *                  - 3, 31: Устаревшие алиасы default и 1 соответственно
	 *                  Вывод на экран: в CLI — сырые строки, в вебе — экранированный HTML-блок. соединения
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
			case 31:
			case 1:
			default:
				// Экран: в CLI — сырые данные как есть (терминал), в вебе —
				// экранированный HTML-блок. 3/31 — устаревшие алиасы default/1;
				// close() выполняется для правил 1 и 31, как и раньше.
				if (PHP_SAPI === 'cli') {
					echo $header . $val . "\n\n";
				} else {
					echo "\n<pre style='font:14px sans-serif;text-align:left;color:black;background: #80FF80;border:1px solid gray;box-sizing:border-box;margin:0;padding: 12px;width:100%;max-width:100%;height:400px;overflow:auto;position:relative;z-index:999;'>\n"
						. $header . htmlspecialchars($val) . "\n</pre>\n";
				}
				if ($rule === 1 || $rule === 31) {
					Connect::$instance->close();
				}
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
	 * Белый список символов для безопасной подстановки пользовательских строк
	 * в URL, компоненты URL и HTTP-заголовки: переносы строк, кавычки, пробелы
	 * и прочие спецсимволы удаляются. Буквы всех языков (\p{L}) и цифры (\p{N})
	 * сохраняются — кириллица в путях и именах файлов не пострадает.
	 *
	 * @param string $value Строка (в том числе пользовательский ввод)
	 * @param string $pattern PCRE-шаблон фильтрации без ограничителей ^$:
	 *                        по умолчанию удаляется всё вне белого списка;
	 *                        можно передать и шаблон удаляемых символов ([...]+)
	 * @return string Отфильтрованная строка
	 *
	 * @psalm-taint-escape redirect
	 * @psalm-taint-escape header
	 * @psalm-taint-escape url_component
	 * @psalm-taint-escape url_path
	 * @psalm-taint-escape url_scheme
	 * @psalm-taint-specialize
	 */
	public static function safeChars(string $value, string $pattern = '/[^\p{L}\p{N}\/._~\-?&=%+]+/u'): string
	{
		return preg_replace($pattern, '', $value) ?? '';
	}

	/**
	 * Относительный путь внутри проекта для file-операций.
	 *
	 * Отклоняет пустой путь, path traversal ('..'), абсолютные пути и схемы
	 * (://): строка обязана начинаться с буквы и состоять только из
	 * [A-Za-z0-9_\-./]. Прошедшая проверка строка — путь относительно корня
	 * без выхода наружу, поэтому file/ssrf-тaint снимается честно.
	 *
	 * @param string $path Путь из запроса или конфигурации
	 * @return string|null Путь или null, если проверка не пройдена
	 *
	 * @psalm-taint-escape file
	 * @psalm-taint-escape ssrf
	 * @psalm-taint-escape url_component
	 * @psalm-taint-escape url_path
	 * @psalm-taint-escape url_scheme
	 */
	public static function safeRelPath(string $path): ?string
	{
		$path = str_replace('\\', '/', $path);
		if ($path === '' || str_contains($path, '..') || preg_match('#^[A-Za-z0-9][A-Za-z0-9_\-./]*$#D', $path) !== 1) {
			return null;
		}
		return $path;
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
        if (strlen($num) === 11 && substr($num, 0, 2) === '79') {
            #РФ
            $part1 = substr($num, 0, 4);
            $part2 = substr($num, 4, 3);
            $part3 = substr($num, 7, 2);
            $part4 = substr($num, 9, 2);
            return [
				'num' => (int) $num,
				'view' => "+" . $part1 . " " . $part2 . "-" . $part3 . "-" . $part4
			];
        }
		if (strlen($num) === 12 && substr($num, 0, 3) === '375') {
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
	 * @param array $array Входной массив
	 * @param string $index Поле, которое будет использоваться в качестве ключа
	 * @param string $output Поле, которое будет использоваться в качестве значения (опционально)
	 * @return array Массив с ключами, установленными по указанному полю
	 */
	public static function array(array $array, string $index = 'Id', string $output = ''): array
	{
		$arr = [];

		if ($output === '') {
			foreach ($array as $v) {
				$arr[$v[$index]] = $v;
			}
		} else {
			foreach ($array as $v) {
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
	 * Генерация GUID (UUID).
	 *
	 * Если параметр `$string` пустой, возвращает случайный UUID версии 4.
	 * Если передана строка, возвращает детерминированный UUID версии 3 или 5
	 * на основе хэша от этой строки.
	 *
	 * @param string $string Входная строка для детерминированного GUID (опционально)
	 * @param int $version Версия UUID для генерации, 3 или 5 при ненулевой строке.
	 *                     Для пустой строки всегда используется версия 4.
	 * @return string Сгенерированный UUID в формате 8-4-4-4-12.
	 */
	public static function guid(string $string = '', int $version = 4): string
	{
		if ($string === '') {
			// v4: Случайный UUID
			$data = random_bytes(16);
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Версия 4
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Вариант RFC 4122
		} else {
			if ($version === 3) {
				// v3: На основе MD5 (быстрее, но менее безопасен)
				$data = md5($string, true);
				$data[6] = chr(ord($data[6]) & 0x0f | 0x30); // Версия 3
			} else {
				// v5: На основе SHA-1 (медленнее, но безопаснее)
				$data = sha1($string, true);
				$data[6] = chr(ord($data[6]) & 0x0f | 0x50); // Версия 5
			}
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Вариант RFC 4122
		}

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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
		return (float) number_format($number, $scale, ".", "");
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
	 * @return string|null
	 *
	 * @psalm-taint-specialize
	 */
	public static function cookies(string $name, ?string $value = null, int $lifetime = 86400): string|null
	{
		// Если значение не передано — возвращаем текущее значение куки
		if ($value === null) {
			return $_COOKIE[$name] ?? null;
		}
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
		// Значения наших куки — собственные данные пользователя (корзина, флаги
		// согласий, преференции): пользовательское значение в собственную куку —
		// штатное поведение. Сессионные куки формирует PHP (session.*), сюда не попадают.
		/** @psalm-suppress TaintedInput */
		setcookie($name, $value, $settings);
		return $value;
	}
	/**
	 * Кодирование массива в JSON-строку
	 *
	 * JSON_HEX_TAG/JSON_HEX_AMP экранируют < и & как \u003C/\u0026 — psalm не
	 * проводит html-тaint через эти флаги, а парсер JSON вернёт символы
	 * исходно (данные не искажаются). has_quotes закрыт аннотацией: JSON —
	 * структура данных, а не HTML-разметка.
	 *
	 * @param array $data Массив данных для кодирования
	 * @return string JSON-строка с поддержкой Unicode и слешей
	 *
	 * @psalm-taint-escape has_quotes
	 */
	public static function json(array $data): string
	{
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
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
			if (substr($name, 0, 5) === 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}
