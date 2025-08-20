<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsExtensions\Addons\Messages\Mail\MailWepps;
use WeppsExtensions\Addons\Messages\Telegram\TelegramWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class BotTestWepps extends BotWepps {
	public $parent = 0;
	public function __construct() {
		parent::__construct();
	}
	public function setHashes() {
		$sql = "select * from s_PropertiesValues;";
		$res = ConnectWepps::$instance->fetch($sql);
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
		UtilsWepps::debug($str,2);
	}
	public function telegram() {
		/*
		 * Как узнать chat_id - находим бот в ТГ. Пишем ему /start
		 * Далее через getUpdates находим chat_id пользователя (который ввел /start)
		 * В группе - добавляем Бота в группу и тоже пишем /start и далее проверяем getUpdates
		 * chat_id группы начинается с минуса
		 */
		$tg = new TelegramWepps();
		$response = $tg->send(ConnectWepps::$projectServices['telegram']['dev'],'Hello from Bot (TelegramWepps)');
		UtilsWepps::debug($response,2);
	}
	public function mail() {
		$mail = new MailWepps("html");
		$mail->mail(ConnectWepps::$projectInfo['email'], "Test subject", "Test text");
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
		$t = ConnectWepps::$instance->insert('DataTbls',$row,$settings);
		
		UtilsWepps::debug($t,21);

		/* $obj = new DataWepps("Products");
		$res = $obj->fetch('',20,1);	
		UtilsWepps::debug($obj->paginator,21); */
		$obj = new DataWepps("DataTbls");
		$row = [
				'Name' => 'Add Test2',
				'Text' => 'Text Test',
				'DDate' => '2024-09-08 00:00:01',
				'MyProperties' => '1',
				'RemoteTest' => '3',
				'Alias' => 'AliasTest',
		];
		$id = $obj->add($row,1);
		UtilsWepps::debug($id,31);
		
		$obj = new DataWepps("Products");
		$obj->setParams([
				'Брюки Armani Junior'
		]);
		$res = $obj->fetch("t.DisplayOff=0 and t.Name = ?",5,1);
		UtilsWepps::debug($res,21);
		
		/* $obj = new DataWepps("DataTbls");
		$t = $obj->add([
				'Name'=>'TEST1',
				'BTest'=>'test text',
		],[
				'BTest'=>['fn'=>'compress(:BTest)']
		]); */
		
		
		$t = ListsWepps::setListItem(
				"DataTbls",
				55,
				[
						'pps_path'=>'list',
						'GUID' => "",
						'BTest' => 'test text'
				]
				);
		UtilsWepps::debug($t,21);
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
			#UtilsWepps::debug('ok',31);
			$this->cli->success('ok');
			exit();
		}
		#UtilsWepps::debug('fail',31);
		$this->cli->error('fail');
		exit();
	}
	public function testOrderText() {
		/*
		 * EText - комментарий
		 */
		$cartUtils = new CartUtilsWepps();
		$id = 41;
		$sql = "select o.Id,o.Name,o.JData,o.JPositions,o.Address,o.PostalCode,o.Phone,o.Email,e.EText from Orders o
		left join OrdersEvents e on e.OrderId=o.Id and e.EType='Msg' where o.Id=? order by e.Id";
		$res = ConnectWepps::$instance->fetch($sql,[$id])[0];
		$str = $cartUtils->getOrderText($res);
		
		$sql = "update Orders set OText=? where Id=?";
		ConnectWepps::$instance->query($sql,[$str,$id]);
		#UtilsWepps::debug($str,21);
	}
}
?>