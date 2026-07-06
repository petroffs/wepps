<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsAdmin\Lists\Lists;
use WeppsExtensions\Addons\Bot\BotSystem;

class SaveItemConfigFields extends Request
{
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];

	public function request($action = "")
	{
		$this->scheme = $this->get['listScheme'];
		$this->listSettings = $this->get['listSettings'];
		$this->element = $this->get['element'];
		if ($this->listSettings['TableName'] == 's_ConfigFields') {
			$str = Lists::addListField($this->element['Id'], $this->element['FType']);
			if ($str != "") {
				Connect::$instance->query($str);
				// ! Обновляем REST API mapping для новых полей (в addListField есть инвалидация кэша, требуется тестирование)
				// ! Поэтому текущий медод скрываем, но после тестов решим что делать
				// $obj = new BotSystem();
				// $obj->clearCache();
			}
		}
	}
}