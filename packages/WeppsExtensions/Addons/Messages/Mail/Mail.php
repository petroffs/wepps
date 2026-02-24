<?php
namespace WeppsExtensions\Addons\Messages\Mail;

use WeppsCore\Connect;
use WeppsCore\Smarty;
use Curl\Curl;
use WeppsCore\Utils;

/**
 * Модуль отправки электронных писем для платформы Wepps.
 *
 * Поддерживает форматы HTML и plain text, кодирование quoted-printable,
 * файловые вложения (из файловой системы и из памяти), встраивание изображений
 * через CID (Content-ID) и режим отладки с переадресацией писем.
 *
 * @package WeppsExtensions\Addons\Messages\Mail
 */
class Mail
{
	/** @var array<string> Список путей к файлам-вложениям из файловой системы */
	private $attachment = [];

	/** @var array<array{title: string, content: string}> Вложения из памяти */
	private $attachmentInput = [];

	/** @var string Заголовок From в формате RFC 2047 (base64 + email) */
	private $from;

	/** @var string Тип письма: 'html' или 'plain' */
	private $type;

	/** @var mixed Зарезервировано для внешнего контента */
	private $outer;

	/** @var string Содержимое письма (после QP-кодирования для html-типа) */
	private $content;

	/** @var string Исходное содержимое письма до QP-кодирования */
	private $contentRaw;

	/** @var string Полное тело MIME-сообщения */
	private $contentAll;

	/** @var int|null Режим отладки: 1 — письма перенаправляются на адрес разработчика */
	private $debug;

	/** @var bool Встраивать ли изображения через CID вместо внешних ссылок */
	private $embedImages = false;

