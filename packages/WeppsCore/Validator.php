<?php
namespace WeppsCore;

/**
 * Класс Validator предоставляет методы для валидации различных типов данных.
 *
 * Этот класс содержит статические методы для проверки переменных на соответствие
 * различным критериям, таким как непустота, целочисленность, наличие только латинских
 * символов, корректность URL, email, даты и времени. Также предоставляет методы для
 * отображения ошибок и успешных сообщений в формах.
 *
 * @package WeppsCore
 */
class Validator
{
	/**
	 * Проверка на непустую переменную
	 *
	 * @param mixed $variable Переменная для проверки. Может быть строкой или массивом.
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается, если переменная пустая.
	 * @return string Возвращает пустую строку, если переменная не пустая, в противном случае возвращает сообщение об ошибке.
	 *
	 * @example
	 * Пример использования:
	 * $result = Validator::isNotEmpty('test', 'Переменная пустая');
	 * $result будет пустой строкой, так как 'test' не пустая.
	 *
	 * $result = Validator::isNotEmpty(['', 'test'], 'Массив пустой');
	 * $result будет пустой строкой, так как хотя бы один элемент массива не пустой.
	 *
	 * $result = Validator::isNotEmpty('', 'Переменная пустая');
	 * $result будет 'Переменная пустая', так как переменная пустая.
	 */
	public static function isNotEmpty(mixed $variable, string $errorMessage)
	{
		$values = is_array($variable) ? $variable : [$variable];
		foreach ($values as $value) {
			if (!empty(trim($value))) {
				return '';
			}
		}
		return $errorMessage;
	}
	
	/**
	 * Проверяет, является ли переменная целым числом (строго тип int).
	 *
	 * @param mixed $variable Переменная для проверки.
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 *
	 * @return string
	 *   - Пустая строка ('') — если переменная является целым числом.
	 *   - $errorMessage — если переменная не является целым числом.
	 */
	public static function isInt(mixed $variable, string $errorMessage)
	{
		$tmp = '';
		return is_int($variable) ? $tmp : $errorMessage;
	}

