<?php
namespace WeppsExtensions\Gallery;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Gallery extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Gallery/Gallery.tpl';
				$obj = new Data("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
				$res = $obj->fetch("t.TableName='Gallery' and fg.NavigatorId = '{$this->navigator->content['Id']}'",500,1,'t.Priority');
				$smarty->assign('elements',$res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/packages/vendor/dimsemenov/magnific-popup/dist/magnific-popup.css");
		$this->headers->js("/packages/vendor/dimsemenov/magnific-popup/dist/jquery.magnific-popup.js");
		$this->headers->css("/ext/Gallery/Gallery.{$this->rand}.css");
		$this->headers->js("/ext/Gallery/Gallery.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}