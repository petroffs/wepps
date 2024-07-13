<?php

namespace WeppsExtensions\HomePage;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class HomePageWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = "";
				
				/*
				 * Карусель на главной
				 */
				$obj = new DataWepps("Sliders");
				$res = $obj->getMax ( "t.DisplayOff=0 and SPlace=1 and sm2.Id={$this->navigator->content['Id']}" );
				if (isset( $res[0]['Id'] )) {
					$smarty->assign ( 'carousel', $res );
					$this->tpl = $smarty->fetch ( 'packages/WeppsExtensions/Template/Carousel/Carousel.tpl', null, 'a' );
					$this->headers->css ( "/packages/vendor/kenwheeler/slick/slick/slick.css" );
					$this->headers->css ( "/packages/vendor/kenwheeler/slick/slick/slick-theme.css" );
					$this->headers->js ( "/packages/vendor/kenwheeler/slick/slick/slick.min.js" );
					$this->headers->css ( "/ext/Template/Carousel/Carousel.{$this->rand}.css" );
				}

				/*
				 * Услуги
				 */
				$obj = new DataWepps("Services");
				$res = $obj->getMax ( "t.DisplayOff=0" );
				$smarty->assign('services',$res);
				$this->tpl .= $smarty->fetch ( 'packages/WeppsExtensions/HomePage/HomePageServices.tpl', null, 'a' );

				/*
				 * Галерея
				 */
				$this->headers->css("/packages/vendor/dimsemenov/magnific-popup/dist/magnific-popup.css");
				$this->headers->js("/packages/vendor/dimsemenov/magnific-popup/dist/jquery.magnific-popup.js");
				$this->headers->css("/ext/Gallery/Gallery.{$this->rand}.css");
				$this->headers->js("/ext/Gallery/Gallery.{$this->rand}.js");
				
				$obj = new DataWepps("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
				$res = $obj->getMax("t.TableName='Gallery' and fg.DirectoryId=17",500,1,'t.Priority');
				$smarty->assign('elements',$res);
				$smarty->assign('galleryTpl',$smarty->fetch ( 'packages/WeppsExtensions/Gallery/Gallery.tpl', null, 'a' ));
				$this->tpl .= $smarty->fetch ( 'packages/WeppsExtensions/HomePage/HomePageGallery.tpl', null, 'a' );
				/*
				 * Преимущества
				 */
				$obj = new DataWepps("Advantages");
				$res = $obj->getMax ( "t.DisplayOff=0" );
				$smarty->assign('advantages',$res);
				$this->tpl .= $smarty->fetch ( 'packages/WeppsExtensions/HomePage/HomePageAdvantages.tpl', null, 'a' );
				/*
				 * Контакты
				 */
				//$this->headers->js("/ext/Addons/GoogleMaps/GoogleMaps.js");
				//$this->headers->css("/ext/Addons/GoogleMaps/GoogleMaps.css");
				//$this->headers->js('https://maps.googleapis.com/maps/api/js?key=AIzaSyDpwLH4rSQRyL3_59AoDsdecpX7KcRjAqo');
				$this->headers->js("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.js");
				$this->headers->css("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.css");
				$apikey = ConnectWepps::$projectServices['yandexmaps']['apikey'];
				$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
				
				$this->headers->css("/ext/Contacts/Contacts.{$this->rand}.css");
				$this->headers->js("/ext/Contacts/Contacts.{$this->rand}.js");
				
				
				$obj = new DataWepps("Contacts");
				$res = $obj->getMax("t.DisplayOff=0",1);
				$smarty->assign('contacts',$res);
				$this->tpl .= $smarty->fetch ( 'packages/WeppsExtensions/HomePage/HomePageContacts.tpl', null, 'a' );
				
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
		$this->headers->css("/ext/HomePage/HomePage.{$this->rand}.css");
		$smarty->assign($this->targetTpl,$this->tpl);
		return;
	}
}
?>