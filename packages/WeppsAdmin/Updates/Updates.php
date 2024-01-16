<?php

namespace WeppsAdmin\Updates;

use WeppsCore\Utils\UtilsWepps;

class UpdatesWepps {
	public $parent = 1;
	public $settings;
	public function __construct($settings=[]) {
		$this->settings = $settings;
		#UtilsWepps::debug($this->settings,1);
		if ($this->parent==0) {
			return;
		}
		switch ($this->settings[1]) {
			case 'test':
				echo 1;
				break;
			default:
				echo 2;
				break;
		}
		return true;
	}
	
}
?>