<?php

namespace WeppsAdmin\Updates;

use WeppsCore\Utils\UtilsWepps;

class UpdatesWepps {
	public $parent = 1;
	public $settings;
	public function __construct($settings=[]) {
		$this->settings = $settings;
		if ($this->parent==0) {
			return;
		}
		$output = "";
		switch ($this->settings[1]) {
			case 'version':
				$obj = new UpdatesMethodsWepps();
				$output = $obj->getReleaseCurrentVersion();
				break;
			case 'modified':
				$obj = new UpdatesMethodsWepps();
				$output = $obj->getReleaseCurrentModified()['output'];
				break;
			case 'list':
				$obj = new UpdatesMethodsWepps();
				$output = $obj->getReleasesList()['output'];
				break;
			case 'update':
				/*
				 * Предусмотреть
				 * -f - для обновления измененных файлов
				 */
				$obj = new UpdatesMethodsWepps();
				if (empty($this->settings[2])) {
					$output = "release tag is empty, see list";
					break;
				}
				$output = $obj->setUpdates($this->settings[2])['output'];
				break;
			
			case 'test':
				$obj = new UpdatesMethodsWepps();
				$obj->getCliProgress(10, 100,"копирование 1");
				sleep(2);
				$obj->getCliProgress(20, 100,"копирование 2");
				sleep(2);
				$obj->getCliProgress(30, 100,"копирование 3");
				
				#$output = "Test";
				break;
			default:
				echo 2;
				break;
		}
		echo "\n===============\n{$output}\n===============";
		return true;
	}
	
}
?>