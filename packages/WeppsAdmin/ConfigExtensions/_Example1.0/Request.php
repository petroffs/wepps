<?
namespace WeppsAdmin\ConfigExtensions\Example;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Mail\MailWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\FilesWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsAdmin\Admin\AdminWepps;
use WeppsCore\Spell\SpellWepps;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

if (!session_start()) session_start();

//http://host/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php?id=5

class RequestExampleWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (!isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin']!=1) ExceptionWepps::error404();
		switch ($action) {
			case "test":
				UtilsWepps::debug('test1',1);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestExampleWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>