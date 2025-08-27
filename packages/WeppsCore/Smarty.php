<?php
namespace WeppsCore;

use Smarty\Smarty as VendorSmarty;
use WeppsExtensions\Addons\SmartyExt\SmartyExt;
use WeppsExtensions\Addons\SmartyExt\SmartyPlugins;
class Smarty
{
	private static $instance;
	private function __construct($backOffice = 0)
	{
		$root = Connect::$projectDev['root'];
		$smarty = new VendorSmarty();
		$smarty->setTemplateDir($root . '/packages/');
		$smarty->addExtension(new SmartyExt());
		(new SmartyPlugins($smarty));
		$smarty->setCompileDir($root . '/files/tpl/compile');
		$smarty->setCacheDir($root . 'files/tpl/cache');
		$smarty->error_reporting = error_reporting() & ~E_NOTICE & ~E_WARNING;
		self::$instance = $smarty;
	}
	public static function getSmarty($backOffice = 0): VendorSmarty
	{
		if (empty(self::$instance)) {
			new Smarty($backOffice);
		}
		return self::$instance;
	}
}