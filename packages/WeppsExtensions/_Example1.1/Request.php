<?
namespace PPSExtensions\Example;

use PPS\Utils\RequestPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestExamplePPS extends RequestPPS {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestExample.tpl";
				break;
		}
	}
}
$request = new RequestExamplePPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>