<?php
namespace WeppsExtensions\Home;
use WeppsCore\Extension;
use WeppsCore\Data;
use WeppsCore\Exception;
use WeppsCore\Smarty;
use WeppsCore\Navigator;
use WeppsCore\Connect;
use WeppsCore\Utils;
	
class Home extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = "";
				
				/*
				 * Услуги
				 */
				$obj = new Data("Services");
				$res = $obj->fetch("t.DisplayOff=0");
				$smarty->assign('services',$res);
				
				/*
				 * Галерея
				 */
				$this->headers->css("/packages/vendor/dimsemenov/magnific-popup/dist/magnific-popup.css");
				$this->headers->js("/packages/vendor/dimsemenov/magnific-popup/dist/jquery.magnific-popup.js");
				
				$obj = new Data("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
				$res = $obj->fetch("t.TableName='Gallery' and fg.NavigatorId=17",500,1,'t.Priority');
				$smarty->assign('gallery',$res);

				/*
				 * Преимущества
				 */
				$obj = new Data("Advantages");
				$res = $obj->fetch("t.DisplayOff=0");
				$smarty->assign('advantages',$res);

				/*
				 * Контакты
				 */
				$this->headers->js("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.js");
				$this->headers->css("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.css");
				$apikey = Connect::$projectServices['yandexmaps']['apikey'];
				$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
				$obj = new Data("Contacts");
				$res = $obj->fetch("t.DisplayOff=0",1);
				$smarty->assign('contacts',$res);
				#Utils::debug($res,1);
				$this->tpl .= $smarty->fetch('packages/WeppsExtensions/Home/Home.tpl');
				break;
			default:
				Exception::error404();
				break;
		}
		/*
		 * Нормальное представление
		 */
		$smarty->assign('normalView',0);
		$this->navigator->content['Text1'] = '';
		$this->headers->css("/ext/Home/Home.{$this->rand}.css");
		$this->headers->js("/ext/Home/Home.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$this->tpl);
		return;
	}
}