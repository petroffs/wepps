<?php
namespace WeppsExtensions\Template;

use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\UtilsWepps;

if (!class_exists('WeppsExtensions\Template\TemplateAddonsWepps')) {
	class TemplateAddonsWepps extends ExtensionWepps {
		public function request() {
			
			$smarty = SmartyWepps::getSmarty();
			$this->headers->js("/packages/vendor/components/jquery/jquery.min.js");
			$this->headers->js("/packages/vendor/components/jqueryui/jquery-ui.min.js");
			$this->headers->css("/packages/vendor/components/jqueryui/themes/base/jquery-ui.min.css");
			$this->headers->css("/packages/vendor/fortawesome/font-awesome/css/font-awesome.min.css");
			$this->headers->css("/packages/vendor/twbs/bootstrap-icons/font/bootstrap-icons.min.css");
			$this->headers->js("/packages/vendor/select2/select2/dist/js/select2.min.js");
			$this->headers->js("/packages/vendor/select2/select2/dist/js/i18n/ru.js");
			$this->headers->css("/packages/vendor/select2/select2/dist/css/select2.min.css");
	
			/*
			 * Проект
			 */
			$this->headers->css("/ext/Template/Layout/Settings.{$this->rand}.css");
			$this->headers->js("/ext/Template/Layout/Layout.{$this->rand}.js");
			$this->headers->css("/ext/Template/Layout/Layout.{$this->rand}.css");
			$this->headers->css("/ext/Template/Layout/Flexbox.{$this->rand}.css");
			$this->headers->css("/ext/Template/Layout/Grid.{$this->rand}.css");
			$this->headers->css("/ext/Template/Layout/Modal.{$this->rand}.css");
			$this->headers->js("/ext/Template/Layout/Suggestions.{$this->rand}.js");
			$this->headers->css("/ext/Template/Layout/Suggestions.{$this->rand}.css");
			
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
			$obj = new DataWepps("Organizations");
			$smarty->assign('org',$obj->fetchmini()[0]);
			unset ($obj);
	
			/*
			 * Соцсети
			 */
			$obj = new DataWepps("ServList");
			$res = $obj->fetch("t.Categories='Соцсети' and t.DisplayOff=0");
			$smarty->assign('socials',$res);
			unset($obj);
	
			/*
			 * Нормальное представление
			 */
			$smarty->assign ('normalView',1);
		}
	}
}

?>
