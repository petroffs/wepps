<?php
namespace WeppsAdmin\ConfigExtensions\Orders;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Exception;
use WeppsCore\Data;

class Orders extends Request
{
	public $way;
	public $title;
	public $headers;
	public function request($action = "")
	{
		$smarty = Smarty::getSmarty();
		$this->tpl = 'Orders.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
			'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/",
			'Name' => $this->title
		]);
		$this->headers = new TemplateHeaders();
		$this->headers->js("/packages/WeppsAdmin/ConfigExtensions/Orders/Orders.{$this->headers::$rand}.js");
		$this->headers->css("/packages/WeppsAdmin/ConfigExtensions/Orders/Orders.{$this->headers::$rand}.css");
		if ($action == "") {
			return;
		}
		switch ($action) {
			case 'orders':
				/*
				 * Отобразить список заказов
				 * При клике на заказ подгружать подробности + контролы
				 */
				$this->tpl = 'OrdersItems.tpl';
				$statusesActive = 1;
				if (!empty($this->get['status'])) {
					$statusesActive = (int) $this->get['status'];
				}
				$statusesActive = ($statusesActive == 0) ? 1 : $statusesActive;

				/*
				 * Статусы
				 */
				$sql = "select ts.Id,ts.Name,count(o.Id) as Co from OrdersStatuses ts left join Orders o on o.OStatus = ts.Id where ts.DisplayOff=0 group by ts.Id order by ts.Priority";
				$statuses = Connect::$instance->fetch($sql);
				$sql = "select count(o.Id) as Co from OrdersStatuses ts left join Orders o on o.OStatus = ts.Id where ts.DisplayOff=0 order by ts.Priority";
				$statusesCo = Connect::$instance->fetch($sql);
				array_push($statuses, [
					'Id' => -1,
					'Name' => 'Все заказы',
					'Co' => $statusesCo[0]['Co']
				]);
				$smarty->assign('statuses', $statuses);
				$smarty->assign('statusesActive', $statusesActive);
				$smarty->assign('url', '/_wepps/extensions/Orders/orders.html');

				/*
				 * Заказы
				 */
				$obj = new Data("Orders");
				$condition = "t.OStatus!=-1 * ?";
				if ($statusesActive != -1) {
					$condition = "t.OStatus=?";
				}
				$obj->setParams([$statusesActive]);
				if (!empty($this->get['search'])) {
					#Utils::debug($this->get,1)
					$condition .= " and t.Id=? or t.Name like concat('%',?,'%')";
					$obj->setParams([$statusesActive, $this->get['search'], $this->get['search']]);
				}
				$page = (empty($_GET['page'])) ? 1 : (int) $_GET['page'];
				$orders = $obj->fetch($condition, 50, $page, "t.Id desc");
				if (!empty($orders[0]['Id'])) {
					$smarty->assign('orders', $orders);
					$smarty->assign('paginator', $obj->paginator);
					#Utils::debug($obj->paginator,21);
					$smarty->assign('paginatorUrl', "/_wepps/extensions/Orders/orders.html?status=$statusesActive");
					$smarty->assign('paginatorTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/Orders/Paginator.tpl'));
					$this->headers->css("/packages/WeppsAdmin/Admin/Paginator/Paginator.{$this->headers::$rand}.css");
				}
				break;
			default:
				Exception::error(404);
				break;
		}
		array_push($this->way, [
			'Url' => "/_wepps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
			'Name' => $this->title
		]);
	}
}