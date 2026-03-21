<?php
namespace WeppsExtensions\Addons\Messages\Telegram;

use WeppsCore\Connect;
use Curl\Curl;
use WeppsCore\Tasks;
use WeppsExtensions\Addons\Messages\Mail\Mail;

class Telegram
{
    private $token;
    private $curl;
    private $timeout;

    public function __construct() {
        $this->token = "bot" . Connect::$projectServices['telegram']['token'];
        $this->curl = new Curl();
        $this->timeout = Connect::$projectServices['telegram']['timeout'] ?? 10;
        
        $this->configureProxy();
        $this->curl->setOpt(CURLOPT_TIMEOUT, (int)$this->timeout);
    }

    /**
     * Настройка прокси для curl
     * @return void
     */
    private function configureProxy(): void {
        $proxy = Connect::$projectServices['telegram']['proxy'] ?? [];
        if (empty($proxy['host']) || empty($proxy['port'])) {
            return;
        }

        $proxyString = $proxy['host'] . ':' . $proxy['port'];
        $proxyTypeConst = $this->mapProxyType(strtolower($proxy['type'] ?? 'socks5'));
        
        $this->curl->setOpt(CURLOPT_PROXYTYPE, $proxyTypeConst);
        $this->curl->setOpt(CURLOPT_PROXY, $proxyString);
        
        if (!empty($proxy['username']) && !empty($proxy['password'])) {
            $this->curl->setOpt(CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);
        }
    }

