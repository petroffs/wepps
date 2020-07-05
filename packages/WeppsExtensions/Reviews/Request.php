<?
namespace PPSExtensions\Reviews;

use PPS\Utils\RequestPPS;
use PPS\Validator\ValidatorPPS;
use PPS\Utils\UtilsPPS;
use PPS\Connect\ConnectPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';


if (!session_start()) session_start();

class RequestReviewsPPS extends RequestPPS {
	public function request($action="") {
		
		switch ($action) {
			case 'add':
				
				/**
				 * Проверка формы
				 */
				$errors = array();
				$errors['name'] = ValidatorPPS::isNotEmpty($this->get['name'], "Не заполнено");
				$errors['email'] = ValidatorPPS::isEmail($this->get['email'], "Неверно заполнено");
				$errors['comment'] = ValidatorPPS::isNotEmpty($this->get['comment'], "Не заполнено");
				
				$outer = ValidatorPPS::setFormErrorsIndicate($errors, $this->get['form']);
				$this->assign('jscode', $outer['Out']);
				
				if ($outer['Co']==0) {
					
					/**
					 * Добавление отзыва
					 */
					$row = array();
					$row['Name'] = $this->get['name'];
					$row['RAuthorEmail'] = $this->get['email'];
					$row['RText'] = $this->get['comment'];
					$row['TableName'] = $this->get['tablename'];
					$row['TableNameId'] = $this->get['tablenameid'];
					$row['RPage'] = $this->get['pageurl'];
					$row['RRating'] = $this->get['rate'];
					$row['RDate'] = date("Y-m-d H:i:s");
					ConnectPPS::$instance->insert("Reviews", $row);
					
					/**
					 * Вывод сообщения о добавлении отзыва
					 */
					$arr = ValidatorPPS::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
					$this->assign('jscode', $arr['Out']);
				}
				
				
				$this->tpl = "RequestReviewsAdd.tpl";
				break;
			default:
				$this->tpl = "RequestReviewsDefault.tpl";
				break;
		}
	}
}
$request = new RequestReviewsPPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>