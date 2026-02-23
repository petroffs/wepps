<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Data;
use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Utils;

class SaveItemPanels extends Request
{
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];


	public function request($action = "")
	{
		$this->listSettings = $this->get['listSettings'];
		$this->element = $this->get['element'];
		if ($this->listSettings['TableName'] == 's_Panels') {
			$sql = "select count(*) Co from s_Blocks where PanelId = ?";
			$res = Connect::$instance->fetch($sql, [$this->element['Id']]);
			if ($res[0]['Co'] == 0) {
				$data = new Data("s_Blocks");
				$data->add([
					'Name' => 'Блок для панели ' . ((int) $this->element['Id']),
					'PanelId' => $this->element['Id'],
				]);
			}
			if (!empty($this->element['Template']) && $this->isStrictLatin($this->element['Template'], 'Error: Template name must be in strict Latin format') === '') {
				$source = Connect::$projectDev['root'] . '/packages/WeppsExtensions/Template/Blocks/Example';
				$target = Connect::$projectDev['root'] . '/packages/WeppsExtensions/Template/Blocks/' . $this->element['Template'];
				if (is_dir($source) && !is_dir($target)) {
					mkdir($target, 0775, true);
					foreach (scandir($source) as $file) {
						if ($file == '.' || $file == '..')
							continue;
						$content = file_get_contents($source . '/' . $file);
						$content = str_replace('-example', '-' . strtolower($this->element['Template']), $content);
						$content = str_replace('Example', $this->element['Template'], $content);
						$newFileName = str_replace('Example', $this->element['Template'], $file);
						file_put_contents($target . '/' . $newFileName, $content);
					}
				}
			}
		}
	}
	private function isStrictLatin(string $variable, string $errorMessage)
	{
		if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $variable)) {
			return '';
		}
		return $errorMessage;
	}
}