    /**
     * Маппирование типа прокси на CURL константу
     * @param string $type
     * @return int
     */
    private function mapProxyType(string $type): int {
        return match ($type) {
            'socks4' => defined('CURLPROXY_SOCKS4') ? CURLPROXY_SOCKS4 : 4,
            'socks4a' => defined('CURLPROXY_SOCKS4A') ? CURLPROXY_SOCKS4A : 6,
            'socks5_hostname' => defined('CURLPROXY_SOCKS5_HOSTNAME') ? CURLPROXY_SOCKS5_HOSTNAME : 7,
            default => CURLPROXY_SOCKS5,
        };
    }
    /**
     * Отправить сообщение в Telegram
     * @param int $chat ID чата
     * @param string $text Текст сообщения
     * @param string $parseMode Режим парсинга (html, markdown, etc.)
     * @param array $replyMarkup Разметка ответа (кнопки, инлайн-клавиатура)
     * @return array Результат запроса
     */
    public function send(int $chat, string $text, string $parseMode = 'html', array $replyMarkup = []): array {
        if (Connect::$projectDev['debug'] == 1) {
            $chat = Connect::$projectServices['telegram']['dev'];
        }

        $data = [
            'chat_id' => $chat,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        if (!empty($replyMarkup)) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        $url = "https://api.telegram.org/{$this->token}/sendMessage?" . http_build_query($data);
        $this->curl->get($url);
        
        return $this->handleResponse($url, 'send');
    }
    /**
     * Получить обновления из Telegram
     * @param string $method Метод API (по умолчанию getUpdates)
     * @return array Результат запроса
     */
    public function updates(string $method = "getUpdates"): array {
        $url = "https://api.telegram.org/{$this->token}/{$method}";
        $this->curl->get($url);
        
        return $this->handleResponse($url, 'updates');
    }

    /**
     * Обработка результата запроса к Telegram с обработкой ошибок
     * @param string $url URL запроса
     * @param string $method Метод запроса (send, updates)
     * @return array Результат запроса
     */
    private function handleResponse(string $url, string $method): array {
        $result = [
            'url' => $url,
            'response' => $this->curl->response,
            'http_status' => $this->curl->http_status_code,
            'error' => $this->curl->error,
            'error_code' => $this->curl->error_code,
            'error_message' => $this->curl->error_message,
        ];

        // Проверка на таймаут
        if ($this->curl->error && $this->curl->error_code === CURLE_OPERATION_TIMEDOUT) {
            $this->handleTimeout($url);
            return $result;
        }

        // Если есть любая другая ошибка curl (прокси, DNS, подключение и т.д.)
        if ($this->curl->error && $this->curl->error_code !== CURLE_OPERATION_TIMEDOUT) {
            $this->logErrorToTasks($url, $result);
            return $result;
        }

        // Если статус не успешный — логируем в Tasks
        if ($this->curl->http_status_code !== 200 && $this->curl->http_status_code !== 0) {
            $this->logErrorToTasks($url, $result);
        }

        return $result;
    }

    /**
     * Обработка ошибки таймаута
     * @param string $url URL запроса
     * @return void
     */
    private function handleTimeout(string $url): void {
        try {
            $tasks = new Tasks();
            $tasks->add('telegram_timeout', [
                'url' => $this->maskTokenInUrl($url),
                'timeout' => $this->timeout,
                'error' => $this->curl->error,
                'error_code' => $this->curl->error_code,
                'error_message' => $this->curl->error_message,
            ], date('Y-m-d H:i:s'), '', 'http');
        } catch (\Throwable $e) {
            // Логирование непредвиденных ошибок при записи в Tasks
        }
    }

    /**
     * Маскирование токена в URL перед логированием
     * @param string $url URL запроса
     * @return string URL с замаскированным токеном
     */
    private function maskTokenInUrl(string $url): string {
        return preg_replace('/bot\d+:[A-Za-z0-9_-]+/', '<TOKEN>', $url);
    }

    /**
     * Логирование ошибки в Tasks
     * @param string $url URL запроса
     * @param array $result Результат запроса
     * @return void
     */
    private function logErrorToTasks(string $url, array $result): void {
        try {
            $tasks = new Tasks();
            $tasks->add('telegram_error', [
                'url' => $this->maskTokenInUrl($url),
                'response' => $result['response'],
                'status' => $result['http_status'],
                'error' => $result['error'],
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ], date('Y-m-d H:i:s'), '', 'http');
        } catch (\Throwable $e) {
            // Логирование непредвиденных ошибок при записи в Tasks
        }
    }

	public function processTimeoutTask(array $request, Tasks $tasks): array
	{
		$jdata = json_decode($request['BRequest'], true);
		$text = "<b>Ошибка при отправке в Telegram</b><br/><br/>";
		$text .= "<strong>Тип ошибки:</strong> Таймаут<br/>";
		$text .= "<strong>Время истечения:</strong> " . $jdata['timeout'] . " сек.<br/>";
		$text .= "<strong>URL запроса:</strong> <code>" . htmlspecialchars($jdata['url']) . "</code><br/>";
		$text .= "<strong>Сообщение об ошибке:</strong> " . htmlspecialchars($jdata['error_message'] ?? 'N/A') . "<br/>";
		$text .= "<strong>Время ошибки:</strong> " . date('Y-m-d H:i:s') . "<br/>";
		$text .= "<br/>Пожалуйста, проверьте настройки прокси и соединение.";
		
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail(Connect::$projectDev['email'], "⚠️ Telegram Timeout Error", $text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage,
			'error_type' => 'timeout'
		];
		return $tasks->update($request['Id'], $response, 200);
	}

	public function processErrorTask(array $request, Tasks $tasks): array
	{
		$jdata = json_decode($request['BRequest'], true);
		$text = "<b>Ошибка при отправке в Telegram</b><br/><br/>";
		$text .= "<strong>HTTP Статус:</strong> " . $jdata['status'] . "<br/>";
		$text .= "<strong>URL запроса:</strong> <code>" . htmlspecialchars($jdata['url']) . "</code><br/>";
		$text .= "<strong>Ошибка curl:</strong> " . htmlspecialchars($jdata['error_message'] ?? 'N/A') . "<br/>";
		$text .= "<strong>Код ошибки:</strong> " . $jdata['error_code'] . "<br/>";
		$text .= "<br/><strong>Ответ от сервера:</strong><br/>";
		$text .= "<code>" . htmlspecialchars(substr($jdata['response'] ?? '', 0, 500)) . "</code><br/>";
		$text .= "<strong>Время ошибки:</strong> " . date('Y-m-d H:i:s') . "<br/>";
		$text .= "<br/>Проверьте логи и конфигурацию Telegram API.";
		
		$mail = new Mail('html');
		$outputMessage = "email fail";
		if ($mail->mail(Connect::$projectDev['email'], "⚠️ Telegram API Error", $text)) {
			$outputMessage = "email ok";
		}
		$response = [
			'message' => $outputMessage,
			'error_type' => 'api_error'
		];
		return $tasks->update($request['Id'], $response, 200);
	}
}