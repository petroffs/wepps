<?php
namespace WeppsExtensions\Addons\Images;

use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsCore\Connect;

/**
 * Class Images
 *
 * Утилитарный класс для работы с изображениями на сервере — ресайз, кроп, штамп и вывод.
 *
 * Назначение:
 * - Загружает исходное изображение из файловой системы и метаданные из таблицы `s_Files`.
 * - Выполняет подготовку целевого изображения в зависимости от запрошенного префикса
 *   (lists, full, preview, medium, slide и т.д.).
 * - Поддерживает сохранение в файловую систему (`save`) и вывод в ответ (`output`).
 * - Позволяет добавлять штамп (логотип) методом `stamp`.
 *
 * Примечания по использованию:
 * - Конструктор принимает массив параметров (обычно из GET-запроса) с ключами
 *   `fileUrl` и `pref` (префикс типа вывода).
 * - При невозможности найти файл или запись в БД выбрасывает ошибку 404 через `Exception::error(404)`.
 *
 * @package WeppsExtensions\Addons\Images
 */
class Images
{
	private $source;
	private $target;
	private $newfile;
	private $fileinfo;
	private $mime;
	private $outputMime;
	private $ratio = 0.00;
	private $width = 0;
	private $height = 0;
	private $widthDst = 0;
	private $heightDst = 0;
	public $get;
	/**
	 * Images constructor.
	 *
	 * Инициализирует объект, читает метаданные файла из `s_Files`, определяет
	 * требуемые размеры и подготавливает ресурс изображения для дальнейших
	 * манипуляций (resize / crop / stamp).
	 *
	 * Ожидаемые ключи в `$get`:
	 * - `fileUrl` : string Путь к файлу относительно корня `packages`.
	 * - `pref` : string Префикс вывода (lists, full, preview, medium и т.д.).
	 *
	 * @param array $get Массив параметров (необработанный $_GET-подобный массив).
	 * @throws \WeppsCore\Exception При отсутствии файла или записи в БД (генерирует 404).
	 */
	function __construct($get)
	{
		$this->get = Utils::trim($get);
		$filename = (isset($this->get['fileUrl'])) ? ltrim(str_replace('\\', '/', $this->get['fileUrl']), '/') : '';
		$action = (isset($this->get['pref'])) ? $this->get['pref'] : '';
		$root = dirname(__DIR__, 4);
		$rootfilename = $root . DIRECTORY_SEPARATOR . $filename;
		if (!is_file($rootfilename)) {
			Exception::error(404);
		}
		$res = Connect::$instance->fetch("select * from s_Files where FileType like 'image/%' and FileUrl=?", ['/' . $filename]);
		if (count($res) == 0) {
			Exception::error(404);
		}
		$this->fileinfo = $res[0];
		$size = @getimagesize($rootfilename);
		if ($size === false || empty($size[0]) || empty($size[1]) || empty($size['mime'])) {
			Exception::error(404);
		}
		$this->mime = $size['mime'];
		$this->outputMime = $this->resolveOutputMime($this->mime);
		$this->ratio = $size[0] / $size[1];

		/*
		 * Проверка на наличие установочных параметров для ресайза/кропа
		 */
		$crop = 1;
		$side = 0;
		$this->widthDst = 0;
		$this->heightDst = 0;
		switch ($action) {
			case "lists":
				$this->widthDst = 80;
				$this->heightDst = 80;
				$crop = 0;
				break;
			case "full":
				$side = 1280;
				break;
			case "preview":
				$this->widthDst = 320;
				$this->heightDst = 240;
				break;
			case "medium":
				$this->widthDst = 640;
				$this->heightDst = 480;
				break;
			case "mediumv":
				$this->widthDst = 480;
				$this->heightDst = 600;
				break;
			case "mediumsq":
				$this->widthDst = 600;
				$this->heightDst = 600;
				$crop = 0;
				break;
			case "slide":
				$side = 1280;
				break;
			case "slidem":
				$side = 600;
				break;
			case "a4":
				$this->widthDst = 500;
				$this->heightDst = 705;
				break;
			default:
				Exception::error(404);
				exit();
		}
		/*
		 * Директория и файл для записи в файловую систему
		 */
		$newfile = $root . "/pic/" . $action . "/" . $filename;
		$newdir = dirname($newfile);
		if (!is_dir($newdir)) {
			@mkdir($newdir, 0750, true);
		}
		$this->newfile = $newfile;
		$ratio = ($this->widthDst > 0 && $this->heightDst > 0) ? $this->widthDst / $this->heightDst : $this->ratio;

		/*
		 * Подготовка данных для манипуляций
		 */

		if ($side > 0) {
			$side = (is_numeric($side) && $side > 0) ? $side : 100;
			$side = ($side > 1920) ? 1920 : $side;
			$this->widthDst = ($this->ratio >= 1) ? $side : $side * $this->ratio;
			$this->heightDst = ($this->ratio >= 1) ? $side / $this->ratio : $side;
		}

		$source = $this->createSourceImage($rootfilename);

		if ($this->heightDst > 0) {
			if ($size[0] < $this->widthDst && $size[1] < $this->heightDst) {
				$this->width = $size[0];
				$this->height = $size[1];
			} elseif ($crop == 0) {
				$this->resize($this->widthDst, $this->heightDst, $ratio);
			} else {
				$this->crop($this->widthDst, $this->heightDst, $ratio);
			}
			$this->width = round($this->width);
			$this->height = round($this->height);
			$target = imagecreatetruecolor($this->width, $this->height);
			$target = $this->imagefill($target);
			$target2 = imagecreatetruecolor($this->widthDst, $this->heightDst);
			$target2 = $this->imagefill($target2);
			$newX = round(($this->widthDst - $this->width) / 2);
			$newY = round(($this->heightDst - $this->height) / 2);
			imagecopyresampled($target, $source, 0, 0, 0, 0, $this->width, $this->height, $size[0], $size[1]);
			imagecopy($target2, $target, $newX, $newY, 0, 0, $this->width, $this->height);
			$this->target = $target2;
			$this->source = $source;
			return;
		}

		/*
		 * Сохранение для вывода
		 */
		$target = imagecreatetruecolor($this->widthDst, $this->heightDst);
		$target = $this->imagefill($target);
		imagecopyresampled($target, $source, 0, 0, 0, 0, $this->widthDst, $this->widthDst / $this->ratio, $size[0], $size[1]);
		$this->target = $target;
		$this->source = $source;
		return;
	}
	/**
	 * Сохраняет подготовленное изображение в файловую систему по пути, определённому
	 * в конструкторе (`$this->newfile`). Формат сохраняемого файла зависит от MIME:
	 * - PNG — через `imagepng` с качеством 5
	 * - прочие (JPEG) — через `imagejpeg` с качеством 100
	 *
	 * @return void
	 */
	public function save()
	{
		$this->writeImage($this->target, $this->newfile);
	}
	/**
	 * Выводит подготовленное изображение в HTTP-ответ с корректными заголовками
	 * кэширования. Используется для отдачи изображения напрямую клиенту.
	 *
	 * @return void
	 */
	public function output()
	{
		header('Content-type: ' . $this->outputMime);
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Expires: " . gmdate("D, d M Y H:i:s", strtotime('+1 years')) . " GMT");
		header("Cache-Control: max-age=31536000, s-maxage=31536000, must-revalidate");
		$this->writeImage($this->target);
	}
	private function resolveOutputMime(string $mime): string
	{
		switch ($mime) {
			case 'image/png':
				return 'image/png';
			case 'image/gif':
				return 'image/gif';
			case 'image/webp':
				return function_exists('imagewebp') ? 'image/webp' : 'image/jpeg';
			case 'image/jpeg':
			default:
				return 'image/jpeg';
		}
	}
	private function createSourceImage(string $filename)
	{
		switch ($this->mime) {
			case 'image/gif':
				$source = @imagecreatefromgif($filename);
				break;
			case 'image/jpeg':
				$source = @imagecreatefromjpeg($filename);
				break;
			case 'image/png':
				$source = @imagecreatefrompng($filename);
				if ($source) {
					imagealphablending($source, false);
					imagesavealpha($source, true);
				}
				break;
			case 'image/webp':
				$source = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filename) : false;
				break;
			default:
				$contents = @file_get_contents($filename);
				$source = ($contents !== false) ? @imagecreatefromstring($contents) : false;
				break;
		}

