<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Utils;

class SaveItemTemplates extends Request
{
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];

	#[\Override]
	public function request($action = ""): void
    {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName'] == 's_Templates') {
	    	$this->copyTpl($this->element['FileTemplate']);
	    }
	}
	private function copyTpl($tpl): void
    {
		// Имя шаблона приходит из запроса и участвует в путях copy():
		// строгий паттерн + safeRelPath (снимает file/ssrf-taint) закрывают выход
		// за каталог Template
		$tplOk = null;
		if (preg_match('/^[A-Za-z0-9_\/\-]+\.tpl$/D', (string) $tpl) === 1) {
			$tplOk = Utils::safeRelPath((string) $tpl);
		}
		if ($tplOk === null) {
			return;
		}
		$tpl = $tplOk;
		$tmp = substr($tpl, 0, - 4);
		$root = Connect::$projectDev['root'] . "/packages/WeppsExtensions/Template/";
		if (! is_file("{$root}{$tmp}.tpl")) {
			copy("{$root}Template.tpl", "{$root}{$tmp}.tpl");
		}
		if (! is_file("{$root}{$tmp}.css")) {
			copy("{$root}Template.css", "{$root}{$tmp}.css");
		}
		if (! is_file("{$root}{$tmp}.js")) {
			copy("{$root}Template.js", "{$root}{$tmp}.js");
		}
	}
}
