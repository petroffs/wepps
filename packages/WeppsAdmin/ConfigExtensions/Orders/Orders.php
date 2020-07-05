<?
namespace WeppsAdmin\ConfigExtensions\Orders;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\DataWepps;

class RequestOrdersWepps extends RequestWepps {
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = 'Orders.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = array(0=>array('Url'=>"/_pps/extensions/{$this->get['ext']['KeyUrl']}/",'Name'=>$this->title));
		$this->headers = new TemplateHeadersWepps();
		$this->headers->js ("/packages/WeppsAdmin/ConfigExtensions/Orders/Orders.{$this->headers::$rand}.js");
		$this->headers->css ("/packages/WeppsAdmin/ConfigExtensions/Orders/Orders.{$this->headers::$rand}.css");
		switch ($action) {
			case 'orders':
				/*
				 * Отобразить список заказов
				 * При клике на заказ подгружать подробности + контролы
				 *
				 */
				//UtilsWepps::debug(1,1);
				
				$statusActive = 1;
				if (!empty($_GET['status'])) {
					$statusActive = (int) $_GET['status'];
				}
				$statusActive = ($statusActive==0) ? 1 : $statusActive;
				
				/*
				 * Статусы
				 */
				$sql = "select ts.Id,ts.Name,count(o.Id) as Co
                        from TradeStatus ts
                        left join TradeOrders o on o.TStatus = ts.Id
                        where ts.DisplayOff=0
                        group by ts.Id
                        order by ts.Priority";
				$statuses = ConnectWepps::$instance->fetch($sql);
				$smarty->assign('statuses',$statuses);
				$smarty->assign('statusesActive',$statusActive);
				
				/*
				 * Заказы
				 */
				$condition = "";
				if ($statusActive!=7) {
					$condition = "t.TStatus = '$statusActive'";
				}
				$page = (empty($_GET['page'])) ? 1 : (int) $_GET['page'];
				$obj = new DataWepps("TradeOrders");
				$orders = $obj->getMax($condition,20,$page,"t.Id desc");
				if (!empty($orders[0]['Id'])) {
					$smarty->assign('orders',$orders);
				}
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		
		
	}
}
?>