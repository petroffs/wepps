<?php
namespace WeppsExtensions\Addons\Messages\Telegram;

use WeppsCore\Connect;
use Curl\Curl;
use WeppsCore\Utils;

class Telegram
{
    private $token;
    private $curl;
    public function __construct() {
        $this->token = "bot" . Connect::$projectServices['telegram']['token'];
        $this->curl = new Curl();
        if (!empty($proxy = Connect::$projectServices['telegram']['proxy'])) {
            $this->curl->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            $this->curl->setOpt(CURLOPT_PROXY, $proxy);
        }
    }
    public function send(int $chat,string $text)
    {
        if (Connect::$projectDev['debug']==1) {
            $chat = Connect::$projectServices['telegram']['dev'];
        }
        $data = [
				'chat_id' => $chat,
				'text' => $text
		];
        $params = http_build_query($data);
        if (!empty($params)) {
            $params = (string) "?" . $params . "&parse_mode=html";
        }
        $url = "https://api.telegram.org/{$this->token}/sendMessage{$params}";
        $res = $this->curl->get($url);
        return [
            'url' => $url,
            'response' => $res->response
        ];
    }
    public function updates($method = "getUpdates")
    {
        $url = "https://api.telegram.org/{$this->token}/getUpdates";
        $res = $this->curl->get($url);
        return [
            'url' => $url,
            'response' => $res->response
        ];
    }
}