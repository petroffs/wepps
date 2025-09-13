<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\RemoteServices\RecaptchaV2;
use WeppsExtensions\Cart\CartUtils;

class Profile extends Extension
{
	private $profileTpl = '';
	private $user;
	public function request()
	{
		$this->tpl = __DIR__ . '/Profile.tpl';
		$this->user = Connect::$projectData['user'] ?? [];
		if (!empty(Navigator::$pathItem)) {
			$this->extensionData['element'] = 1;
		}
		$profileUtils = new ProfileUtils($this->user);
		$profileNav = $profileUtils->getNav();
		$smarty = Smarty::getSmarty();
		$smarty->assign('pathItem', Navigator::$pathItem);
		$smarty->assign('get', $this->get);
		$smarty->assign('profileNav', $profileNav);
		$this->headers->js("/ext/Profile/Profile.{$this->rand}.js");
		$this->headers->css("/ext/Profile/Profile.{$this->rand}.css");
		$this->headers->js("/packages/vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.min.js");
		if (!empty($this->get['token'])) {
			Utils::cookies('wepps_token');
			$jwt = new Jwt();
			$payload = $jwt->token_decode($this->get['token']);
			if ($payload['payload']['status'] = 200) {
				switch ($payload['payload']['typ']) {
					case 'pass':
						$user = @Connect::$instance->fetch('SELECT * from s_Users where Id=? and DisplayOff=0', [$payload['payload']['id']])[0];
						if (empty($user)) {
							$this->profileTpl = 'ProfilePasswordError.tpl';
							break;
						}
						$this->profileTpl = 'ProfilePasswordConfirm.tpl';
						break;
					case 'reg':
						$this->profileTpl = 'ProfileRegConfirm.tpl';
						break;
				}
			} else {
				$this->profileTpl = 'ProfilePasswordError.tpl';
			}
			$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
			$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
			return;
		}
		if (empty($this->user)) {
			switch (Navigator::$pathItem) {
				case 'reg':
					$this->profileTpl = 'ProfileReg.tpl';
					break;
				case 'password':
					$this->profileTpl = 'ProfilePassword.tpl';
					$recaptcha = new RecaptchaV2($this->headers);
					$response = $recaptcha->render();
					$smarty->assign('recaptcha', $response);
					break;
				default:
					$this->profileTpl = 'ProfileSignIn.tpl';
					break;
			}
			$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
			$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
			return;
		}
		switch (Navigator::$pathItem) {
			case '':
			case 'reg':
			case 'password':
				$this->profileTpl = 'ProfileHome.tpl';
				break;
			case 'orders':
				if (!empty($this->get['id'])) {
					$this->profileTpl = 'ProfileOrdersItem.tpl';
					$this->getOrder($this->get['id'],$smarty);
					break;
				}
				$this->profileTpl = 'ProfileOrders.tpl';
				$obj = new Data('Orders');
				$obj->setParams([$this->user['Id']]);
				$orders = $obj->fetch('t.DisplayOff=0 and t.UserId=?', 20,(int) ($this->get['page'] ?? 1),'Id desc');
				$smarty->assign('orders', $orders);
				#Utils::debug($orders,21);
				break;
			case 'favorites':
				$this->profileTpl = 'ProfileFavorites.tpl';
				break;
			case 'settings':
				$this->profileTpl = 'ProfileSettings.tpl';
				break;
			default:
				Exception::error404();
				break;
		}
		$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
	private function getOrder($id,\Smarty\Smarty $smarty): array {
		$obj = new Data("Orders");
		$obj->setJoin('left join Payments p on p.TableNameId=t.Id and p.TableName=\'Orders\' and p.IsPaid=1 and p.IsProcessed=1 and p.DisplayOff=0');
		$obj->setConcat('if(sum(p.PriceTotal)>0,sum(p.PriceTotal),0) PricePaid,if(sum(p.PriceTotal)>0,(t.OSum-sum(p.PriceTotal)),t.OSum) OSumPay,group_concat(p.Id,\':::\',p.Name,\':::\',p.PriceTotal,\':::\',p.MerchantDate,\':::\' separator \';;;\') Payments');
		$obj->setParams([$this->user['Id'],$id]);
		#Utils::debug($obj->sql,2);
		if (empty($order = @$obj->fetch("t.DisplayOff=0 and t.UserId=? and t.Id=?")[0])) {
			Exception::error(404);
		}
		$sql = "select ts.Id,ts.Name from OrdersStatuses ts group by ts.Id order by ts.Priority";
		$statuses = Connect::$instance->fetch($sql);
		$smarty->assign('statuses',$statuses);
		$smarty->assign('statusesActive',$order['OStatus']);
		$products = json_decode($order['JPositions'],true);
		$sql = '';
		$sum = 0;
		$cartUtils = new CartUtils();
		$products = $cartUtils->getCartPositionsRecounter($products,$order['ODeliveryDiscount'],$order['OPaymentTariff'],$order['OPaymentDiscount']);
		foreach ($products as $value) {
			$sum += $value['sum'];
			$sql .= "\n(select '{$value['id']}' `id`,'{$value['name']}' `name`,'{$value['quantity']}' `quantity`,'{$value['price']}' `price`,'{$value['sum']}' `sum`,'{$value['priceTotal']}' `priceTotal`,'{$value['sumTotal']}' `sumTotal`) union";
		}
		$sql = "(select * from (\n" . trim($sql," union\n").') y)';
		$ids = implode(',', array_column($products, 'id'));
		$sql = "select x.id,x.name name,x.quantity,x.price,x.sum,x.priceTotal,x.sumTotal from $sql x left join Products t on x.id=t.Id where x.id in ($ids)";
		$products = Connect::$instance->fetch($sql);
		$smarty->assign('products', $products);

		$sum += $order['ODeliveryTariff'];
		$sum -= $order['ODeliveryDiscount'];
		$sum += $order['OPaymentTariff'];
		$sum -= $order['OPaymentDiscount'];

		$order['OSum'] = Utils::round($sum);
		#$order['OSumPay'] = $sum - $order['PricePaid'];
		$sql = "update Orders set OSum=? where Id=?";
		Connect::$instance->query($sql,[$sum,$id]);
		$obj = new Data("OrdersEvents");
		$obj->setParams([$id]);
		$obj->setJoin("join s_Users u on u.Id=t.UserId");
		$obj->setConcat("u.Name UsersName");
		$res = $obj->fetch("t.DisplayOff=0 and t.OrderId=?",2000,1,"t.Priority");
		if (!empty($res)) {
			$order['Messages'] = $res;
		}
		$smarty->assign('order', $order);
		return ['order'=>$order,'products'=>$products,'statuses'=>$statuses];
	}
}