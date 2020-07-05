<?
namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\DataWepps;

class RequestUploadsWepps extends RequestWepps {
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = 'Uploads.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = array(0=>array('Url'=>"/_pps/extensions/{$this->get['ext']['KeyUrl']}/",'Name'=>$this->title));
		$headers = new TemplateHeadersWepps();
		$headers->js ("/packages/WeppsAdmin/ConfigExtensions/Uploads/Uploads.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/ConfigExtensions/Uploads/Uploads.{$headers::$rand}.css");
		
		switch ($action) {
			case 'excel':
				$this->title = "Загрузки из Excel";
				$this->tpl = 'UploadsExcel.tpl';
				if (isset($_SESSION['uploads']['list-data-form'])) {
					$smarty->assign('uploaded',$_SESSION['uploads']['list-data-form']);
				}

				$obj = new DataWepps("s_UploadsSource");
				$source = $obj->get("DisplayOff=0",200,1);
				$smarty->assign('source',$source);
				
				$obj = new DataWepps("s_Files");
				$files = $obj->getMax("TableName='s_UploadsSource'",5,1,"t.Id desc");
				$smarty->assign('files',$files);
				break;
			default:
				if ($action!="") {
					ExceptionWepps::error404();
				}
				break;
		}
		$this->headers = &$headers;
	}
}
?>