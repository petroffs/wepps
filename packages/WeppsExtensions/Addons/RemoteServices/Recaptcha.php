<?php

namespace PPSExtensions\Addons\RemoteServices;

use Curl\Curl;
use PPS\Utils\UtilsPPS;

class RecaptchaPPS extends RemoteServicesPPS {
	private $sitekey = '6LdYpawUAAAAAKrtzBJFkum0OifxfwRNvNESvHhv';
	private $secret = '6LdYpawUAAAAAPAu2TWj5ZS2Eo1Ka5ixnHrQsdUg';

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