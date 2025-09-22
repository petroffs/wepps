<?php
namespace WeppsCore;

/**
 * Класс Cli предоставляет методы для работы с командной строкой, включая вывод сообщений с разными уровнями важности,
 * управление файлами и выполнение команд.
 */
class Cli
{
	/**
	 * Флаг, определяющий, выводить ли сообщения на экран.
	 *
	 * @var bool
	 */
	private $display = 0;

	/**
	 * Конструктор класса Cli.
	 * Инициализирует объект и устанавливает флаг отображения сообщений.
	 */
	public function __construct()
	{
		$this->display();
	}

	/**
	 * Устанавливает флаг отображения сообщений.
	 *
	 * @param bool $display Флаг отображения сообщений (по умолчанию true).
	 */
	public function display(bool $display = true)
	{
		$this->display = $display;
	}

	/**
	 * Выводит сообщение об ошибке.
	 *
	 * @param string $text Текст сообщения об ошибке (по умолчанию пустая строка).
	 * @return string Отформатированное сообщение об ошибке.
	 */
	public function error(string $text = '')
	{
		return self::outer(self::color("[error] $text", 'e'));
	}

	/**
	 * Выводит сообщение об успешном выполнении.
	 *
	 * @param string $text Текст сообщения об успешном выполнении (по умолчанию пустая строка).
	 * @return string Отформатированное сообщение об успешном выполнении.
	 */
	public function success(string $text = '')
	{
		return self::outer(self::color("[success] $text", 's'));
	}

	/**
	 * Выводит предупреждающее сообщение.
	 *
	 * @param string $text Текст предупреждающего сообщения (по умолчанию пустая строка).
	 * @return string Отформатированное предупреждающее сообщение.
	 */
	public function warning(string $text = '')
	{
		return self::outer(self::color("[warning] $text", 'w'));
	}

	/**
	 * Выводит информационное сообщение.
	 *
	 * @param string $text Текст информационного сообщения (по умолчанию пустая строка).
	 * @return string Отформатированное информационное сообщение.
	 */
	public function info(string $text = '')
	{
		return self::outer(self::color("[info] $text", 'i'));
	}

	/**
	 * Выводит текстовое сообщение.
	 *
	 * @param string $text Текст сообщения (по умолчанию пустая строка).
	 * @return string Отформатированное текстовое сообщение.
	 */
	public function text(string $text = '')
	{
		return self::outer(self::color($text));
	}

	/**
	 * Выводит символ новой строки.
	 *
	 * @return string Символ новой строки.
	 */
	public function br()
	{
		return self::outer("\n");
	}

	/**
	 * Выводит прогресс выполнения задачи.
	 *
	 * @param int $done Количество выполненных задач.
	 * @param int $total Общее количество задач.
	 */
	public function progress($done, $total)
	{
		$perc = floor(($done / $total) * 100);
		$left = 100 - $perc;
		$rate = 0.5;
		$perc2 = floor($perc * $rate);
		$left2 = ceil($left * $rate);
		$write = sprintf("\033[0G\033[2K[%'#{$perc2}s#%-{$left2}s] $done/$total [$perc%%]", "", "");
		echo $write;
	}

	/**
	 * Копирует файл из одного места в другое.
	 *
	 * @param string $source Путь к исходному файлу.
	 * @param string $destination Путь к файлу назначения.
	 * @param bool $overwrite Флаг перезаписи существующего файла (по умолчанию true).
	 * @return bool Возвращает true, если копирование прошло успешно, иначе false.
	 */
	public function copy(string $source, string $destination, bool $overwrite = true): bool
	{
		if ($overwrite === false && file_exists($destination)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!copy($source, $destination)) {
			return false;
		}
		return true;
	}

