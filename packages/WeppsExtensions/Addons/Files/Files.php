<?php
namespace WeppsExtensions\Addons\Files;

use WeppsCore\Connect;
use WeppsCore\Exception;
use WeppsCore\TextTransforms;
use WeppsCore\Utils;
use WeppsCore\Validator;

class Files
{
	private $uploadSettings = [];
	public function __construct()
	{
	}
	/**
	 * Вывод указанного файла в браузер на сохранение или открытие на стороне клиента
	 * @param string $file
	 */
	public function output(string $filename)
	{
		$filenameFull = Connect::$projectDev['root'] . $filename;
		if (!is_file($filenameFull)) {
			Exception::error(404);
		}
		$sql = "select * from s_Files where FileUrl='$filename' limit 1";
		$res = Connect::$instance->fetch($sql);
		if (count($res) == 0)
			Exception::error404();
		$row = $res[0];
		$filetitle = $row['Name'];

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"$filetitle\"");
		header("Content-Length: " . filesize($filenameFull));
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", mktime(0, 0, 0, 1, 1, 2000)) . " GMT"); // Дата в прошлом
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		readfile($filenameFull);
		exit();
	}

	/**
	 * Очистка файловой ситсемы от файлов, не указанных в s_Files
	 */
	public function clear()
	{

	}

	/**
	 * Запись файлов в s_Files с физическим копировнием
	 */
	public function save($file)
	{

	}

	/**
	 * Обрабатывает загрузку нескольких файлов с валидацией по настроенным правилам.
	 *
	 * Проверяет каждый файл на соответствие установленным ограничениям (размер и MIME-тип),
	 * сохраняет допустимые файлы и возвращает результат с ошибками или JS-обновлениями.
	 *
	 * @param array $files Массив загруженных файлов (как $_FILES)
	 * @param string $field Имя поля формы
	 * @param string $form Имя формы
	 * @return array Результат с ошибками или данными об успешной загрузке
	 */
	public function upload(array $files, string $field, string $form): array
	{
		if (!session_id()) {
			@session_start();
		}

		$root = Connect::$projectDev['root'];
		$errors = [];
		$uploadedFiles = [];
		// Проверяем, есть ли настройки валидации
		if (empty($this->uploadSettings)) {
			$errors[$field] = "Не настроены правила загрузки файлов";
			return ['error' => $errors[$field]];
		}
		foreach ($files as $file) {
			$valid = false;
			// Проверяем файл против всех правил
			foreach ($this->uploadSettings as $setting) {
				$isSizeValid = $file['size'] <= $setting['size'] || $setting['size'] === 0;
				$isMimeValid = empty($setting['mime']) || strpos($file['type'], $setting['mime']) === 0;
				if ($isSizeValid && $isMimeValid) {
					$valid = true;
					break;
				}
			}
			if (!$valid) {
				$errors[] = "Файл '{$file['name']}' не соответствует требованиям";
				continue;
			}
			// Формируем уникальное имя файла
			$filepathinfo = pathinfo($file['name']);
			$filename = strtolower(TextTransforms::translit($filepathinfo['filename'], 2));
			$filedest = "{$root}/packages/WeppsExtensions/Template/Forms/uploads/{$filename}-" . time() . ".{$filepathinfo['extension']}";
			// Сохраняем файл
			if (move_uploaded_file($file['tmp_name'], $filedest)) {
				$uploadedFiles[] = $file['name'];
				// Обновляем сессию
				if (!isset($_SESSION['uploads'][$form][$field])) {
					$_SESSION['uploads'][$form][$field] = [];
				}
				$file['filedest'] = $filedest;
				$_SESSION['uploads'][$form][$field][] = $file;
			} else {
				$errors[] = "Ошибка сохранения файла '{$file['name']}'";
			}
		}
		
		#$_SESSION['uploads'][$form][$field] = array_unique($_SESSION['uploads'][$form][$field]??[]);
		if (!empty($errors)) {
			$outer = Validator::setFormErrorsIndicate($errors, $form);
			return [
				'error' => implode(', ', $errors),
				'js' => $outer['html']
			];
		}
		#Utils::debug($_SESSION['uploads'][$form][$field]);
		// Генерация JS-ответа
		$js = "<script>
        $('.pps_upload_add').children().remove();\n";
		foreach ($_SESSION['uploads'][$form][$field] as $key => $file) {
			#$filename = addslashes(basename($filedest));
			$js .= "$('input[name=\"{$field}\"]').parent().siblings('div.pps_upload_add').append($('<div class=\"pps_upload_file\" data-key=\"{$key}\">{$file['name']} <i class=\"bi bi-x-circle-fill\"></i></div>'));\n";
		}
		$js .= "$('label.{$field}').siblings('.pps_error').trigger('click');formsInit();</script>";
		return ['success' => 'Файлы загружены', 'js' => $js];
	}


	/**
	 * Загрузка файлов из формы, расчитано что за один раз грузится 1 файл,
	 * в дальнешем проработать возможность мультизагрузки (или вызывать этот
	 * метод необходимое кол-во раз при таком случае.
	 * 
	 * @param array $files Массив с загруженными файлами ($_FILES)
	 * @param string $field Наименование html-элемента input[type="file"]
	 * @param string $form Идентификатор формы
	 * @return array
	 */
	public function upload2(array $files, string $field, string $form): array
	{
		if (!session_id()) {
			@session_start();
		}

		$root = Connect::$projectDev['root'];
		$errors = [];
		/*
		 * Не все изображения имеют эту метку, возможны ошибки
		 * Переработать таким образом, чтобы была входная информация
		 * о типе загруженного файла и в зависимости от этого делать
		 * валидацию
		 */
		if (!strstr($files[0]['type'], "image/")) {
			$errors[$field] = "Неверный тип файла";
			$outer = Validator::setFormErrorsIndicate($errors, $form);
			return ['error' => $errors[$field], 'js' => $outer['html']];
		}
		if ((int) $files[0]['size'] > 10000000) {
			#1 мегабайт = 1 000 000 байт
			$errors[$field] = "Слишком большой файл";
			$outer = Validator::setFormErrorsIndicate($errors, $form);
			return ['error' => $errors[$field], 'js' => $outer['html']];
		}
		$filepathinfo = pathinfo($files[0]['name']);
		$filepathinfo['filename'] = strtolower(TextTransforms::translit($filepathinfo['filename'], 2));
		$filedest = "{$root}/packages/WeppsExtensions/Addons/Forms/uploads/{$filepathinfo['filename']}-" . date("U") . ".{$filepathinfo['extension']}";
		move_uploaded_file($files[0]['tmp_name'], $filedest);
		if (!isset($_SESSION['uploads'][$form][$field])) {
			$_SESSION['uploads'][$form][$field] = [];
		}
		array_push($_SESSION['uploads'][$form][$field], $filedest);
		$_SESSION['uploads'][$form][$field] = array_unique($_SESSION['uploads'][$form][$field]);
		$js = "	<script>
		$('.fileadd').remove();
		$('input[name=\"{$field}\"]').parent().append($('<p class=\"pps_fileadd\">Загружен файл &laquo;{$files[0]['name']}&raquo;</p>'));
		$('label.{$field}').siblings('.pps_error').trigger('click');
		</script>";
		$data = ['success' => 'Files uploaded', 'js' => $js];
		return $data;
	}
	/**
	 * Устанавливает правила загрузки файлов (ограничения по размеру и MIME-типу).
	 *
	 * Добавляет новое правило в массив настроек, которое может использоваться для проверки
	 * соответствия загружаемых файлов заданным условиям.
	 *
	 * @param int $size Максимальный размер файла в байтах (например, 1024 * 1024 = 1 МБ)
	 * @param string $mime Разрешённый MIME-тип (например, "image/jpeg", "application/pdf")
	 * @return bool Всегда возвращает true для поддержки цепочек вызовов
	 */
	public function setUploadSettings($size = 0, $mime = '')
	{
		$this->uploadSettings[] = [
			'size' => $size,
			'mime' => $mime
		];
		return true;
	}

}