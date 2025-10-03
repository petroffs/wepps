<?php
namespace WeppsExtensions\Addons\Bot;

use WeppsCore\Connect;
use WeppsCore\Data;

class BotFeeds extends Bot {
	public $parent = 0;
	private $data;
	private $date;
	public function __construct() {
		parent::__construct();
	}
	
	public function setSitemap() {
		$arr = [];
		
		/*
		 * Структура
		 */
		$sql = "select Id,Name,NameMenu,if(UrlMenu!='',UrlMenu,Url) as Url from s_Navigator where DisplayOff=0 and (NGroup!=1 or (Id=1))";
		$res = Connect::$instance->fetch($sql);
		foreach ($res as $value) {
			$arr[$value['Url']] = "<url><loc>https://{$this->host}{$value['Url']}</loc></url>";
		}
		
		/*
		 * Данные в структуре
		 */
		$obj = new Data("News");
		$obj->setFields("Name,Alias,Id");
		$obj->setConcat ( "concat('/novosti/',if(Alias!='',Alias,Id),'.html') as Url" );
		$res = $obj->fetchmini("DisplayOff=0",50000,1);
		foreach ($res as $value) {
			$arr[$value['Url']] = "<url><loc>https://{$this->host}{$value['Url']}</loc></url>";
		}
		
		/*
		 $sql = "select Id,Name,concat('/blog/',if(Alias!='',Alias,Id),'.html') as Url from Blog where DisplayOff=0";
		 $res = Connect::$instance->fetch($sql);
		 foreach ($res as $value) {
		 $arr[$value['Url']] = "<url><loc>http://izburg.ru{$value['Url']}</loc></url>";
		 }
		 */
		$str = trim("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
						<urlset
									xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
									xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
									xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9
									http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">
						","\t");
		$str .= implode("\n", $arr);
		$str .= "\n</urlset>";
		$this->data = new \DOMDocument('1.0');
		$this->data->preserveWhiteSpace = false;
		$this->data->formatOutput = true;
		$this->data->loadXML($str);
		$this->data->save(dirname(__FILE__) .'/../../../../sitemap.xml');
	}
}