<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsCore\Data;
use WeppsAdmin\Lists\Lists;
use WeppsExtensions\Addons\Messages\Mail\Mail;
use WeppsExtensions\Addons\Messages\Telegram\Telegram;
use WeppsExtensions\Cart\CartUtils;

class BotTest extends Bot {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function setHashes() {
		$sql = "select * from s_PropertiesValues;";
		$res = Connect::$instance->fetch($sql);
		$str = "";
		foreach ($res as $value) {
			$list = $value['TableName'];
			$field = $value['TableNameField'];
			$id = $value['TableNameId'];
			$prop = $value['Name'];
			$v = $value['PValue'];
			$hash = md5($list . $field . $id . $prop . $v);
			$str .= "update s_PropertiesValues set HashValue='{$hash}' where Id='{$value['Id']}';\n";
		}
		Utils::debug($str,2);
	}
	public function telegram() {
		/*
		 * Как узнать chat_id - находим бот в ТГ. Пишем ему /start
		 * Далее через getUpdates находим chat_id пользователя (который ввел /start)
		 * В группе - добавляем Бота в группу и тоже пишем /start и далее проверяем getUpdates
		 * chat_id группы начинается с минуса
		 */
		$tg = new Telegram();
		$response = $tg->send(Connect::$projectServices['telegram']['dev'],'Hello from Bot (Telegram)');
		Utils::debug($response,2);
	}
	public function mail() {
		$mail = new Mail("html");
		$mail->mail(Connect::$projectInfo['email'], "Test subject", "Test text");
	}
	public function testDB() {
		$row = [
				'Name' => 'TEST1',
				'Text' => 'test text',
				'Priority'=>0
		];
		$settings = [];
		$settings = [
				'Text' => [
						'fn' => 'md5(:Text)'
				]
		];
		$t = Connect::$instance->insert('DataTbls',$row,$settings);
		
		Utils::debug($t,21);

		/* $obj = new Data("Products");
		$res = $obj->fetch('',20,1);	
		Utils::debug($obj->paginator,21); */
		$obj = new Data("DataTbls");
		$row = [
				'Name' => 'Add Test2',
				'Text' => 'Text Test',
				'DDate' => '2024-09-08 00:00:01',
				'MyProperties' => '1',
				'RemoteTest' => '3',
				'Alias' => 'AliasTest',
		];
		$id = $obj->add($row,1);
		Utils::debug($id,31);
		
		$obj = new Data("Products");
		$obj->setParams([
				'Брюки Armani Junior'
		]);
		$res = $obj->fetch("t.DisplayOff=0 and t.Name = ?",5,1);
		Utils::debug($res,21);
		
		/* $obj = new Data("DataTbls");
		$t = $obj->add([
				'Name'=>'TEST1',
				'BTest'=>'test text',
		],[
				'BTest'=>['fn'=>'compress(:BTest)']
		]); */
		
		
		$t = Lists::setListItem(
				"DataTbls",
				55,
				[
						'w_path'=>'list',
						'GUID' => "",
						'BTest' => 'test text'
				]
				);
		Utils::debug($t,21);
	}
	public function cli() {
		$this->cli->text("simle text");
		$this->cli->success("success text");
		$this->cli->warning("warning text");
		$this->cli->error("error text");
	}
	public function password() {
		$password = "1541";
		$hash = password_hash($password,PASSWORD_BCRYPT);
		$this->cli->info($hash);
		exit();
		#$password2 = "556";
		#$hash = password_hash($password2,PASSWORD_BCRYPT);
		if (password_verify($password,$hash)) {
			#Utils::debug('ok',31);
			$this->cli->success('ok');
			exit();
		}
		#Utils::debug('fail',31);
		$this->cli->error('fail');
		exit();
	}
	public function testOrderText() {
		/*
		 * EText - комментарий
		 */
		$cartUtils = new CartUtils();
		$id = 41;
		$sql = "select o.Id,o.Name,o.JData,o.JPositions,o.Address,o.PostalCode,o.Phone,o.Email,e.EText from Orders o
		left join OrdersEvents e on e.OrderId=o.Id and e.EType='Msg' where o.Id=? order by e.Id";
		$res = Connect::$instance->fetch($sql,[$id])[0];
		$str = $cartUtils->getOrderText($res);
		
		$sql = "update Orders set OText=? where Id=?";
		Connect::$instance->query($sql,[$str,$id]);
		#Utils::debug($str,21);
	}
}
?>