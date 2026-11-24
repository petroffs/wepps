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

namespace WeppsExtensions\Addons\Files;

use WeppsCore\Connect;
use WeppsCore\Exception;
use WeppsCore\TextTransforms;
use WeppsCore\Utils;
use WeppsCore\Validator;

if (!session_id()) {
	@session_start();
}

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
	public function output(string $filename): never
	{
		// Путь из запроса: только относительный без .. — защита от path traversal
		// (safeRelPath снимает file/ssrf-taint). Ведущий / сохраняем: по нему
		// сверяется FileUrl в БД и конкатенируется корень проекта.
		$rel = Utils::safeRelPath(ltrim($filename, '/')) ?? '';
		if ($rel === '') {
			Exception::error(404);
		}
		$filename = '/' . $rel;
		$filenameFull = Connect::$projectDev['root'] . $filename;
		if (!is_file($filenameFull)) {
			Exception::error(404);
		}
		$sql = "select * from s_Files where FileUrl=? limit 1";
		$res = Connect::$instance->fetch($sql, [$filename]);
		if (count($res) == 0)
			Exception::error404();
		$row = $res[0];
		$filetitle = $row['Name'];

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"" . Utils::safeChars($filetitle, '/[\x00-\x1F\x7F"\'\\\\;]+/u') . "\"");
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
	public function clear(): void
	{

	}

	/**
	 * Запись файлов в s_Files с физическим копировнием
	 */
	public function save($file): void
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
		$errors = [];
		// Проверяем, есть ли настройки валидации
		if (empty($this->uploadSettings)) {
			$errors[$field] = "Не настроены правила загрузки файлов";
			return [
				'message' => $errors[$field],
				'html' => ''
			];
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
			if (is_file($file['tmp_name'])) {
				if (!isset($_SESSION['uploads'][$form][$field])) {
					$_SESSION['uploads'][$form][$field] = [];
				}
				$filepath = pathinfo((string) $file['name']);
				// Имя из загрузки: транслит-имя + safeRelPath — путь не покидает папку uploads
				$base = Utils::safeRelPath(TextTransforms::translit($filepath['filename'], 2) . '_' . substr(Utils::guid(), 0, 8)) ?? '';
				$ext = Utils::safeRelPath((string) ($filepath['extension'] ?? '')) ?? '';
				if ($base === '') {
					$errors[] = "Некорректное имя файла '{$file['name']}'";
					continue;
				}
				$file['dest_name'] = Connect::$projectDev['root']."/packages/WeppsExtensions/Template/Forms/uploads/{$base}" . ($ext !== '' ? ".{$ext}" : '');
        		move_uploaded_file($file['tmp_name'], $file['dest_name']);
				$_SESSION['uploads'][$form][$field][] = $file;
			} else {
				$errors[] = "Ошибка сохранения файла '{$file['name']}'";
			}
		}
		if (!empty($errors)) {
			$outer = Validator::setFormErrorsIndicate($errors, $form);
			return [
				'message' => implode(', ', $errors),
				'html' => $outer['html']
			];
		}
		$js = "<script>
        $('.w_upload_add').children().remove();\n";
		$fieldJs = htmlspecialchars($field, ENT_QUOTES);
		foreach ($_SESSION['uploads'][$form][$field] as $key => $file) {
			$keyJs = (int) $key;
			$nameJs = htmlspecialchars($file['name'], ENT_QUOTES);
			$js .= "$('input[name=\"{$fieldJs}\"]').parent().siblings('div.w_upload_add').append($('<div class=\"w_upload_file\" data-key=\"{$keyJs}\">{$nameJs} <i class=\"bi bi-x-circle-fill\"></i></div>'));\n";
		}
		$js .= "$('label.{$fieldJs}').siblings('.w_error').trigger('click');formsInit();</script>";
		return [
			'message' => 'Файлы загружены',
			'html' => $js];
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
	public function setUploadSettings($size = 0, $mime = ''): bool
	{
		$this->uploadSettings[] = [
			'size' => $size,
			'mime' => $mime
		];
		return true;
	}
	public function getUploaded(string $form,string $field): array
	{
		if (empty($_SESSION['uploads'][$form][$field])) {
			return [];
		}
		$files = array_column($_SESSION['uploads'][$form][$field], 'dest_name');
		return $files;
	}
	public function removeUploadedAll(): bool
	{
		if (isset($_SESSION['uploads'])) {
			unset($_SESSION['uploads']);
		}
		return true;
	}
	public function removeUploaded(string $form, string $field, string $index = ''): array
	{
		if ($index==='' && !empty($_SESSION['uploads'][$form][$field])) {
			foreach ($_SESSION['uploads'][$form][$field] as $file) {
				// dest_name собран из санитизированного имени при записи; запрет .. как второй барьер
				if (!str_contains((string) ($file['dest_name'] ?? ''), '..') && is_file($file['dest_name'])) {
					/** @psalm-suppress TaintedInput */
					unlink($file['dest_name']);
				}
			}
			unset($_SESSION['uploads'][$form][$field]);
			return [
				'message' => "Файлы удалены",
				'html' => ''
			];
		}
		if (empty($_SESSION['uploads'][$form][$field][$index])) {
			return [
				'message' => "Файл не найден",
				'html' => ''
			];
		}
		if (!str_contains((string) ($_SESSION['uploads'][$form][$field][$index]['dest_name'] ?? ''), '..')
			&& is_file($_SESSION['uploads'][$form][$field][$index]['dest_name'])) {
			/** @psalm-suppress TaintedInput */
			unlink($_SESSION['uploads'][$form][$field][$index]['dest_name']);
		}
		unset($_SESSION['uploads'][$form][$field][$index]);
		$formJs = htmlspecialchars($form, ENT_QUOTES);
		$fieldJs = htmlspecialchars($field, ENT_QUOTES);
		$indexJs = htmlspecialchars($index, ENT_QUOTES);
		return [
			'message' => "Файл удален",
			'html' => "<script>$('#{$formJs}').find('input[name=\"{$fieldJs}\"]').parent().siblings('.w_upload_add').children('[data-key=\"{$indexJs}\"]').remove();</script>"
		];
	}
}