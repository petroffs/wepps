<?php
namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsCore\Connect;

/**
 * SmartCaptcha wrapper for Yandex SmartCaptcha service
 *
 * Обеспечивает генерацию HTML/JS виджета SmartCaptcha, сброс виджета
 * и проверку ответа на стороне сервера через API проверки.
 *
 * @package WeppsExtensions\Addons\RemoteServices
 */
class SmartCaptcha extends RemoteServices
{
    /** @var string Site key для публичного рендера виджета */
    private $sitekey;

    /** @var string Секретный ключ для валидации токенов на сервере */
    private $secret;

    /** @var string DOM id контейнера для captcha-виджета */
    private $containerId;

    /** @var string DOM id виджета captcha */
    private $captchaId;

    /** @var string Honeypot поле (dub) для предотвращения ботов */
    private $captchadub;

    /**
     * Конструктор
     *
     * @param string $containerId DOM id контейнера для вставки виджета (по умолчанию 'captcha-container')
     * @param string $captchaId DOM id самого виджета (по умолчанию 'captcha-1')
     * @param string $captchadub Имя скрытого (honeypot) поля для ботов (по умолчанию 'smartcaptchadub')
     */
    public function __construct($containerId = 'captcha-container', $captchaId = 'captcha-1', $captchadub = 'smartcaptchadub')
    {
        $this->curl = new Curl();
        $this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
        $this->sitekey = Connect::$projectServices['smartcaptcha']['sitekey'];
        $this->secret = Connect::$projectServices['smartcaptcha']['secret'];
        $this->containerId = $containerId;
        $this->captchaId = $captchaId;
        $this->captchadub = $captchadub;
    }

    /**
     * Проверить ответ SmartCaptcha через API валидации
     *
     * Выполняет POST-запрос к сервису проверки с передачей секретного ключа
     * и полученного от клиента токена. Возвращает результат проверки
     * в том виде, как возвращает `getResponse` (обычно ассоциативный массив).
     *
     * @param string $response Токен, полученный от SmartCaptcha на клиенте
     * @return mixed Результат валидации (формат зависит от реализации getResponse)
     */
    public function check($response)
    {
        $url = "https://smartcaptcha.yandexcloud.net/validate";
        $body = array(
            'secret' => $this->secret,
            'token' => $response
        );
        $this->curl = new Curl();
        $this->cache = 0;
        return $this->getResponse($url, $body);
    }

    /**
     * Получить sitekey для рендера на клиенте
     *
     * @return string Site key, используемый в JavaScript для инициализации SmartCaptcha
     */
    public function getSitekey()
    {
        return $this->sitekey;
    }

    /**
     * Сгенерировать HTML и JavaScript для вставки виджета SmartCaptcha
     *
     * Возвращает строку с HTML, которая включает скрытые поля (honeypot и поле для ответа),
     * контейнер виджета и JS-код для загрузки скрипта SmartCaptcha и рендера.
     *
     * @return string HTML/JS для вставки на страницу
     */
    public function render()
    {
        $html = "
		<label class=\"w_label w_input\"><input type=\"text\" name=\"{$this->captchadub}\" style=\"display:none;\"/></label>
		<label class=\"w_label w_input\"><input type=\"text\" name=\"smartcaptcha-response\" id=\"{$this->captchaId}-response\" style=\"display:none;\"/></label>
		<div id=\"{$this->containerId}\" data-captcha-id=\"{$this->captchaId}\"><div id=\"{$this->captchaId}\"></div></div>
		<script>
		(function() {
			var renderCaptcha = function() {
				smartCaptcha.render('{$this->captchaId}', {
					sitekey: '{$this->sitekey}',
					hl: 'ru',
					domain: window.location.hostname,
					callback: function(token) { $('#{$this->captchaId}-response').val(token); }
				});
			};
			if (typeof smartCaptcha === 'undefined') {
				$.getScript('https://smartcaptcha.yandexcloud.net/captcha.js', renderCaptcha);
			} else {
				renderCaptcha();
			}
		})();
		</script>
		";
        return $html;
    }

    /**
     * Сбросить/реинициализировать виджет на странице
     *
     * Возвращает JS-код, который заново подставляет контейнер и вызывает
     * render() для перерендера виджета. Используется, например, при AJAX-форме.
     *
     * @return string JS-код для рендеринга/сброса виджета
     */
    public function reset()
    {
        $html = "
        <script>
            if ($('#{$this->containerId}').length) {
                $('#{$this->containerId}').html('<div id=\"{$this->captchaId}\"></div>').attr('data-captcha-id', '{$this->captchaId}');
                var renderCaptcha = function() {
                    smartCaptcha.render('{$this->captchaId}', {
                        sitekey: '{$this->sitekey}',
                        hl: 'ru',
                        domain: window.location.hostname,
                        callback: function(token) { $('#{$this->captchaId}-response').val(token); }
                    });
                };
                if (typeof smartCaptcha === 'undefined') {
                    $.getScript('https://smartcaptcha.yandexcloud.net/captcha.js', renderCaptcha);
                } else {
                    renderCaptcha();
                }
            }
        </script>
        ";
        return $html;
    }

    /**
     * Получить селектор элемента для вывода ошибки
     *
     * Метод получает селектор/id дополнительного DOM-элемента, в который
     * можно выводить сообщения об ошибке капчи (например "#captcha-error").
     *
     * @return string
     */
    public function captchadub(): string
    {
        return $this->captchadub;
    }
}