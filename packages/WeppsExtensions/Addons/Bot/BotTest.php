<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Mail\MailWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Lists\ListsWepps;

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
		UtilsWepps::debugf($str);
	}
	public function telegram() {
		/*
		 * Как узнать chat_id - находим бот в ТГ. Пишем ему /start
		 * Далее через getUpdates находим chat_id пользователя (который ввел /start)
		 * В группе - добавляем Бота в группу и тоже пишем /start и далее проверяем getUpdates
		 * chat_id группы начинается с минуса
		 */
		$mail = new MailWepps();
		$data = [
				'chat_id' => ConnectWepps::$projectServices['telegram']['dev'],
				'text' => 'Hello from Bot (MailWepps)'
		];
		$tg = $mail->telegram("sendMessage",$data);
		UtilsWepps::debugf($tg);
	}
	public function mail() {
		$mail = new MailWepps("html");
		$mail->mail(ConnectWepps::$projectInfo['email'], "Ваш новый пароль", "Test text");
		$output = $mail->getContent(false);
		file_put_contents(__DIR__ . '/files/mail.html', $output);
		echo $output;
	}
	public function testDB() {
		exit();
		$row = [
				'Name' => 'TEST1',
				'BTest' => 'test text',
				'Priority'=>0
		];
		$settings = [
				'BTest' => [
						'fn' => 'compress(:BTest)'
				]
		];
		$t = ConnectWepps::$instance->insert('DataTbls',$row,$settings);
		UtilsWepps::debugf($t,1);
		
		
		$obj = new DataWepps("DataTbls");
		$t = $obj->add([
				'Name'=>'TEST1',
				'BTest'=>'test text',
		],['BTest'=>['fn'=>'compress(:BTest)']]);
		
		
		$t = ListsWepps::setListItem(
				"DataTbls",
				55,
				[
						'pps_path'=>'list',
						'GUID' => "",
						'BTest' => 'test text'
				]
				);
		UtilsWepps::debugf($t,1);
	}
}
?>