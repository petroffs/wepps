<?php
namespace WeppsAdmin\ConfigExtensions\Orders;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\DataWepps;

class OrdersWepps extends RequestWepps {
	private $way;
	private $title;
	private $headers;
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = 'Orders.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
			'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/",
			'Name'=>$this->title
		]);
		$this->headers = new TemplateHeadersWepps();
		$this->headers->js ("/packages/WeppsAdmin/ConfigExtensions/Orders/Orders.{$this->headers::$rand}.js");
		$this->headers->css ("/packages/WeppsAdmin/ConfigExtensions/Orders/Orders.{$this->headers::$rand}.css");
		if ($action=="") {
			return;
		}
		switch ($action) {
			case 'orders':
				/*
				 * Отобразить список заказов
				 * При клике на заказ подгружать подробности + контролы
				 *
				 */
				//UtilsWepps::debug(1,1);
				$this->tpl = 'OrdersItems.tpl';
				$statusesActive = 1;
				if (!empty($this->get['status'])) {
					$statusesActive = (int) $this->get['status'];
				}
				$statusesActive = ($statusesActive==0) ? 1 : $statusesActive;

				/*
				 * Статусы
				 */
				$sql = "select ts.Id,ts.Name,count(o.Id) as Co from OrdersStatuses ts left join Orders o on o.OStatus = ts.Id where ts.DisplayOff=0 group by ts.Id order by ts.Priority";
				$statuses = ConnectWepps::$instance->fetch($sql);
				$sql = "select count(o.Id) as Co from OrdersStatuses ts left join Orders o on o.OStatus = ts.Id where ts.DisplayOff=0 order by ts.Priority";
				$statusesCo = ConnectWepps::$instance->fetch($sql);
				array_push($statuses, [
						'Id' => -1,
						'Name' => 'Все заказы',
						'Co' => $statusesCo[0]['Co']
				]);
				$smarty->assign('statuses',$statuses);
				$smarty->assign('statusesActive',$statusesActive);
				$smarty->assign('url','https://platform.wepps.ubu/_wepps/extensions/Orders/orders.html');
				
				/*
				 * Заказы
				 */
				$obj = new DataWepps("Orders");
				$condition = "t.OStatus!=-1 * ?";
				if ($statusesActive!=-1) {
					$condition = "t.OStatus=?";
				}
				$obj->setParams([$statusesActive]);
				if (!empty($this->get['search'])) {
					#UtilsWepps::debug($this->get,1)
					$condition .= " and t.Id=? or t.Name like concat('%',?,'%')";
					$obj->setParams([$statusesActive,$this->get['search'],$this->get['search']]);
				}
				$page = (empty($_GET['page'])) ? 1 : (int) $_GET['page'];
				$orders = $obj->getMax($condition,50,$page,"t.Id desc");
				if (!empty($orders[0]['Id'])) {
					$smarty->assign('orders',$orders);
					$smarty->assign('paginator',$obj->paginator);
					#UtilsWepps::debug($obj->paginator,21);
					$smarty->assign('paginatorUrl',"/_wepps/extensions/Orders/orders.html?status=$statusesActive");
					$smarty->assign('paginatorTpl', $smarty->fetch(ConnectWepps::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/Orders/Paginator.tpl'));
					$this->headers->css("/packages/WeppsAdmin/Admin/Paginator/Paginator.{$this->headers::$rand}.css");
				}
				break;
			default:
				ExceptionWepps::error(404);
				break;
		}
		array_push($this->way, [
			'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
			'Name'=>$this->title
		]);
	}
}