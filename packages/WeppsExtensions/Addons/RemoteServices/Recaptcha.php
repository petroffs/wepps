<?php

namespace WeppsExtensions\Addons\RemoteServices;

use Curl\Curl;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;

class RecaptchaWepps extends RemoteServicesWepps {
	
	private $sitekey;
	private $secret;
	
	public function __construct($settings=[]) {
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->settings = $settings;
		
		$this->sitekey = ConnectWepps::$projectServices['recaptcha']['sitekey'];
		$this->secret = ConnectWepps::$projectServices['recaptcha']['secret'];
	}

	/*
	 * Получить ответ V2
	 */
	public function getRecaptchaV2($recaptcha) {
		$url = "https://www.google.com/recaptcha/api/siteverify";
		$body = array(
		    'secret' => $this->secret,
		    'response' => $recaptcha
		);
		$this->curl = new Curl();
		$this->cache = 0;
		return $this->getResponse($url,$body);
	}
	
	public function getSitekey() {
		return $this->sitekey;
	}
	
	public function getCallback($id='greacptchaV2') {
		$html = "
		<script>
		var onloadRecapchaV2 = function() {
			grecaptcha.render('$id', {
				'sitekey' : '".$this->sitekey."'
			});
		};
		</script>
		<script src=\"https://www.google.com/recaptcha/api.js?onload=onloadRecapchaV2&render=explicit\" async defer></script>
		";
		return $html;
	}
	public function getCallbackReset() {
	    $html = "
        <script>
            grecaptcha.reset();
        </script>
        ";
	    return $html;
	}
}
?>