		if (!$source) {
			Exception::error(404);
		}

		return $source;
	}
	private function writeImage($image, ?string $filename = null): void
	{
		switch ($this->outputMime) {
			case 'image/png':
				imagepng($image, $filename, 5);
				break;
			case 'image/gif':
				imagegif($image, $filename);
				break;
			case 'image/webp':
				imagewebp($image, $filename, 90);
				break;
			case 'image/jpeg':
			default:
				imagejpeg($image, $filename, 100);
				break;
		}
	}
	/**
	 * Рассчитывает целевую ширину и высоту для ресайза, сохраняя соотношение сторон.
	 * Метод не выполняет непосредственного создания изображения — только вычисляет
	 * значения `$this->width` и `$this->height` на основе входных параметров.
	 *
	 * @param float $width Желаемая ширина
	 * @param float $height Желаемая высота
	 * @param float $ratio Целевое соотношение сторон (width/height)
	 * @return void
	 */
	private function resize(float $width, float $height, float $ratio)
	{
		// Сравниваем исходное соотношение сторон с целевым и выбираем ресайз по меньшей стороне
		if ($this->ratio > $ratio) {
			// Исходное шире целевого — масштабируем по высоте
			$this->height = $height;
			$this->width = $height * $this->ratio;
		} else {
			// Исходное уже целевого — масштабируем по ширине
			$this->width = $width;
			$this->height = $width / $this->ratio;
		}
		
		// Проверяем, влезают ли результирующие размеры в целевой прямоугольник
		// и пересчитываем, если какой-то размер выходит за границы
		if ($this->width > $width) {
			$scale = $width / $this->width;
			$this->width = $width;
			$this->height *= $scale;
		}
		if ($this->height > $height) {
			$scale = $height / $this->height;
			$this->height = $height;
			$this->width *= $scale;
		}
	}
	/**
	 * Вычисляет размеры для кропа (обрезки) так, чтобы результирующее изображение
	 * заполнило заданный прямоугольник `$width x $height` без искажений.
	 * Результат записывается в `$this->width` и `$this->height`.
	 *
	 * @param float $width Ширина области кропа
	 * @param float $height Высота области кропа
	 * @param float $ratio Целевое соотношение сторон
	 * @return void
	 */
	private function crop(float $width, float $height, float $ratio)
	{
		$srcRatio = $this->ratio;
		$dstRatio = $ratio;

		if ($srcRatio > $dstRatio) {
			$this->height = $height;
			$this->width = $height * $srcRatio;
		} else {
			$this->width = $width;
			$this->height = $width / $srcRatio;
		}
	}
	/**
	 * Накладывает штамп (обычно логотип) на подготовленное изображение.
	 *
	 * @param string $x Горизонтальное положение: 'left'|'center'|'right'
	 * @param string $y Вертикальное положение: 'top'|'center'|'bottom'
	 * @param float $gap Отступ от края в долях (по ширине штампа)
	 * @param string $list Список таблиц/источников, для которых штамп НЕ нужен
	 * @param string $filename Путь к PNG-файлу штампа (по умолчанию берётся из конфига проекта)
	 * @return void
	 */
	public function stamp(string $x = 'right', string $y = 'bottom', float $gap = 0.1, string $list = '', string $filename = '')
	{
		$exit = 1;
		if (empty($list)) {
			$exit = 0;
		}
		if (!empty($list)) {
			if (strstr($list, $this->fileinfo['TableName'])) {
				$exit = 0;
			}
		}
		if ($exit == 1) {
			return;
		}
		if (empty($filename)) {
			$filename = Connect::$projectDev['root'] . Connect::$projectInfo['logopng'];
		}
		$target = @imagecreatefrompng($filename);
		if (!$target) {
			Exception::error(500);
		}
		imagesavealpha($target, true);
		$size = @getimagesize($filename);
		if ($size === false || empty($size[0]) || empty($size[1])) {
			Exception::error(500);
		}
		$ratio = $size[0] / $size[1];

		/*
		 * Уменьшить штамп
		 */
		$width = $size[0];
		$height = $width / $ratio;
		$thumb = imagecreatetruecolor($width, $height);
		$background = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
		imagecolortransparent($thumb, $background);
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);

		// Рассчитать размеры штампа на основе меньшей размерности целевого изображения
		$minDimension = min($this->widthDst, $this->heightDst);
		$width = $minDimension * 0.15;
		$height = $width / $ratio;

		$width = round($width);
		$height = round($height);

		imagecopyresampled($thumb, $target, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
		$target = $thumb;
		$gap = $width * $gap;
		switch ($x) {
			case 'left':
				$posX = $gap;
				break;
			case 'center':
				$posX = $this->widthDst / 2 - $width / 2;
				break;
			case 'right':
			default:
				$posX = $this->widthDst - $width - $gap;
				break;
		}
		switch ($y) {
			case 'top':
				$posY = $gap;
				break;
			case 'center':
				$posY = $this->heightDst / 2 - $height / 2;
				break;
			case 'bottom':
			default:
				$posY = $this->heightDst - $height - $gap;
				break;
		}
		$posX = round($posX);
		$posY = round($posY);
		imagecopy($this->target, $target, $posX, $posY, 0, 0, $width, $height);
	}
	/**
	 * Заполняет целевой ресурс фоном, учитывая прозрачность для PNG и белый фон
	 * для остальных форматов.
	 *
	 * @param resource $target Ресурс изображения, созданный через `imagecreatetruecolor`
	 * @return resource Заполненный ресурс
	 */
	private function imagefill($target)
	{
		switch ($this->mime) {
			case "image/png":
				imagealphablending($target, false);
				imagesavealpha($target, true);
				$transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
				imagefill($target, 0, 0, $transparent);
				break;
			default:
				imagefill($target, 0, 0, 0xFFFFFF);
				break;
		}
		return $target;
	}
}