<?
namespace WeppsExtensions\Addons;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

if (!class_exists('WeppsExtensions\Addons\AddonsWepps')) {
	class AddonsWepps extends ExtensionWepps {
		public function request() {
			$smarty = SmartyWepps::getSmarty();
			$rand = $this->headers::$rand;
			
			$this->headers->js ( "/packages/vendor/components/jquery/jquery.min.js" );
			$this->headers->js ( "/packages/vendor/components/jqueryui/jquery-ui.min.js" );
			$this->headers->css ( "/packages/vendor/components/jqueryui/themes/base/jquery-ui.min.css" );
			$this->headers->css ( "/packages/vendor/fortawesome/font-awesome/css/font-awesome.min.css" );
	
			/**
			 * Проект
			 */
			$this->headers->js ( "/ext/Addons/Layout/Layout.{$rand}.js" );
			$this->headers->css ( "/ext/Addons/Layout/Layout.{$rand}.css" );		
			//$this->headers->css ( "/ext/Addons/Layout/WinLayer.{$rand}.css" );		
			$this->headers->css ( "/ext/Addons/Layout/Win.{$rand}.css" );		
			
			/**
			 * Навигация
			 */
			$this->headers->js ("/ext/Addons/Nav/Nav.{$rand}.js");
			$this->headers->css ("/ext/Addons/Nav/Nav.{$rand}.css");
			$smarty->assign('nav',$this->navigator->nav);
			$smarty->assign('navTpl',$smarty->fetch( __DIR__ .'/Nav/Nav.tpl'));
	
			/**
			 * Формы
			 */
			$this->headers->js ("/ext/Addons/Forms/Forms.{$rand}.js");
			$this->headers->css ("/ext/Addons/Forms/Forms.{$rand}.css");
	
			/**
			 * Информация организации
			 */
			$obj = new DataWepps ( "TradeShops" );
			$res = $obj->get () [0];
			$smarty->assign ( 'shopInfo', $res );
			unset ( $obj );
	
			/**
			 * Соцсети
			 */
			$obj = new DataWepps ( "ServList" );
			$res = $obj->getMax ( "t.Categories='Соцсети' and t.DisplayOff=0" );
			$smarty->assign ('socials', $res );
			unset ( $obj );
	
			/**
			 * Нормальное представление
			 */
			$smarty->assign ( 'normalView', 1 );
			$smarty->assign ( 'normalHeader1', 1 );
		}
	}
}

?>
