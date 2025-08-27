<?php
namespace WeppsExtensions\Addons\Docs\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel
{
	private $get;
	private $filename = 'excel.xlsx';
	public function __construct($get)
	{
		$this->get = $get;
	}
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
	public function read(string $filename)
	{

	}
}