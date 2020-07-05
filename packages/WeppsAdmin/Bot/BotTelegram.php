<?
namespace WeppsAdmin\Bot;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\UtilsWepps;
use Curl\Curl;
use WeppsExtensions\Mail\MailWepps;

class BotTelegramWepps extends BotWepps {
	private $token;
	private $proxy;
	public $parent = 0;
	public function __construct() {
		parent::__construct();
		$this->token = "bot" . ConnectWepps::$projectServices['telegram']['token'];
		$this->proxy = ConnectWepps::$projectServices['telegram']['proxy'];
	}
	
	public function test() {
		$curl = new Curl();
		if (!empty($this->proxy)) {
			$curl->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			$curl->setOpt(CURLOPT_PROXY, $this->proxy);
		}
		//$str = "https://api.telegram.org/{$this->token}/getUpdates";
		$str = "https://api.telegram.org/{$this->token}/sendMessage?chat_id=306118632&text=Привет от BOT через прокси";
		$res = $curl->get($str);
		
		//UtilsWepps::debugf($res->response,1);
		//UtilsWepps::debugf($str,1);
		
	}
	
	public function test2() {
		/*
		 * Как узнать chat_id - находим бот в ТГ. Пишем ему /start
		 * Далее через getUpdates находим chat_id пользователя (который ввел /start)
		 * В группе - добавляем Бота в группу и тоже пишем /start и далее проверяем getUpdates
		 * chat_id группы начинается с минуса
		 */
		$mail = new MailWepps();
		$data = [
				'chat_id' => 306118632,
				'text' => 'Привет от BOT (MailWepps) через прокси'
		];
		$tg = $mail->telergam("sendMessage",$data);
		UtilsWepps::debugf($tg);
		
	}
	public function call($method = "getUpdates", array $data = null) {
		
	}
}
?>