	/**
	 * Перемещает файл из одного места в другое.
	 *
	 * @param string $source Путь к исходному файлу.
	 * @param string $destination Путь к файлу назначения.
	 * @param bool $overwrite Флаг перезаписи существующего файла (по умолчанию true).
	 * @return bool Возвращает true, если перемещение прошло успешно, иначе false.
	 */
	public function move(string $source, string $destination, bool $overwrite = true)
	{
		if ($overwrite === false && file_exists($destination)) {
			return false;
		} elseif (!file_exists($source)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!rename($source, $destination)) {
			return false;
		}
		return true;
	}

	/**
	 * Записывает содержимое в файл.
	 *
	 * @param mixed $content Содержимое для записи.
	 * @param string $destination Путь к файлу назначения.
	 * @return bool Возвращает true, если запись прошла успешно, иначе false.
	 */
	public function put($content, $destination)
	{
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!file_put_contents($destination, $content)) {
			return false;
		}
		return true;
	}

	/**
	 * Создает директорию.
	 *
	 * @param string $dir Путь к директории.
	 * @return bool Возвращает true, если директория создана успешно, иначе false.
	 */
	public function mkdir(string $dir): bool
	{
		$dir = str_replace('\\', '/', $dir);
		if (!stristr($dir, Connect::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		return true;
	}

	/**
	 * Удаляет директорию.
	 *
	 * @param string $dir Путь к директории.
	 * @return bool Возвращает true, если директория удалена успешно, иначе false.
	 */
	public function rmdir(string $dir)
	{
		$dir = str_replace('\\', '/', $dir);
		if (!is_dir($dir)) {
			$this->warning('no dir');
			return false;
		} elseif (!stristr($dir, Connect::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		exec("rm $dir -rf");
		return true;
	}

	/**
	 * Удаляет файл.
	 *
	 * @param string $file Путь к файлу.
	 * @return bool Возвращает true, если файл удален успешно, иначе false.
	 */
	public function rmfile(string $file)
	{
		if (!file_exists($file)) {
			return false;
		}
		unlink($file);
		return true;
	}

	/**
	 * Выполняет команду в командной строке.
	 *
	 * @param string $cmd Команда для выполнения.
	 * @param bool $silent Флаг тихого режима (по умолчанию false).
	 * @return array Массив строк, содержащих вывод команды.
	 * @throws \Exception Если команда выполнена с ошибкой.
	 */
	public function cmd(string $cmd, bool $silent = false): array
	{
		if (empty($cmd)) {
			$this->warning("cmd is empty");
		}
		$o = [];
		$v = 100;
		exec("$cmd 2>&1", $o, $v);
		if ($v != 0) {
			if (!empty($o[0])) {
				$this->error($o[0]);
			} else {
				$this->error('cmd');
			}
			exit();
		}
		if ($silent == false && !empty($o)) {
			$this->info(implode("\n", $o));
		}
		return $o;
	}

	/**
	 * Форматирует строку с цветом в зависимости от типа.
	 *
	 * @param string $str Строка для форматирования.
	 * @param string $type Тип форматирования (e - ошибка, s - успех, w - предупреждение, i - информация).
	 * @return string Отформатированная строка.
	 */
	private function color(string $str = '', string $type = ''): string
	{
		$output = '';
		switch ($type) {
			case 'e': //error
				$output = "\033[0;31;47m$str\033[0m\n";
				break;
			case 's': //success
				$output = "\033[32m$str\033[0m\n";
				break;
			case 'w': //warning
				$output = "\033[33m$str\033[0m\n";
				break;
			case 'i': //info
				$output = "\033[36m$str\033[0m\n";
				break;
			default:
				$output = "$str\n";
				break;
		}
		return $output;
	}

	/**
	 * Выводит текст на экран, если флаг отображения установлен.
	 *
	 * @param string $text Текст для вывода.
	 * @return string Возвращает переданный текст.
	 */
	private function outer(string $text = ''): string
	{
		if ($this->display == true) {
			echo $text;
		}
		return $text;
	}
}