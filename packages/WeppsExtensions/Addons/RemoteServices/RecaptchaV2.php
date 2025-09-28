<?php
namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;
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
	public function check($response) : array
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
		#<script src=\"https://www.google.com/recaptcha/api.js?onload=onloadRecapchaV2&render=explicit\" async defer></script>
		$this->headers->js("https://www.google.com/recaptcha/api.js?onload=onloadRecapchaV2&render=explicit");
		$html = "
		<label class=\"pps w_input\"><input type=\"text\" name=\"{$recaptchadub}\"  style=\"display:none;\"/></label>
		<div class=\"g-recaptcha\" id=\"{$id}\"></div>
		<script>
		var onloadRecapchaV2 = function() {
			{$gwidgetId} = grecaptcha.render('{$id}', {
				'sitekey' : '" . $this->sitekey . "'
			});
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