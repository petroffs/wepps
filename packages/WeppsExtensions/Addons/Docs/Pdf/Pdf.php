<?php
namespace WeppsExtensions\Addons\Docs\Pdf;

use WeppsCore\Utils;
use Dompdf\Dompdf;
use WeppsCore\Exception;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\TextTransforms;
use WeppsExtensions\Profile\ProfileActions;
use WeppsCore\Connect;

class Pdf
{
	private $get;
	private $output;
	private $css;
	private $header;
	private $footer;
	private $filename = 'pdf.pdf';
	function __construct($get)
	{
		$this->get = $get;
		$action = Utils::trim($this->get['action']);
		$id = Utils::trim($this->get['id']);
		if ($action == '' || $id == '')
			Exception::error404();
		$smarty = Smarty::getSmarty();
		$obj = new Data("TradeShops");
		$shop = $obj->fetch(1)[0];
		$smarty->assign('shopInfo', $shop);
		$smarty->assign('projectInfo', Connect::$projectInfo);
		$smarty->assign('projectDev', Connect::$projectDev);
		$this->css = $smarty->fetch('Pdf.css');
		$this->header = $smarty->fetch('PdfHeader.tpl');
		$this->footer = $smarty->fetch('PdfFooter.tpl');
		switch ($action) {
			case "Order":
				$profileActions = new ProfileActions(false);
				$order = $profileActions->getFullOrder($this->get['id']);
				$obj = new Data("TradeClientsHistory");
				$orderPositions = $obj->fetch("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS'] != 0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS', $orderNDS);
				}
				$orderSummLetter = TextTransforms::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId'] != 0)
						$orderPositionsCount += $value['ItemQty'];
				}
				$obj = new Data("s_Users");
				$user = $obj->fetch($order['UserId'])[0];
				$smarty->assign('order', $order);
				$smarty->assign('orderPositions', $orderPositions);
				$smarty->assign('orderPositionsCount', $orderPositionsCount);
				$smarty->assign('orderSummLetter', $orderSummLetter);
				$smarty->assign('user', $user);
				$this->css .= $smarty->fetch('PdfOrder.css');
				$this->output = $smarty->fetch('PdfOrder.tpl');
				$this->filename = "Заказ {$order['Id']}.pdf";
				break;
			case "Receipt":
				$profileActions = new ProfileActions(false);
				$order = $profileActions->getFullOrder($this->get['id']);
				$obj = new Data("TradeClientsHistory");
				$orderPositions = $obj->fetch("OrderId='{$this->get['id']}'");
				$orderSummLetter = TextTransforms::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId'] != 0)
						$orderPositionsCount += $value['ItemQty'];
				}
				$obj = new Data("s_Users");
				$user = $obj->fetch($order['UserId'])[0];
				$smarty->assign('order', $order);
				$smarty->assign('orderPositions', $orderPositions);
				$smarty->assign('orderPositionsCount', $orderPositionsCount);
				$smarty->assign('orderSummLetter', $orderSummLetter);
				$smarty->assign('user', $user);
				$this->css .= $smarty->fetch('PdfReceipt.css');
				$this->output = $smarty->fetch('PdfReceipt.tpl');
				$this->filename = "Квитанция {$order['Id']}.pdf";
				break;
			case "Invoice":
				$profileActions = new ProfileActions(false);
				$order = $profileActions->getFullOrder($this->get['id']);
				$obj = new Data("TradeClientsHistory");
				$orderPositions = $obj->fetch("OrderId='{$this->get['id']}'");
				if ($shop['UrNDS'] != 0) {
					$orderNDS = round($order['Summ'] / ((100 + $shop['UrNDS']) / 100) * ($shop['UrNDS'] / 100));
					$smarty->assign('orderNDS', $orderNDS);
				}
				$orderSummLetter = TextTransforms::num2str($order['Summ']);
				$orderPositionsCount = 0;
				foreach ($orderPositions as $value) {
					if ($value['ProductId'] != 0)
						$orderPositionsCount += $value['ItemQty'];
				}
				$smarty->assign('order', $order);
				$smarty->assign('orderPositions', $orderPositions);
				$smarty->assign('orderPositionsCount', $orderPositionsCount);
				$smarty->assign('orderSummLetter', $orderSummLetter);
				$this->css .= $smarty->fetch('PdfInvoice.css');
				$this->output = $smarty->fetch('PdfInvoice.tpl');
				$this->filename = "Счет на оплату {$order['Id']}.pdf";
				break;
			default:
				Exception::error404();
				break;
		}
	}
	function save()
	{
		exit();
	}
	function output($download = false)
	{
		$dompdf = new Dompdf();
		$dompdf->setPaper('A4', 'portrait');

		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $this->css . '</style></head><body>';
		$html .= $this->header;
		$html .= $this->output;
		$html .= $this->footer;
		$html .= '</body></html>';

		$dompdf->loadHtml($html);
		$dompdf->render();

		if ($download == false) {
			$dompdf->stream($this->filename, ['Attachment' => false]);
		} else {
			$dompdf->stream($this->filename, ['Attachment' => true]);
		}
	}
	function __destruct()
	{
		exit();
	}
}