	/** @var string MIME-разделитель частей сообщения */
	private $mime_boundary;
	/**
	 * @param string $type Тип письма: 'html' для HTML-формата, любое другое значение — plain text
	 */
	public function __construct($type = 'plain')
	{
		$this->type = $type;
		$this->from = "=?utf-8?B?" . base64_encode(Connect::$projectInfo['name']) . "?=" . " <" . Connect::$projectInfo['email'] . ">";
		$this->mime_boundary = md5(time());
		if (Connect::$projectDev['debug'] == 1) {
			$this->debug = 1;
		}
	}
	/**
	 * Формирует и отправляет письмо.
	 *
	 * Рендерит Smarty-шаблон, применяет QP-кодирование (для HTML),
	 * добавляет вложения и CID-изображения (если включены).
	 *
	 * @param string $to      Email-адрес получателя
	 * @param string $subject Тема письма
	 * @param string $text    Текст или HTML-контент для подстановки в шаблон
	 * @return bool           Результат вызова функции mail()
	 */
	public function mail(string $to, string $subject, string $text)
	{
		$from = $this->from;
		$subj = "=?utf-8?B?" . base64_encode($subject) . "?=";
		$headers = "";
		$headers .= "From: $from\n";
		$headers .= "Reply-to: $from\n";
		$headers .= "X-Mailer: PHP v" . phpversion() . "\n";
		$headers .= "MIME-Version: 1.0" . "\n";
		$headers .= "Content-Type: multipart/related; boundary=\"{$this->mime_boundary}\"" . "\n";
		$this->contentAll = "--" . $this->mime_boundary . "\n";

		$smarty = Smarty::getSmarty();
		$settings = Connect::$projectInfo;
		$settings['host'] = [
			'title' => Connect::$projectDev['host'],
			'url' => Connect::$projectDev['protocol'] . Connect::$projectDev['host']
		];
		$smarty->assign('settings', $settings);
		switch ($this->type) {
			case "html":
				$smarty->assign('subject', $subject);
				$smarty->assign('text', $text);
				$this->contentRaw = $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsExtensions/Addons/Messages/Mail/MailHtml.tpl');
				$this->content = $this->contentRaw;
				$imageParts = $this->embedImages ? $this->getImagesHtml() : '';
				$this->getQuotedPrintable();
				$this->contentAll .= "Content-Type: text/html; charset=\"utf-8\"\n";
				$this->contentAll .= "Content-Transfer-Encoding: quoted-printable\n\n";
				$this->contentAll .= (string) $this->content . "\r\n\r\n";
				$this->contentAll .= $imageParts;
				break;
			default:
				$smarty->assign('text', $text);
				$this->contentRaw = $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsExtensions/Addons/Messages/Mail/MailPlain.tpl');
				$this->content = $this->contentRaw;
				$this->contentAll .= "Content-Type: text/plain; charset=\"utf-8\"\n";
				$this->contentAll .= "Content-Transfer-Encoding: quoted-printable\n\n";
				$this->contentAll .= (string) $this->content . "\n\n";
				break;
		}
		$this->contentAll .= self::getAttach();
		$this->contentAll .= self::getAttachInput();
		$this->contentAll .= "--{$this->mime_boundary}--\n";
		if ($this->debug == 1) {
			$to = Connect::$projectDev['email'];
		}
		return mail($to, $subj, $this->contentAll, $headers, "-f" . Connect::$projectInfo['email']);
	}
	/**
	 * Переопределяет отправителя письма.
	 *
	 * По умолчанию используется название и email проекта из конфигурации.
	 *
	 * @param string $name  Отображаемое имя отправителя
	 * @param string $email Email отправителя
	 * @return void
	 */
	public function setSender($name, $email)
	{
		$this->from = "=?utf-8?B?" . base64_encode($name) . "?=" . " <" . $email . ">";
	}
	/**
	 * Включает или отключает встраивание изображений через CID.
	 *
	 * При включении изображения из img src скачиваются и вкладываются
	 * в письмо как MIME-части с заменой src на cid:-ссылки.
	 * По умолчанию отключено — изображения загружаются по внешним URL.
	 *
	 * @param bool $embed true — встраивать, false — использовать внешние ссылки
	 * @return void
	 */
	public function setEmbedImages(bool $embed = true)
	{
		$this->embedImages = $embed;
	}
	/**
	 * Устанавливает список файловых вложений из файловой системы.
	 *
	 * @param array<string> $attachment Массив абсолютных путей к файлам
	 * @return void
	 */
	public function setAttach(array $attachment = [])
	{
		$this->attachment = $attachment;
	}
	/**
	 * Устанавливает список вложений из памяти (без файловой системы).
	 *
	 * @param array<array{title: string, content: string}> $attachment
	 *        Массив вложений, каждый элемент: ['title' => имя файла, 'content' => бинарное содержимое]
	 * @return void
	 */
	public function setAttachInput(array $attachment = [])
	{
		$this->attachmentInput = $attachment;
	}
	/**
	 * Включает режим отладки, если debug=1 в конфигурации проекта.
	 *
	 * В режиме отладки все письма перенаправляются на email разработчика.
	 *
	 * @return int|void
	 */
	public function setDebug()
	{
		if (Connect::$projectDev['debug'] == 1) {
			return $this->debug = 1;
		}
	}
	/**
	 * Отключает режим отладки, если debug=1 в конфигурации проекта.
	 *
	 * @return int|void
	 */
	public function unsetDebug()
	{
		if (Connect::$projectDev['debug'] == 1) {
			return $this->debug = 0;
		}
	}
	/**
	 * Возвращает содержимое письма после последнего вызова mail().
	 *
	 * @param bool $contentAll false — только тело (content), true — полное MIME-сообщение (contentAll)
	 * @return string
	 */
	public function getContent(bool $contentAll = false)
	{
		if ($contentAll == true) {
			return $this->contentAll;
		}
		return $this->content;
	}
	/**
	 * Сохраняет исходный HTML-контент письма в файл (для отладки).
	 *
	 * Использует contentRaw (до QP-кодирования), чтобы файл был читаемым.
	 *
	 * @param string $filename Путь к файлу. По умолчанию — files/mail.html в директории класса
	 * @return void
	 */
	public function save($filename = '')
	{
		$filename = ($filename != '') ? $filename : __DIR__ . '/files/mail.html';
		$output = file_get_contents($filename);
		$output .= ($this->contentRaw ?? $this->content) . "\n";
		file_put_contents($filename, $output);
	}
	/**
	 * Формирует MIME-части для файловых вложений из файловой системы.
	 *
	 * @return string MIME-части вложений или пустая строка, если вложений нет
	 */
	private function getAttach()
	{
		$msg = "";
		if (count($this->attachment) == 0) {
			return $msg;
		}
		foreach ($this->attachment as $value) {
			if (!is_file($value)) {
				//Utils::debug($value, 1);
				//return '';
			} else {
				$f_name = $value;
				$handle = fopen($f_name, 'rb');
				$f_contents = fread($handle, filesize($f_name));
				$f_contents = chunk_split(base64_encode($f_contents));
				fclose($handle);
				$f_info = pathinfo($f_name);
				$msg .= "--{$this->mime_boundary}\n";
				$msg .= "Content-Type: application/octet-stream; name=\"{$f_info['basename']}\"\n";
				$msg .= "Content-Transfer-Encoding: base64\n";
				$msg .= "Content-Disposition: attachment; filename=\"{$f_info['basename']}\"\n\n";
				$msg .= "{$f_contents}\n\n";
			}
		}
		return $msg;
	}
	/**
	 * Формирует MIME-части для вложений из памяти.
	 *
	 * @return string MIME-части вложений или пустая строка, если вложений нет
	 */
	private function getAttachInput()
	{
		$msg = "";
		if (!empty($this->attachmentInput)) {
			foreach ($this->attachmentInput as $value) {
				$f_contents = chunk_split(base64_encode($value['content']));
				$msg .= "--{$this->mime_boundary}\n";
				$msg .= "Content-Type: 	application/octet-stream; name=\"{$value['title']}\"\n";
				$msg .= "Content-Transfer-Encoding: base64\n";
				$msg .= "Content-Disposition: attachment; filename=\"{$value['title']}\"\n\n";
				$msg .= (string) $f_contents . "\n\n";
			}
		}
		return $msg;
	}
	/**
	 * Встраивает изображения из HTML-контента как CID-вложения (multipart/related).
	 *
	 * Находит все теги img src в $this->content, скачивает изображения по URL,
	 * заменяет src на cid:-ссылки и возвращает готовые MIME-части.
	 * Повторяющиеся URL обрабатываются один раз.
	 * Недоступные URL пропускаются без ошибок.
	 *
	 * @return string MIME-части изображений или пустая строка, если изображений нет
	 */
	private function getImagesHtml(): string
	{
		$matches = [];
		preg_match_all('/img\s+src="([^"]+)"/i', $this->content, $matches);
		$messfiles = "";
		if (empty($matches[1])) {
			return $messfiles;
		}
		$tmp = [];
		$arrContextOptions = [
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false
			]
		];
		foreach ($matches[1] as $key => $url) {
			if (isset($tmp[$url])) {
				continue;
			}
			$file = @file_get_contents($url, false, stream_context_create($arrContextOptions));
			if ($file === false) {
				continue;
			}
			$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
			$ext = ($ext === 'jpg') ? 'jpeg' : $ext;
			$basename = basename(parse_url($url, PHP_URL_PATH));
			$messfiles .= "--{$this->mime_boundary}\n";
			$messfiles .= "Content-Type: image/{$ext}; name=\"{$basename}\"\n";
			$messfiles .= "Content-Transfer-Encoding: base64\n";
			$messfiles .= "Content-ID: <img_{$key}>\n";
			$messfiles .= "Content-Disposition: inline; filename=\"{$basename}\"\n\n";
			$messfiles .= chunk_split(base64_encode($file), 64, "\r\n") . "\n";
			$this->content = str_replace($url, "cid:img_{$key}", $this->content);
			$tmp[$url] = 1;
		}
		return $messfiles;
	}
	/**
	 * Кодирует $this->content в формат quoted-printable.
	 *
	 * Должен вызываться после getImagesHtml(), чтобы src уже содержали cid:-ссылки.
	 *
	 * @return void
	 */
	private function getQuotedPrintable()
	{
		$this->content = quoted_printable_encode($this->content);
	}
}