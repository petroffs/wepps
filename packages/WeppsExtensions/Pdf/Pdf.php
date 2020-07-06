<?
namespace WeppsExtensions\Pdf;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class PdfWepps {
	private $get;
	private $output;
	private $css;
	private $header;
	private $footer;
	private $filename = 'pdf.pdf';
	function __construct($get) {
		$this->get = $get;
		$action = UtilsWepps::getStringFormatted($this->get['action']);
		$id = UtilsWepps::getStringFormatted($this->get['id']);
		if ($action == '' || $id == '') ExceptionWepps::error404();
		$smarty = SmartyWepps::getSmarty();
		$obj = new DataWepps("TradeShops");
		$shop = $obj->getMax(1)[0];
		$smarty->assign('shopInfo',$shop);
		$this->css = $smarty->fetch('Pdf.css');
		$this->header = $smarty->fetch('PdfHeader.tpl');
		$this->footer = $smarty->fetch('PdfFooter.tpl');
		switch ($action) {
			case "Order" :
				$order = CartUtilsWepps::getOrder($this->get['id']);
				$obj = new DataWepps("TradeClientsHistory");
				$orderPositions = $obj->getMax("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS']!=0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS',$orderNDS);
				}
				$orderSummLetter = SpellWepps::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$obj = new DataWepps("s_Users");
				$user = $obj->getMax($order['UserId'])[0];
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$smarty->assign('user',$user);
				$this->css .= $smarty->fetch('PdfOrder.css');
				$this->output = $smarty->fetch('PdfOrder.tpl');
				$this->filename = "Заказ ". SpellWepps::getNumberOrder($order['Id']).".pdf";
				break;
			case "Receipt" :
				$order = CartUtilsWepps::getOrder($this->get['id']);
				$obj = new DataWepps("TradeClientsHistory");
				$orderPositions = $obj->getMax("OrderId='{$this->get['id']}'");
				$orderSummLetter = SpellWepps::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$obj = new DataWepps("s_Users");
				$user = $obj->getMax($order['UserId'])[0];
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$smarty->assign('user',$user);
				$this->css .= $smarty->fetch('PdfReceipt.css');
				$this->output = $smarty->fetch('PdfReceipt.tpl');
				$this->filename = "Квитанция ". SpellWepps::getNumberOrder($order['Id']).".pdf";
				break;
			case "Invoice" :
				$order = CartUtilsWepps::getOrder($this->get['id']);
				$obj = new DataWepps("TradeClientsHistory");
				$orderPositions = $obj->getMax("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS']!=0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS',$orderNDS);
				}
				$orderSummLetter = SpellWepps::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$this->css .= $smarty->fetch('PdfInvoice.css');
				$this->output = $smarty->fetch('PdfInvoice.tpl');
				$this->filename = "Счет на оплату ". SpellWepps::getNumberOrder($order['Id']).".pdf";
				break;
			default :
				ExceptionWepps::error404 ();
				break;
		}
	}
	function save() {
		exit();
	}
	function output($download = false) {
		$mpdf = new \Mpdf\Mpdf();
		$mpdf->curlAllowUnsafeSslRequests = true;
		$mpdf->SetMargins(3, 3, 25);
		$mpdf->WriteHTML($this->css,1);
		$mpdf->SetHTMLHeader($this->header);
		$mpdf->SetHTMLFooter($this->footer);
		$mpdf->WriteHTML($this->output);
		if ($download == false) {
			$mpdf->Output();
		} else {
			$mpdf->Output($this->filename,'D');
		}
	}
	function __destruct() {
		exit();
	}
}
?>