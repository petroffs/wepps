<?
namespace WeppsAdmin\ConfigExtensions\Processing;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsAdmin\Lists\ListsWepps;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

if (!session_start()) session_start();

//http://pps.lubluweb.ru/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php?id=5

class RequestProcessingWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (!isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin']!=1) ExceptionWepps::error404();
		switch ($action) {
			case "searchindex":
				ListsWepps::setSearchIndex();
				UtilsWepps::getModal('Поисковый индекс построен');
				break;
			default:
				UtilsWepps::debug('def1',1);
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestProcessingWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>