<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Validator;
use WeppsCore\Connect;

/**
 * @var \Smarty $smarty
 */

class RequestReviews extends Request {
	public function request($action="") {
		
		switch ($action) {
			case 'add':
				/*
				 * Проверка формы
				 */
				$this->errors = [];
				$this->errors['name'] = Validator::isNotEmpty($this->get['name'], "Не заполнено");
				$this->errors['email'] = Validator::isEmail($this->get['email'], "Неверно заполнено");
				$this->errors['comment'] = Validator::isNotEmpty($this->get['comment'], "Не заполнено");
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
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
					Connect::$instance->insert("Reviews", $row);
					/*
					 * Вывод сообщения о добавлении отзыва
					 */
					$arr = Validator::setFormSuccess("Ваше сообщение отправлено. Спасибо", $this->get['form']);
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
$request = new RequestReviews($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);