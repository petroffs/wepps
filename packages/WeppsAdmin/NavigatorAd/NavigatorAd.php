<?php
namespace WeppsAdmin\NavigatorAd;

use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Navigator;
use WeppsCore\Utils;
use WeppsCore\NavigatorData;
use WeppsAdmin\Lists\Lists;
use WeppsCore\TemplateHeaders;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsAdmin\Admin\Admin;


class NavigatorAd {
    public function __construct(TemplateHeaders &$headers) {
		$smarty = Smarty::getSmarty();
		$headers->js ("/packages/WeppsAdmin/NavigatorAd/NavigatorAd.{$headers::$rand}.js");
		$headers->css("/packages/WeppsAdmin/NavigatorAd/NavigatorAd.{$headers::$rand}.css");
		$ppsUrl = substr($_GET['ppsUrl'], 9);
		$navigator = new Navigator($ppsUrl,1);
		$nav2 = new NavigatorData("s_Navigator");

		$translate = Admin::getTranslate();
		$smarty->assign('translate',$translate);

		/*
		 * Рекурсия
		 */
		$navtree = $nav2->getChildTree();
		$smarty->assign('navtree',$navtree);
		
		/*
		 * Элемент списка
		 */
		$listForm = Lists::getListItemForm($headers,"s_Navigator", $navigator->content['Id']);
		$navigator->content = $listForm['element'];
		$headers = &$listForm['headers'];

		if ($navigator->content['Id'] == 'add') {
			$navigator->content['Id'] = 'add';
			$navigator->content['Name'] = 'Новый раздел';
		    $smarty->assign('listMode','CreateMode');
		} elseif (!empty($listForm['element'])) {
		    $smarty->assign('listMode','ModifyMode');
		} else {
		    Exception::error404();
		}
		$smarty->assign('nav',$navigator->nav);
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
		$smarty->assign('controlsTpl', $smarty->fetch( Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsItemControls.tpl'));
		if (isset($_SESSION['uploads']['list-data-form'])) $smarty->assign('uploaded',$_SESSION['uploads']['list-data-form']);
		$smarty->assign('listItemFormTpl',$smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/ListsItemForm.tpl'));
		$tpl = $smarty->fetch( __DIR__ . '/NavigatorAd.tpl');
		$smarty->assign('extension',$tpl);
	}
}
?>