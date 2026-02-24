<?php
namespace WeppsExtensions\Addons\Messages\Telegram;

use WeppsCore\Connect;
use Curl\Curl;
use WeppsCore\Utils;

/**
 * Модуль отправки сообщений через Telegram Bot API для платформы Wepps.
 *
 * Поддерживает HTML-разметку, SOCKS5-прокси и режим отладки
 * с перенаправлением сообщений на аккаунт разработчика.
 *
 * Настройки берутся из конфигурации проекта:
 * - `services.telegram.token` — токен бота
 * - `services.telegram.proxy` — SOCKS5-прокси (опционально)
 * - `services.telegram.dev`   — chat_id разработчика для режима отладки
 *
 * @package WeppsExtensions\Addons\Messages\Telegram
 */
class Telegram
{
    /** @var string Токен бота в формате 'bot{TOKEN}' */
    private $token;

    /** @var Curl HTTP-клиент для запросов к Telegram Bot API */
    private $curl;

    /**
     * Инициализирует токен, HTTP-клиент и SOCKS5-прокси (если задан в конфигурации).
     */
    public function __construct() {
        $this->token = "bot" . Connect::$projectServices['telegram']['token'];
        $this->curl = new Curl();
        if (!empty($proxy = Connect::$projectServices['telegram']['proxy'])) {
            $this->curl->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            $this->curl->setOpt(CURLOPT_PROXY, $proxy);
        }
    }
    /**
     * Отправляет текстовое сообщение в указанный чат.
     *
     * В режиме отладки chat_id подменяется на аккаунт разработчика.
     * Поддерживает HTML-разметку (parse_mode=html).
     *
     * @param int    $chat ID чата или пользователя Telegram
     * @param string $text Текст сообщения (допускается HTML)
     * @return array{url: string, response: mixed} URL запроса и ответ Telegram API
     */
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
    /**
     * Получает последние обновления (входящие сообщения) бота через getUpdates.
     *
     * Используется для получения chat_id новых пользователей или отладки вебхуков.
     *
     * @return array{url: string, response: mixed} URL запроса и ответ Telegram API
     */
    public function updates()
    {
        $url = "https://api.telegram.org/{$this->token}/getUpdates";
        $res = $this->curl->get($url);
        return [
            'url' => $url,
            'response' => $res->response
        ];
    }
}