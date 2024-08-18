<?php
namespace WeppsExtensions\Template;

use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\SmartyWepps;

if (!class_exists('WeppsExtensions\Template\TemplateAddonsWepps')) {
	class TemplateAddonsWepps extends ExtensionWepps {
		public function request() {
			$smarty = SmartyWepps::getSmarty();
			$this->headers->js ( "/packages/vendor/components/jquery/jquery.min.js" );
			$this->headers->js ( "/packages/vendor/components/jqueryui/jquery-ui.min.js" );
			$this->headers->css ( "/packages/vendor/components/jqueryui/themes/base/jquery-ui.min.css" );
			$this->headers->css ( "/packages/vendor/fortawesome/font-awesome/css/font-awesome.min.css" );
	
			/*
			 * Проект
			 */
			$this->headers->js ( "/ext/Template/Layout/Layout.{$this->rand}.js" );
			$this->headers->css ( "/ext/Template/Layout/Layout.{$this->rand}.css" );		
			//$this->headers->css ( "/ext/Template/Layout/WinLayer.{$this->rand}.css" );		
			$this->headers->css ( "/ext/Template/Layout/Win.{$this->rand}.css" );		
			
			/*
			 * Навигация
			 */
			$this->headers->js ("/ext/Template/Nav/Nav.{$this->rand}.js");
			$this->headers->css ("/ext/Template/Nav/Nav.{$this->rand}.css");
			$smarty->assign('nav',$this->navigator->nav);
			$smarty->assign('navTpl',$smarty->fetch( __DIR__ .'/Nav/Nav.tpl'));
	
			/*
			 * Формы
			 */
			$this->headers->js ("/ext/Template/Forms/Forms.{$this->rand}.js");
			$this->headers->css ("/ext/Template/Forms/Forms.{$this->rand}.css");
	
			/*
			 * Информация организации
			 */
			$obj = new DataWepps ( "TradeShops" );
			$res = $obj->get () [0];
			$smarty->assign ( 'shopInfo', $res );
			unset ($obj);
	
			/*
			 * Соцсети
			 */
			$obj = new DataWepps ( "ServList" );
			$res = $obj->getMax ( "t.Categories='Соцсети' and t.DisplayOff=0" );
			$smarty->assign ('socials', $res );
			unset ( $obj );
	
			/*
			 * Нормальное представление
			 */
			$smarty->assign ('normalView',1);
		}
	}
}

?>
