<?
namespace PPSExtensions\News;

use PPS\Utils\RequestPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestNewsPPS extends RequestPPS {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestNews.tpl";
				break;
		}
	}
}
$request = new RequestNewsPPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>