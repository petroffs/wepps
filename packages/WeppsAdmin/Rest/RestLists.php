<?
namespace WeppsAdmin\Rest;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;

class RestListsWepps extends RestWepps {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function getLists($list='',$field='',$text='',$page=1) {
		/*
		 * Условие поля извлечь из схемы
		 */
		$sql = "select * from s_ConfigFields where TableName='{$list}' && Id='{$field}'";
		$res = ConnectWepps::$instance->fetch($sql);
		//UtilsPPS::debugf($sql,1);
		
		if (empty($res)) {
			ExceptionWepps::error404();
		}
		
		$ex = explode('::', $res[0]['Type']);
		$list = $ex[1];
		$field = $ex[2];
		$condition = $ex[3];
		
		if (mb_strlen($text)>=0) {
			$condition .= " and t.{$field} like '%{$text}%'";
		}
		$limit = 10;
		$offset = ($page-1)*$limit;
		$sql = "select t.Id id,concat(t.{$field},' (',t.Id,')') text from $list t where $condition order by t.{$field} limit $offset,$limit";
		$res = ConnectWepps::$instance->fetch($sql);
		
		$pagination = false;
		if (!empty($res)) {
			$pagination = true;
		}
		$output = [
				'results'=>$res,
				'pagination' => [
						'more'=> $pagination
				]
		];
		$output = [
				'results'=>$res
		];
		return $this->response = $this->getJson($output);
	}
}

?>