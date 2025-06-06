<?php
namespace WeppsExtensions\Reviews;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Connect\ConnectWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

/**
 * @var \Smarty $smarty
 */

class RequestReviewsWepps extends RequestWepps {
	public function request($action="") {
		
		switch ($action) {
			case 'add':
				/*
				 * Проверка формы
				 */
				$this->errors = [];
				$this->errors['name'] = ValidatorWepps::isNotEmpty($this->get['name'], "Не заполнено");
				$this->errors['email'] = ValidatorWepps::isEmail($this->get['email'], "Неверно заполнено");
				$this->errors['comment'] = ValidatorWepps::isNotEmpty($this->get['comment'], "Не заполнено");
				$outer = ValidatorWepps::setFormErrorsIndicate($this->errors, $this->get['form']);
				$this->assign('jscode', $outer['html']);
				if ($outer['Co']==0) {
					/*
					 * Добавление отзыва
					 */
					$row = [];
					$row['Name'] = $this->get['name'];
					$row['RAuthorEmail'] = $this->get['email'];
					$row['RText'] = $this->get['comment'];
					$row['TableName'] = $this->get['tablename'];
					$row['TableNameId'] = $this->get['tablenameid'];
					$row['RPage'] = $this->get['pageurl'];
					$row['RRating'] = $this->get['rate'];
					$row['RDate'] = date("Y-m-d H:i:s");
					ConnectWepps::$instance->insert("Reviews", $row);
					/*
					 * Вывод сообщения о добавлении отзыва
					 */
					$arr = ValidatorWepps::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
					$this->assign('jscode', $arr['html']);
				}
				$this->tpl = "RequestReviewsAdd.tpl";
				break;
			default:
				$this->tpl = "RequestReviewsDefault.tpl";
				break;
		}
	}
}
$request = new RequestReviewsWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>