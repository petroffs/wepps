<?
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Core\DataWepps;

class RequestProcessingWepps extends RequestWepps {
	public $tpl='';
	public $title='';
	public $headers;
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$tpl = 'Processing.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = array(0=>array('Url'=>"/_pps/extensions/{$this->get['ext']['KeyUrl']}/",'Name'=>$this->title));
		$headers = new TemplateHeadersWepps();
		switch ($action) {
			case 'searchindex':
				$this->title = "Построение поискового индекса";
				$tpl = 'ProcessingSearchIndex.tpl';
				
				/*
				 * Сделаем аналогичную схему, как в текущей версии
				 */
				
				break;
			default:
				if ($action!="") {
					ExceptionWepps::error404();
				}
				break;
		}
		$this->headers = &$headers;
		//$this->tpl = $smarty->fetch( __DIR__ . '/' . $tpl);
		$this->tpl = $tpl;
	}
}
?>