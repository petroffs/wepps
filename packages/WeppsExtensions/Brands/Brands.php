<?php
namespace WeppsExtensions\Brands;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;
use WeppsCore\Utils;

class Brands extends Extension
{
	public function request()
	{
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Brands/Brands.tpl';
				$sql = "SELECT distinct substring(pv.PValue,1,1) as FirstLetter,pv.Name,pv.Alias,pv.PValue, concat('{$this->navigator->content['Url']}/?f_1=',pv.Alias) as Url
						from s_PropertiesValues pv
						join Products p on p.Id = pv.TableNameId and p.DisplayOff=0
						where pv.Name=1 and pv.DisplayOff=0";
				$res = Connect::$instance->fetch($sql, [], 'group');
				$smarty->assign('brands', $res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/Brands/Brands.{$this->rand}.css");
		$this->headers->js("/ext/Brands/Brands.{$this->rand}.js");
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
}