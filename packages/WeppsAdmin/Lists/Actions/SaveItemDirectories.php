<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Data;
use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\TextTransforms;

class SaveItemDirectories extends Request
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
		if ($this->listSettings['TableName'] == 's_Navigator') {
			if ($this->element['Url'] == '') {
				$url = "/" . TextTransforms::translit($this->element['Name'], 2) . "/";
				$sql = "update s_Navigator set Url='{$url}' where Id='{$this->element['Id']}'";
				Connect::$instance->query($sql);
				$this->element['Url'] = $url;
			}
			if ($this->element['IsBlocksActive'] == '1') {
				$sql = "select count(*) Co from s_Panels where NavigatorId = ?";
				$res = Connect::$instance->fetch($sql, [$this->element['Id']]);
				if ($res[0]['Co'] == 0) {
					$data = new Data("s_Panels");
					$panelId = $data->add([
						'Name' => 'Панель для директории ' . ((int) $this->element['Id']),
						'NavigatorId' => $this->element['Id'],
					]);
					$sql = "select count(*) Co from s_Blocks where PanelId = ?";
					$res = Connect::$instance->fetch($sql, [$panelId]);
					if ($res[0]['Co'] == 0) {
						$data = new Data("s_Blocks");
						$data->add([
							'Name' => 'Блок для панели ' . ((int) $panelId),
							'PanelId' => $panelId,
						]);
					}
				}
			}
		}
	}
}