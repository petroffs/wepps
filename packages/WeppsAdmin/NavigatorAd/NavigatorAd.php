<?php
namespace WeppsAdmin\NavigatorAd;

use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\NavigatorDataWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsAdmin\Admin\AdminWepps;


class NavigatorAdWepps {
    function __construct(TemplateHeadersWepps &$headers) {
		$smarty = SmartyWepps::getSmarty();
		$headers->js ("/packages/WeppsAdmin/NavigatorAd/NavigatorAd.{$headers::$rand}.js");
		$headers->css("/packages/WeppsAdmin/NavigatorAd/NavigatorAd.{$headers::$rand}.css");
		$ppsUrl = substr($_GET['ppsUrl'], 9);
		$navigator = new NavigatorWepps($ppsUrl,1);
		$navsub = array();
		$nav2 = new NavigatorDataWepps("s_Directories");

		$translate = AdminWepps::getTranslate();
		$smarty->assign('translate',$translate);

		/*
		 * Рекурсия
		 */
		$navtree = $nav2->getChildTree();
		$smarty->assign('navtree',$navtree);
		//UtilsWepps::debug($navtree,1);
		/*
		 * Элемент списка
		 */
		$listForm = ListsWepps::getListItemForm($headers,"s_Directories", $navigator->content['Id']);
		$navigator->content = $listForm['element'];
				
		$listSettings = $listForm['listSettings'];
		$tpl2 = "ListsItem.tpl";
		$headers = &$listForm['headers'];

		if ($navigator->content['Id'] == 'add') {
			$navigator->content['Id'] = 'add';
			$navigator->content['Name'] = 'Новый раздел';
		    $smarty->assign('listMode','CreateMode');
		} elseif (!empty($listForm['element'])) {
		    $smarty->assign('listMode','ModifyMode');
		} else {
		    ExceptionWepps::error404();
		}
		
		/*
		 * Вывод данных
		 */
		$smarty->assign('nav',$navigator->nav);
		//UtilsWepps::debug($navigator->nav,1);
		$smarty->assign('way',$navigator->way);
		$navigator->content['MetaTitle'] = "{$navigator->content['Name']} — Навигатор";
		$navigator->content['NameNavItem'] = "Навигатор";
		
		$smarty->assign('ppsUrl',$ppsUrl);
		$smarty->assign('ppsPath','navigator');
		$smarty->assign('permFields',$listForm['permFields']);
		$smarty->assign('element',$listForm['element']);
		$smarty->assign('content',$navigator->content);
		$smarty->assign('listScheme',$listForm['listScheme']);
		$smarty->assign('listSettings',$listForm['listSettings']);
		$smarty->assign('tabs',$listForm['tabs']);
		$smarty->assign('controlsTpl', $smarty->fetch( ConnectWepps::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsItemControls.tpl'));
		if (isset($_SESSION['uploads']['list-data-form'])) $smarty->assign('uploaded',$_SESSION['uploads']['list-data-form']);
		$smarty->assign('listItemFormTpl',$smarty->fetch(ConnectWepps::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsItemForm.tpl'));

		/*
		 * Финальный шаблон
		 */
		$smarty->assign('headers', $headers->get());
		$tpl = $smarty->fetch( __DIR__ . '/NavigatorAd.tpl');
		$smarty->assign('extension',$tpl);
	}
}
?>