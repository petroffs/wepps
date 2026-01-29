<?php
namespace WeppsExtensions\Addons\Docs\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Класс для работы с Excel-файлами (чтение и создание)
 * 
 * Использует библиотеку PhpOffice\PhpSpreadsheet для работы с XLSX файлами.
 * Поддерживает создание файлов с форматированием и чтение данных из существующих файлов.
 */
class Excel
{
	private $get;
	private $filename = 'excel.xlsx';
	
	/**
	 * Конструктор класса
	 * 
	 * @param mixed $get Параметры запроса
	 */
	public function __construct($get)
	{
		$this->get = $get;
	}
	
	/**
	 * Создание Excel-файла из массива данных
	 * 
	 * Первая строка массива используется как заголовки таблицы.
	 * Заголовки форматируются жирным шрифтом с цветным фоном.
	 * 
	 * @param array $data Массив данных для записи. Первый элемент - заголовки
	 * @param string $filename Путь для сохранения файла. Если пустой - возвращает содержимое
	 * @return string|null Бинарное содержимое файла или null при сохранении в файл
	 */
	public function create(array $data, string $filename = '')
	{
		$spreadsheet = new Spreadsheet();
		$spreadsheet->getProperties()->setCreator('Wepps')
			->setLastModifiedBy('Wepps')
			->setTitle('Office 2007 XLSX Document')
			->setSubject('Office 2007 XLSX Document')
			->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
			->setKeywords('office 2007 openxml php')
			->setCategory('Wepps List result file');
		$i = 1;
		$j = 1;
		$fields = $data[0];
		foreach ($fields as $key => $value) {
			$str = trim($key);
			$spreadsheet->setActiveSheetIndex(0)
				->setCellValueExplicit([$j, $i], $str, 's')
				->getColumnDimensionByColumn($j)->setWidth(12);
			$j++;
		}

		$i++;
		foreach ($data as $v) {
			$j = 1;
			foreach ($fields as $key => $value) {
				$str = trim($v[$key]);
				$spreadsheet->setActiveSheetIndex(0)
					->setCellValueExplicit([$j, $i], $str, 's');
				$j++;
			}
			$i++;
		}

		$spreadsheet->getActiveSheet()
			->getStyle('A1:AZ1')
			->getFont()->setBold(2)
			->getColor()
			->setARGB('0080C0');

		$spreadsheet->getActiveSheet()
			->getStyle('A1:AZ1')
			->getFill()
			->setFillType('solid')->getStartColor()->setARGB('f1f1f1');

		$spreadsheet->getActiveSheet()->setTitle("Data");
		$spreadsheet->setActiveSheetIndex(0);
		$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
		if (!empty($filename)) {
			/*
			 * Записать в файл для проверки
			 */
			$writer->save($filename);
			return;
		}
		ob_start();
		$writer->save('php://output');
		$content = ob_get_clean();
		return $content;
	}
	
	/**
	 * Чтение данных из Excel-файла
	 * 
	 * Считывает указанный лист из файла и возвращает массив с информацией о всех листах
	 * и данными из выбранного листа. Первая строка содержит заголовки столбцов.
	 * 
	 * @param string $filename Путь к Excel-файлу для чтения
	 * @param int $sheetIndex Индекс листа для чтения (0 - первый лист)
	 * @return array Массив с ключами:
	 *               - 'sheets': array Список названий всех листов
	 *               - 'data': array Двумерный массив данных из указанного листа
	 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception Если файл не найден или поврежден
	 */
	public function read(string $filename, int $sheetIndex = 0): array
	{
		if (!file_exists($filename)) {
			throw new \Exception("Файл не найден: {$filename}");
		}
		
		$spreadsheet = IOFactory::load($filename);
		
		// Получаем список всех листов
		$sheets = $spreadsheet->getSheetNames();
		
		// Проверяем существование указанного листа
		if ($sheetIndex < 0 || $sheetIndex >= count($sheets)) {
			throw new \Exception("Лист с индексом {$sheetIndex} не существует. Доступно листов: " . count($sheets));
		}
		
		// Читаем данные из указанного листа
		$worksheet = $spreadsheet->getSheet($sheetIndex);
		$data = $worksheet->toArray();
		
		return [
			'sheets' => $sheets,
			'data' => $data
		];
	}
}