	/**
	 * Проверяет, состоит ли строка только из латинских букв, цифр и допустимых специальных символов.
	 *
	 * @param string $variable Строка для валидации.
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если строка содержит недопустимые символы.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Регулярное выражение проверяет:
	 *     - Латинские буквы (a-zA-Z).
	 *     - Цифры (\d).
	 *     - Разрешённые специальные символы: подчёркивание (`_`), дефис (`-`), точка (`.`).
	 *   - **Ограничения**:
	 *     - Не разрешает пробелы или другие символы (например, `@`, `#`, `!`).
	 *     - Не проверяет длину строки.
	 *     - Не учитывает регистр (например, `"MyVar"` и `"myvar"` считаются допустимыми).
	 *   - Подходит для валидации идентификаторов, тегов, имен переменных и т.п.
	 */
	public static function isLat(string $variable, string $errorMessage)
	{
		$tmp = '';
		if (preg_match("/^[\w.-]+$/", $variable))
			return $tmp;
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли переданная строка корректным URL.
	 *
	 * @param string $variable Строка для валидации (ожидается URL).
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если URL не соответствует требованиям.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Валидация осуществляется с помощью filter_var() с FILTER_VALIDATE_URL.
	 *   - Поддерживает различные протоколы (http, https, ftp и т.д.).
	 *   - Соответствует RFC 3986.
	 */
	public static function isUrl(string $variable, string $errorMessage)
	{
		$tmp = '';
		if (filter_var($variable, FILTER_VALIDATE_URL) !== false)
			return $tmp;
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли значение целым числом или строкой, которую можно привести к int.
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isInt2(mixed $value, string $errorMessage): string
	{
		if (is_int($value)) {
			return '';
		}
		if (is_string($value)) {
			return filter_var($value, FILTER_VALIDATE_INT) !== false ? '' : $errorMessage;
		}
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли значение числом с плавающей точкой (строго тип float или int).
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isFloat(mixed $value, string $errorMessage): string
	{
		return (is_float($value) || is_int($value)) ? '' : $errorMessage;
	}

	/**
	 * Проверяет, является ли значение числом с плавающей точкой или строкой, которую можно привести к float.
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isFloat2(mixed $value, string $errorMessage): string
	{
		if (is_float($value) || is_int($value)) {
			return '';
		}
		if (is_string($value)) {
			return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? '' : $errorMessage;
		}
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли значение строкой.
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isString(mixed $value, string $errorMessage): string
	{
		return is_string($value) ? '' : $errorMessage;
	}

	/**
	 * Проверяет, является ли значение корректной датой (используя strtotime).
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isDate(mixed $value, string $errorMessage): string
	{
		return (is_string($value) && strtotime($value) !== false) ? '' : $errorMessage;
	}

	/**
	 * Проверяет, является ли значение корректным email-адресом (используя filter_var).
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isEmail(mixed $value, string $errorMessage): string
	{
		return (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ? '' : $errorMessage;
	}

	/**
	 * Проверяет, является ли значение корректным номером телефона (10 цифр после очистки).
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isPhone(mixed $value, string $errorMessage): string
	{
		if (!is_string($value)) {
			return $errorMessage;
		}
		$cleaned = preg_replace('/\D/', '', $value);
		return strlen($cleaned) === 10 ? '' : $errorMessage;
	}

	/**
	 * Проверяет, является ли значение корректным GUID.
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isGuid(mixed $value, string $errorMessage): string
	{
		if (!is_string($value)) {
			return $errorMessage;
		}
		return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1 ? '' : $errorMessage;
	}

	/**
	 * Проверяет, является ли значение корректным EAN13 штрих-кодом.
	 *
	 * @param mixed $value Значение для проверки.
	 * @param string $errorMessage Сообщение об ошибке.
	 * @return string Пустая строка при успехе, сообщение об ошибке при неудаче.
	 */
	public static function isBarcode(mixed $value, string $errorMessage): string
	{
		if (!is_string($value)) {
			return $errorMessage;
		}
		return self::isValidEAN13($value) ? '' : $errorMessage;
	}

	/**
	 * Проверка валидности EAN13 штрих-кода
	 *
	 * @param string $ean13 Код для проверки
	 * @return bool Результат проверки
	 */
	private static function isValidEAN13(string $ean13): bool
	{
		// Должно быть ровно 13 цифр
		if (!preg_match('/^\d{13}$/', $ean13)) {
			return false;
		}

		$sum = 0;
		for ($i = 0; $i < 12; $i++) {
			$digit = (int) $ean13[$i];
			$sum += $i % 2 === 0 ? $digit : $digit * 3;
		}

		$checkDigit = (10 - ($sum % 10)) % 10;
		return (int) $ean13[12] === $checkDigit;
	}

	/**
	 * Отображает ошибки формы с визуальными индикаторами
	 *
	 * @param array  $errors Массив ошибок (ключ - имя поля, значение - текст ошибки)
	 * @param string $form   ID элемента формы для отображения ошибок
	 * @return array         Массив с данными об ошибках и HTML-скриптом
	 */
	public static function setFormErrorsIndicate(array $errors = [], string $form): array
	{
		$str = "<script>\n";
		if (!empty(array_filter($errors))) {
			Utils::debug($errors,2);
			foreach ($errors as $key => $value) {
				if ($value != "") {
					$str .= "
					var elem = $('#{$form}').find('[name=\"{$key}\"]');
					if (elem.length==0) {
						var elem = $('#{$form}').find('[name=\"{$key}[]\"]');
					}
					if (elem.length!=0) {
						elem.closest('label').addClass('w_error_parent');
						var t = $('<div>{$value}</div>').addClass('w_error_{$key}').addClass('w_error');
						elem.eq(0).before(t);
						t.on('click',function(event) {
							$(this).closest('label').removeClass('w_error_parent');
							$(this).remove();
						});
					}\n";
				} else {
					$str .= "$('.w_error_{$key}').trigger('click');";
				}
			}
			$str .= "
			$([document.documentElement, document.body]).animate({
				scrollTop: $('#{$form}').find('.w_error_parent').offset().top - 300
			}, 1000);";
		}
		$str .= "
				$('.w_error_parent').children().on('focus',function() {
                    var attr = $(this).attr('name').replace('[]','');
					$('.w_error_'+attr).trigger('click');
				});
				$('.w_error_parent').children('input').on('change',function() {
                    var attr = $(this).attr('name').replace('[]','');
					$('.w_error_'+attr).trigger('click');
				});
		";
		$str .= "</script>";
		$errors = array_filter($errors, function ($value) {
			return !empty($value);
		});
		return [
			'errors' => $errors,
			'count' => count($errors),
			'html' => $str,
		];
	}

	/**
	 * Отображает сообщение успеха в указанной форме
	 *
	 * @param string $message Текст сообщения
	 * @param string $form    ID элемента формы для отображения сообщения
	 * @param string $js      Дополнительный JavaScript-код для выполнения
	 * @return array          Массив с HTML-контентом
	 */
	public static function setFormSuccess($message, $form, $js = ""): array
	{
		$str = "
			<script>
			$('#{$form}').html('{$message}');
			$('#{$form}').addClass('w_success');
			$('#{$form}').fadeIn();
			$js
			</script>
				";
		return ['html' => $str];
	}
}