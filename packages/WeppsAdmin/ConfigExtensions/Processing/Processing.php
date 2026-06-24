<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Connect;
use WeppsCore\Request;
use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;

class Processing extends Request
{
	public $way;
	public $title;
	public $headers;
	public function request($action = "")
	{
		$this->tpl = 'Processing.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
			'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/",
			'Name' => $this->title
		]);
		$this->headers = new TemplateHeaders();
		$smarty = Smarty::getSmarty();
		$smarty->assign('url', '/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php');
		if ($action == "") {
			return;
		}
		switch ($action) {
			case 'tasks':
				$this->title = "Задачи";
				$this->tpl = 'ProcessingTasks.tpl';
				break;
			case 'products':
				$this->title = "Товары";
				$this->tpl = 'ProcessingProducts.tpl';
				break;
			case 'restapi':
				$this->title = "REST API";
				$this->tpl = 'ProcessingRestApi.tpl';
				$this->prepareRestApiData();
				break;
			default:
				if ($action != "") {
					Exception::error404();
				}
				break;
		}
		array_push($this->way, [
			'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
			'Name' => $this->title
		]);
	}

	/**
	 * Подготавливает данные для шаблона REST API
	 */
	private function prepareRestApiData(): void
	{
		$processingRestApi = new ProcessingRestApi();

		// Получаем списки файлов
		$sourceFiles = $processingRestApi->scanBruFiles('get');
		$destinationFiles = $processingRestApi->scanBruFiles('not_get');

		// Передаём в шаблон
		$this->assign('sourceFiles', $sourceFiles);
		$this->assign('destinationFiles', $destinationFiles);
		$this->assign('projectDev',Connect::$projectDev);
	}
}