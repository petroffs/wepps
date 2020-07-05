<?
class yandex_disk {
	var $ch;
	var $ansver,$info;
	var $headers=array();

	function __construct($user,$pass)
	{
		$this->headers[] = "Authorization: Basic " . base64_encode($user . ":" . $pass);

		$this->ch = curl_init ();
		curl_setopt ($this->ch , CURLOPT_USERAGENT , "MicroWap Agent v0.1 beta (c) Temp (http://microwap.ru)");

		curl_setopt ($this->ch, CURLOPT_HEADER , 0);
		curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt ($this->ch, CURLOPT_BINARYTRANSFER , 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
	}

	//-----------------//
	function my_exec()
	{
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
		$this->ansver = curl_exec($this->ch);
		$this->info = curl_getinfo($this->ch);
		curl_close($this->ch);
	}

	//--------получить файл---------//
	function get($url)
	{
		curl_setopt ($this->ch, CURLOPT_URL , 'https://webdav.yandex.ru'.$url);
		$this->my_exec();
		if ($this->info['http_code'] != '200') return FALSE; // Error!
		return $this->ansver;

	}
	//--------удалить файл---------//
	function delete($url)
	{
		curl_setopt ($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt ($this->ch, CURLOPT_URL , 'https://webdav.yandex.ru'.$url);
		$this->my_exec();
		if ($this->info['http_code'] != '204') return FALSE; // Error!
		return TRUE;
	}
	//-------закачать файл----------//
	function put($file,$url)
	{
		curl_setopt ($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt ($this->ch, CURLOPT_URL , 'https://webdav.yandex.ru'.$url);
		curl_setopt ($this->ch, CURLOPT_POSTFIELDS, file_get_contents($file));

		$this->my_exec();
		if ($this->info['http_code'] != '201') return FALSE; // Error!

		return TRUE;
	}
	//-------Список файлов в директории----------//
	function ls($dir = '')
	{
		$this->headers[] ='Depth: 1';
		curl_setopt ($this->ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
		curl_setopt ($this->ch, CURLOPT_URL , 'https://webdav.yandex.ru'.$dir);
		$this->my_exec();

		if ($this->info['http_code'] != '207') return FALSE; // Error!


		$xml = simplexml_load_string($this->ansver);
		$xml->registerXPathNamespace('d','urn:DAV');
		$res=array();

		foreach ($xml->xpath('/d:multistatus/d:response/d:href') as $v)
		{
			$res[] = urldecode($v);
		}

		return $res;
	}
	//-----------------//
	function mkdir($dir)
	{
		curl_setopt ($this->ch, CURLOPT_CUSTOMREQUEST, 'MKCOL');
		curl_setopt ($this->ch, CURLOPT_URL , 'https://webdav.yandex.ru'.$dir);
		$this->my_exec();
		if ($this->info['http_code'] != '201') return FALSE; // Error!
		return TRUE;
	}
	//-----------------//
}

?>