<?
namespace WeppsExtensions\Template;

use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsExtensions\Blocks\BlocksWepps;
use WeppsExtensions\Addons\AddonsWepps;

class TemplateWepps extends ExtensionWepps {
	
	function request() {
		$smarty = SmartyWepps::getSmarty();
		$smarty->assign('parent',$this->navigator->parent);
		$smarty->assign('child',$this->navigator->child);
		$smarty->assign('way',$this->navigator->way);
		$smarty->assign('language',$this->navigator->lang);
		$smarty->assign('multilang',$this->navigator->multilang);
		$smarty->assign('content',$this->navigator->content);
		$tpl = str_replace(".tpl", "", $this->navigator->tpl['tpl']);
		$this->headers->css ( "/ext/Template/{$tpl}.{$this->rand}.css" );
		
		/*
		 * Дополнительный глобальный функционал
		 */
		new AddonsWepps($this->navigator,$this->headers);
		
		/*
		 * Раширение
		 */
		if ($this->navigator->content['Extension_FileExt']) {
			$extensionClass = "\WeppsExtensions\\{$this->navigator->content['Extension_FileExt']}\\{$this->navigator->content['Extension_FileExt']}Wepps";
			$extension = new $extensionClass($this->navigator,$this->headers);
		}
		$navigator = &$this->navigator;
		if ($navigator::$pathItem!='' && !isset($extension->extensionData['element'])) {
			ExceptionWepps::error404();
		}
		
		/*
		 * Панели и блоки
		 */
		if ($this->navigator->content['IsBlocksActive']==1) {
			new BlocksWepps($this->navigator, $this->headers);
		}
		
		/*
		 * Управление
		 */
		if (isset($_SESSION['user']['ShowAdmin']) && $_SESSION['user']['ShowAdmin']==1) {
			$this->headers->css("/packages/WeppsAdmin/Admin/Admin.{$this->rand}.css");
			$this->headers->js("/packages/WeppsAdmin/Admin/Admin.{$this->rand}.js");
		}
		
		/*
		 * Передача данных в шаблон
		 */
		$this->headers->js("/ext/Template/{$tpl}.{$this->rand}.js");
		$smarty->assign('headers',$this->headers->get());
		$smarty->assign('content',$this->navigator->content);
		$smarty->assign('nav',$this->navigator->nav);
		
		/*
		 * Вывод в шаблон
		 */
		$smarty->display( __DIR__ . '/' . $this->navigator->tpl['tpl'],null,'f');
	}
}

?>