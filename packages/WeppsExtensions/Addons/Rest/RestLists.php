<?php
namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\Utils;
use WeppsCore\Exception;

class RestLists extends Rest {
	public $parent = 0;
	public function __construct($settings=[]) {
		parent::__construct($settings);
	}
	public function getLists($list='',$field='') {
		$text = @$this->get['search'];
		$page = @$this->get['page'];
		if (empty($page)) {
			$page = 1;
		}
		
		/*
		 * Условие поля извлечь из схемы
		 */
		$sql = "select * from s_ConfigFields where TableName='{$list}' and Id='{$field}'";
		$res = Connect::$instance->fetch($sql);
		if (empty($res)) {
			Exception::error(404);
		}
		$ex = explode('::', $res[0]['Type']);
		$list = $ex[1];
		$field = $ex[2];
		$condition = $ex[3];
		
		if (mb_strlen($text)>=0) {
			$condition .= " and t.{$field} like '%{$text}%'";
		}
		$limit = 10;
		$offset = ($page-1)*$limit;
		$sql = "select t.Id id,concat(t.{$field},' (',t.Id,')') text from $list t where $condition order by t.{$field} limit $offset,$limit";
		$res = Connect::$instance->fetch($sql);
		#Utils::debug($res,1);
		$pagination = false;
		if (!empty($res)) {
			$pagination = true;
		}
		$output = [
				'results'=>$res,
				'pagination' => [
						'more'=> $pagination
				]
		];
		echo $this->getJson($output);
		exit();
	}
	public function getTest() {
		$output = [
				[
						'id'=>1,
						'title'=>'test 1',
						'test'=>'test get'
				]
		];
		$this->status = 200;
		$this->setResponse($output);
	}
	public function setTest() {
		$output = [
				[
						'id'=>1,
						'title'=>'test 1',
						'test'=>'test set'
				],
				[
						'id'=>2,
						'title'=>'test 2',
						'test'=>'test set'
				],
		];
		$this->status = 200;
		$this->setResponse($output);
	}
	public function removeTest() {
		$output = [
					'field'=>$this->settings['param'],
					'value'=>$this->settings['paramValue'],
					'removed'=>'ok',
		];
		$this->status = 200;
		$this->setResponse($output);
	}
	public function cliTest() {
		$output = [
				'message'=>'ok'
		];
		$this->status = 200;
		$this->setResponse($output,false);
	}
}

?>