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
		$tmp = '';
		if (is_array($variable)) {
			foreach ($variable as $value) {
				$value = trim($value);
				if (!empty($value))
					return $tmp;
			}
		} else {
			$variable = trim($variable);
			if (!empty($variable))
				return $tmp;
		}
		return $errorMessage;
	}
	
	/**
	 * Проверяет, является ли переменная целым числом.
	 *
	 * @param string|int $variable Переменная для проверки (строка или целое число).
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 *
	 * @return string
	 *   - Пустая строка ('') — если переменная является целым числом.
	 *   - $errorMessage — если переменная не является целым числом.
	 *
	 * @throws \TypeError Если $variable или $errorMessage не являются строками или целыми числами.
	 *
	 * @note
	 *   - Регулярное выражение проверяет, что строка содержит только цифры.
	 *   - Для целых чисел проверка выполняется напрямую.
	 *   - Не учитывает отрицательные числа или числа с десятичной точкой.
	 *   - Для более строгой валидации рекомендуется использовать `is_numeric()` или `filter_var()`.
	 */
	public static function isInt(string|int $variable, string $errorMessage)
	{
		$tmp = '';
		if (preg_match("/^[\d]+$/", $variable))
			return $tmp;
		return $errorMessage;
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
		if (preg_match("/^[\da-zA-Z\_\-\.]+$/", $variable))
			return $tmp;
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли переданная строка корректным URL с протоколом HTTPS.
	 *
	 * @param string $variable Строка для валидации (ожидается URL в формате `https://example.com/path`).
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если URL не соответствует требованиям.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Регулярное выражение проверяет:
	 *     - Обязательный префикс `https://`.
	 *     - Домен содержит буквы, цифры, точки, дефисы, подчеркивания.
	 *     - Расширение домена — 2–5 латинских букв.
	 *     - Поддержка дополнительных путей (`/path/to/resource`).
	 *   - **Ограничения**:
	 *     - Не проверяет существование домена или корректность DNS.
	 *     - Не учитывает специальные символы в URL (например, `?`, `#`, `&`).
	 *     - Не соответствует RFC 3986 полностью (например, игнорирует процентное кодирование).
	 *     - Не поддерживает другие протоколы (например, `http://`).
	 *   - Для более точной валидации рекомендуется использовать `filter_var($variable, FILTER_VALIDATE_URL)`.
	 */
	public static function isUrl(string $variable, string $errorMessage)
	{
		$tmp = '';
		if (preg_match("/^(https\:\/\/)[\da-zA-Z\.\-\_\/]+\.[a-zA-Z]{2,5}(\/.+)*$/", $variable))
			return $tmp;
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли переданная строка корректным email-адресом.
	 *
	 * @param string $variable Строка для валидации (ожидается email-адрес).
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если email не соответствует требованиям.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Валидация осуществляется через регулярное выражение, проверяющее базовый синтаксис email:
	 *     - Локальная часть (до символа `@`) содержит буквы, цифры, точки, подчеркивания, дефисы.
	 *     - Доменная часть содержит буквы, цифры, точки, дефисы.
	 *     - Домен должен заканчиваться расширением из 2–5 латинских букв.
	 *   - **Ограничения**:
	 *     - Регулярное выражение не покрывает все возможные форматы email (RFC 5322), например, не учитывает кавычки вокруг локальной части.
	 *     - Не проверяет существование домена или сервера.
	 *   - Для более строгой валидации рекомендуется использовать `filter_var($variable, FILTER_VALIDATE_EMAIL)`.
	 */
	public static function isEmail(string $variable, string $errorMessage)
	{
		$tmp = '';
		if (preg_match("/^[\da-zA-Z\.\_\-]+\@[\da-zA-Z\.\-]+\.[a-zA-Z]{2,5}$/", $variable))
			return $tmp;
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли переданная строка корректной датой в формате "YYYY-MM-DD".
	 *
	 * @param string $variable Строка для валидации (ожидается формат "YYYY-MM-DD").
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если дата не соответствует требованиям.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Валидация осуществляется через регулярное выражение и проверку диапазонов:
	 *     - Год: 4 цифры (например, 2023).
	 *     - Месяц: 1–12.
	 *     - День: 1–31.
	 *   - Разрешены как однозначные, так и двузначные значения для месяца и дня (например, "2023-1-5" или "2023-01-05").
	 *   ! **Ограничения**:
	 *     - Не проверяет високосные годы.
	 *     - Не учитывает количество дней в конкретном месяце (например, 31 февраля будет считаться допустимым).
	 *     - Не проверяет, что входные данные действительно числа (например, "2023-1a-30" может быть отсеян регуляркой, но не гарантируется).
	 *     - Для более строгой валидации рекомендуется использовать `DateTime::createFromFormat()`.
	 */
	public static function isDate(string $variable, string $errorMessage)
	{
		$tmp = '';
		if (preg_match("/^\d{4}-\d{1,2}-\d{1,2}$/", $variable)) {
			$exp = explode('-', $variable);
			if ($exp[1] > 12)
				return $errorMessage;
			if ($exp[2] > 31)
				return $errorMessage;
			return $tmp;
		}
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли переданная строка корректным временем в формате "HH:MM:SS".
	 *
	 * @param string $variable Строка для валидации (ожидается формат "HH:MM:SS").
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если время не соответствует требованиям.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Валидация осуществляется через регулярное выражение и проверку диапазонов:
	 *     - Часы: 0–24 (включительно, но в реальных сценариях допустимо только 0–23).
	 *     - Минуты: 0–59.
	 *     - Секунды: 0–59.
	 *   - Разрешены как однозначные, так и двузначные значения (например, "1:2:3" или "01:02:03").
	 *   - Регулярное выражение не учитывает ведущие нули, но это компенсируется логикой проверки чисел.
	 *   ! - Ошибка возникает, если часы превышают 24 (что может быть некорректно для стандартных временных форматов).
	 */
	public static function isTime(string $variable, string $errorMessage): string
	{
		$tmp = '';
		if (preg_match("/^\d{1,2}\:\d{1,2}\:\d{1,2}$/", $variable)) {
			$exp = explode(':', $variable);
			if ($exp[0] > 24)
				return $errorMessage;
			if ($exp[1] > 59)
				return $errorMessage;
			if ($exp[2] > 59)
				return $errorMessage;
			return $tmp;
		}
		return $errorMessage;
	}

	/**
	 * Проверяет, является ли переданная строка корректной датой и временем.
	 *
	 * @param string $variable Строка для валидации (ожидается формат "YYYY-MM-DD HH:MM:SS").
	 * @param string $errorMessage Сообщение об ошибке, которое возвращается при провале валидации.
	 * 
	 * @return string 
	 *   - Пустая строка ('') — если валидация прошла успешно.
	 *   - $errorMessage — если хотя бы одна часть (дата/время) не прошла валидацию.
	 * 
	 * @throws \TypeError Если $variable или $errorMessage не являются строками.
	 * 
	 * @note 
	 *   - Метод разделяет строку по первому пробелу на дату и время.
	 *   - Если строка содержит более одного пробела, только первая часть считается датой, остальное — временем.
	 *   - Результат зависит от реализации `isDate()` и `isTime()`, которые должны возвращать:
	 *     - пустую строку ('') при успешной валидации,
	 *     - сообщение об ошибке в противном случае.
	 */
	public static function isDateTime(string $variable, string $errorMessage)
	{
		$tmp = '';
		$exp = explode(' ', $variable);
		$tmp = self::isDate($exp[0], $errorMessage);
		if (isset($exp[1])) {
			$tmp .= self::isTime($exp[1], $errorMessage);
		}
		return ($tmp == '') ? '' : $errorMessage;
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
		if (!empty($errors)) {
			foreach ($errors as $key => $value) {
				if ($value != "") {
					$str .= "
					var elem = $('#{$form}').find('[name=\"{$key}\"]');
					if (elem.length==0) {
						var elem = $('#{$form}').find('[name=\"{$key}[]\"]');
					}
					if (elem.length!=0) {
						elem.closest('label').addClass('pps_error_parent');
						var t = $('<div>{$value}</div>').addClass('pps_error_{$key}').addClass('pps_error');
						elem.eq(0).before(t);
						t.on('click',function(event) {
							$(this).closest('label').removeClass('pps_error_parent');
							$(this).remove();
						});
					}\n";
				} else {
					$str .= "$('.pps_error_{$key}').trigger('click');";
				}
			}
			$str .= "
			$([document.documentElement, document.body]).animate({
				scrollTop: $('#{$form}').find('.pps_error_parent').eq(0).offset().top - 72
			}, 1000);";
		}
		$str .= "
				$('.pps_error_parent').children().on('focus',function() {
                    var attr = $(this).attr('name').replace('[]','');
					$('.pps_error_'+attr).trigger('click');
				});
				$('.pps_error_parent').children('input').on('change',function() {
                    var attr = $(this).attr('name').replace('[]','');
					$('.pps_error_'+attr).trigger('click');
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
			$('#{$form}').addClass('pps_success');
			$('#{$form}').fadeIn();
			$js
			</script>
				";
		return ['html' => $str];
	}
}