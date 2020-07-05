<?
namespace PPSExtensions\Pdf;

use PPS\Utils\UtilsPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Spell\SpellPPS;
use PPSExtensions\Cart\CartUtilsPPS;

class PdfPPS {
	private $get;
	private $output;
	private $css;
	private $header;
	private $footer;
	private $filename = 'pdf.pdf';
	function __construct($get) {
		$this->get = $get;
		$action = UtilsPPS::getStringFormatted($this->get['action']);
		$id = UtilsPPS::getStringFormatted($this->get['id']);
		if ($action == '' || $id == '') ExceptionPPS::error404();
		$smarty = SmartyPPS::getSmarty();
		$obj = new DataPPS("TradeShops");
		$shop = $obj->getMax(1)[0];
		$smarty->assign('shopInfo',$shop);
		$this->css = $smarty->fetch('Pdf.css');
		$this->header = $smarty->fetch('PdfHeader.tpl');
		$this->footer = $smarty->fetch('PdfFooter.tpl');
		switch ($action) {
			case "Order" :
				$order = CartUtilsPPS::getOrder($this->get['id']);
				$obj = new DataPPS("TradeClientsHistory");
				$orderPositions = $obj->getMax("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS']!=0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS',$orderNDS);
				}
				$orderSummLetter = SpellPPS::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$obj = new DataPPS("s_Users");
				$user = $obj->getMax($order['UserId'])[0];
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$smarty->assign('user',$user);
				$this->css .= $smarty->fetch('PdfOrder.css');
				$this->output = $smarty->fetch('PdfOrder.tpl');
				$this->filename = "Заказ ". SpellPPS::getNumberOrder($order['Id']).".pdf";
				break;
			case "Receipt" :
				$order = CartUtilsPPS::getOrder($this->get['id']);
				$obj = new DataPPS("TradeClientsHistory");
				$orderPositions = $obj->getMax("OrderId='{$this->get['id']}'");
				$orderSummLetter = SpellPPS::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$obj = new DataPPS("s_Users");
				$user = $obj->getMax($order['UserId'])[0];
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$smarty->assign('user',$user);
				$this->css .= $smarty->fetch('PdfReceipt.css');
				$this->output = $smarty->fetch('PdfReceipt.tpl');
				$this->filename = "Квитанция ". SpellPPS::getNumberOrder($order['Id']).".pdf";
				break;
			case "Invoice" :
				$order = CartUtilsPPS::getOrder($this->get['id']);
				$obj = new DataPPS("TradeClientsHistory");
				$orderPositions = $obj->getMax("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS']!=0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS',$orderNDS);
				}
				$orderSummLetter = SpellPPS::num2str($order['Summ']);
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
				$this->filename = "Счет на оплату ". SpellPPS::getNumberOrder($order['Id']).".pdf";
				break;
			default :
				ExceptionPPS::error404 ();
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