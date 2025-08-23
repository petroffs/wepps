<?php
namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;

class RecaptchaV2Wepps extends RemoteServicesWepps
{

	private $sitekey;
	private $secret;
	private $headers;

	public function __construct(TemplateHeadersWepps $headers)
	{
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->headers = $headers;
		$this->sitekey = ConnectWepps::$projectServices['recaptcha']['sitekey'];
		$this->secret = ConnectWepps::$projectServices['recaptcha']['secret'];
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
		<label class=\"pps pps_input\"><input type=\"text\" name=\"{$recaptchadub}\"  style=\"display:none;\"/></label>
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