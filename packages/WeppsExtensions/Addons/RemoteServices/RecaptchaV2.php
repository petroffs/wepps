<?php
namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsCore\TemplateHeaders;
use WeppsCore\Connect;

class RecaptchaV2 extends RemoteServices
{

	private $sitekey;
	private $secret;
	private $headers;

	public function __construct(TemplateHeaders $headers)
	{
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->headers = $headers;
		$this->sitekey = Connect::$projectServices['recaptcha']['sitekey'];
		$this->secret = Connect::$projectServices['recaptcha']['secret'];
	}

	/*
	 * Получить ответ V2
	 */
	public function check($response): array
	{
		$url = "https://www.google.com/recaptcha/api/siteverify";
		$body = [
			'secret' => $this->secret,
			'response' => $response
		];
		$this->curl = new Curl();
		$this->cache = 0;
		return $this->getResponse($url, $body);
	}

	public function getSitekey()
	{
		return $this->sitekey;
	}

	public function render($gwidgetId = 'gwidgetId', $id = 'greacptchaV2', $recaptchadub = 'recaptchadub')
	{
		if (empty($this->sitekey)) {
			return '<div style="color: var(--color-attention);">Ошибка: не задан ключ сайта (site key) для reCAPTCHA V2</div>';
		}
		#<script src=\"https://www.google.com/recaptcha/api.js?onload=onloadRecapchaV2&render=explicit\" async defer></script>
		$this->headers->js("https://www.google.com/recaptcha/api.js?onload=onloadRecapchaV2&render=explicit");
		$html = "
		<div class=\"g-recaptcha\" id=\"{$id}\"></div>
		<label class=\"w_label w_input\"><input type=\"text\" name=\"{$recaptchadub}\"  style=\"display:none;\"/></label>
		<div id=\"{$id}_error\" class=\"recaptcha-error\" style=\"display:none; color: var(--color-attention); margin-top: var(--s);\"></div>
		<script>
		var onloadRecapchaV2 = function() {
			try {
				{$gwidgetId} = grecaptcha.render('{$id}', {
					'sitekey' : '" . $this->sitekey . "',
					'error-callback': function(error) {
						var errorElement = document.getElementById('{$id}_error');
						if (error) {
							if (error.includes('invalid-site-key')) {
								errorElement.textContent = 'Ошибка: Неверный ключ сайта (site key)';
							} else {
								errorElement.textContent = 'Ошибка reCAPTCHA: ' + error;
							}
							errorElement.style.display = 'block';
						} else {
							errorElement.style.display = 'none';
						}
					}
				});
			} catch (e) {
				var errorElement = document.getElementById('{$id}_error');
				errorElement.textContent = 'Ошибка инициализации reCAPTCHA: ' + e.message;
				errorElement.style.display = 'block';
				console.error('reCAPTCHA initialization error:', e);
			}
		};
		</script>
		";
		return $html;
	}
	public function reset($gwidgetId = 'gwidgetId')
	{
		$html = "
        <script>
            grecaptcha.reset($gwidgetId);
        </script>
        ";
		return $html;
	}
}