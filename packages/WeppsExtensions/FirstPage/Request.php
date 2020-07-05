<?
namespace PPSExtensions\FirstPage;

use PPS\Utils\RequestPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestCustomPPS extends RequestPPS {
	public function request($get) {
		$action = (isset($get['action'])) ? $get['action'] : '';

		switch ($action) {
			case 'test':
				$this->tpl = "RequestCustom2.tpl";
				break;
			default:
				$this->tpl = "RequestCustom1.tpl";
				break;
		}
	}
}

$request = new RequestCustomPPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>