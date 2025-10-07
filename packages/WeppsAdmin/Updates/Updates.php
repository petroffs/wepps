<?php
namespace WeppsAdmin\Updates;

use WeppsCore\Utils;
use WeppsCore\Cli;

class Updates
{
	public $parent = 1;
	public $settings;
	public $cli;
	public function __construct($settings = [])
	{
		$this->settings = $settings;
		$this->cli = new Cli();
		if ($this->parent == 0) {
			return;
		}
		$output = "";
		switch ($this->settings[1]) {
			case 'version':
				$obj = new UpdatesMethods();
				$output = $obj->getReleaseCurrentVersion();
				break;
			case 'modified':
				$obj = new UpdatesMethods();
				$output = $obj->getReleaseCurrentModified()['output'];
				if (empty($output)) {
					$output = "The project has no modified files";
				}
				break;
			case 'list':
				$obj = new UpdatesMethods();
				$output = $obj->getReleasesList()['output'];
				break;
			case 'update':
				/*
				 * Предусмотреть
				 * -f - для обновления измененных файлов
				 */
				$obj = new UpdatesMethods();
				if (empty($this->settings[2])) {
					$output = "release tag is empty, see list";
					$this->cli->br();
					$this->cli->warning($output);
					break;
				}
				$output = $obj->setUpdates($this->settings[2])['output'];
				break;
			case 'test':
				// $obj = new UpdatesMethods();
				// $obj->getCliProgress(10, 100,"копирование 1");
				// sleep(2);
				// $obj->getCliProgress(20, 100,"копирование 2");
				// sleep(2);
				// $obj->getCliProgress(30, 100,"копирование 3");
				#$output = "Test";
				break;
			default:
				$output = "wrong params";
				$this->cli->br();
				$this->cli->error($output);
				break;
		}
		$this->cli->br();
		$this->cli->info($output);
		return;
	}
}