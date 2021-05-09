<?
namespace WeppsExtensions\Blocks;

use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\FilesWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Mail\MailWepps;
require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

if (!session_start()) session_start();

class RequestBlocksWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'sortable':
				if (isset($_SESSION['user']['ShowAdmin']) && $_SESSION['user']['ShowAdmin']==1 && !empty($this->get['items'])) {
					$ex = explode(",", $this->get['items']);
					$co = 50;
					$str = "";
					foreach ($ex as $value) {
						$str .= "update s_Blocks set Priority='{$co}' where Id='{$value}';\n";
						$co += 5;
					}
					ConnectWepps::$db->exec($str);
				} else {
					exit();
				}
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}













$request = new RequestBlocksWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>