<?
namespace PPSExtensions\Services;

use PPS\Utils\RequestPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestServicesPPS extends RequestPPS {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestServices.tpl";
				break;
		}
	}
}
$request = new RequestServicesPPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>