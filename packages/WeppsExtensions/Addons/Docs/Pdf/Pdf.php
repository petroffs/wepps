<?php
namespace WeppsExtensions\Addons\Docs\Pdf;

use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\TextTransforms;
use WeppsExtensions\Cart\CartUtils;
use WeppsCore\Connect;

class Pdf {
	private $get;
	private $output;
	private $css;
	private $header;
	private $footer;
	private $filename = 'pdf.pdf';
	function __construct($get) {
		$this->get = $get;
		$action = Utils::trim($this->get['action']);
		$id = Utils::trim($this->get['id']);
		if ($action == '' || $id == '') Exception::error404();
		$smarty = Smarty::getSmarty();
		$obj = new Data("TradeShops");
		$shop = $obj->fetch(1)[0];
		$smarty->assign('shopInfo',$shop);
		$smarty->assign('projectInfo',Connect::$projectInfo);
		$smarty->assign('projectDev',Connect::$projectDev);
		$this->css = $smarty->fetch('Pdf.css');
		$this->header = $smarty->fetch('PdfHeader.tpl');
		$this->footer = $smarty->fetch('PdfFooter.tpl');
		switch ($action) {
			case "Order" :
				$order = CartUtils::getOrder($this->get['id']);
				$obj = new Data("TradeClientsHistory");
				$orderPositions = $obj->fetch("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS']!=0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS',$orderNDS);
				}
				$orderSummLetter = TextTransforms::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$obj = new Data("s_Users");
				$user = $obj->fetch($order['UserId'])[0];
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$smarty->assign('user',$user);
				$this->css .= $smarty->fetch('PdfOrder.css');
				$this->output = $smarty->fetch('PdfOrder.tpl');
				$this->filename = "Заказ ". TextTransforms::getNumberOrder($order['Id']).".pdf";
				break;
			case "Receipt" :
				$order = CartUtils::getOrder($this->get['id']);
				$obj = new Data("TradeClientsHistory");
				$orderPositions = $obj->fetch("OrderId='{$this->get['id']}'");
				$orderSummLetter = TextTransforms::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId']!=0) $orderPositionsCount += $value['ItemQty'];
				}
				$obj = new Data("s_Users");
				$user = $obj->fetch($order['UserId'])[0];
				$smarty->assign('order',$order);
				$smarty->assign('orderPositions',$orderPositions);
				$smarty->assign('orderPositionsCount',$orderPositionsCount);
				$smarty->assign('orderSummLetter',$orderSummLetter);
				$smarty->assign('user',$user);
				$this->css .= $smarty->fetch('PdfReceipt.css');
				$this->output = $smarty->fetch('PdfReceipt.tpl');
				$this->filename = "Квитанция ". TextTransforms::getNumberOrder($order['Id']).".pdf";
				break;
			case "Invoice" :
				$order = CartUtils::getOrder($this->get['id']);
				$obj = new Data("TradeClientsHistory");
				$orderPositions = $obj->fetch("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS']!=0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS',$orderNDS);
				}
				$orderSummLetter = TextTransforms::num2str($order['Summ']);
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
				$this->filename = "Счет на оплату ". TextTransforms::getNumberOrder($order['Id']).".pdf";
				break;
			default :
				Exception::error404 ();
				break;
		}
	}
	function save() {
		exit();
	}
	function output($download = false) {
		$mpdf = new \Mpdf\Mpdf();
		$mpdf->showImageErrors = true;
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