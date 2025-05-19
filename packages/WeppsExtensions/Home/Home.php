<?php
namespace WeppsExtensions\Home;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
	
class HomeWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = "";
				
				/*
				 * Карусель на главной
				 */
				$obj = new DataWepps("Sliders");
				$res = $obj->getMax("t.DisplayOff=0 and SPlace=1 and sm3.Id={$this->navigator->content['Id']}");
				#$res = $obj->getMax("t.DisplayOff=0 and t.SPlace=1");
				#UtilsWepps::debug($obj->sql,1);
				if (!empty($res[0]['Id'])) {
					$smarty->assign('carousel',$res);
					$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Carousel/Carousel.tpl');
					$this->headers->css("/packages/vendor_local/slick/slick/slick.css");
					$this->headers->css("/packages/vendor_local/slick/slick/slick-theme.css");
					$this->headers->js("/packages/vendor_local/slick/slick/slick.min.js");
					$this->headers->js("/ext/Template/Carousel/Carousel.{$this->rand}.js	");
					$this->headers->css("/ext/Template/Carousel/Carousel.{$this->rand}.css");
				}

				/*
				 * Услуги
				 */
				$obj = new DataWepps("Services");
				$res = $obj->getMax("t.DisplayOff=0");
				$smarty->assign('services',$res);
				
				/*
				 * Галерея
				 */
				$this->headers->css("/packages/vendor/dimsemenov/magnific-popup/dist/magnific-popup.css");
				$this->headers->js("/packages/vendor/dimsemenov/magnific-popup/dist/jquery.magnific-popup.js");
				
				$obj = new DataWepps("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
				$res = $obj->getMax("t.TableName='Gallery' and fg.NavigatorId=17",500,1,'t.Priority');
				$smarty->assign('gallery',$res);

				/*
				 * Преимущества
				 */
				$obj = new DataWepps("Advantages");
				$res = $obj->getMax ("t.DisplayOff=0");
				$smarty->assign('advantages',$res);

				/*
				 * Контакты
				 */
				$this->headers->js("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.js");
				$this->headers->css("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.css");
				$apikey = ConnectWepps::$projectServices['yandexmaps']['apikey'];
				$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
				$obj = new DataWepps("Contacts");
				$res = $obj->getMax("t.DisplayOff=0",1);
				$smarty->assign('contacts',$res);
				#UtilsWepps::debug($res,1);
				$this->tpl .= $smarty->fetch('packages/WeppsExtensions/Home/Home.tpl');
				break;
			default:
				ExceptionWepps::error404();
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