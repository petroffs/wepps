<?php
namespace WeppsCore\Connect;

use PDO;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\MemcachedWepps;
use WeppsCore\Utils\UtilsWepps;

class ConnectWepps {
	public static $db;
	public static $instance;
	public static $projectInfo;
	public static $projectDev;
	public static $projectDB;
	public static $projectServices;
	public static $projectData;
	public $count;
	private $sth;
	private $memcached;
	private function __construct($projectSettings = array()) {
		self::$projectInfo = $projectSettings['Info'];
		self::$projectDev = $projectSettings['Dev'];
		self::$projectDB = $projectSettings['DB'];
		self::$projectServices = $projectSettings['Services'];
		self::$projectData = [];
		try {
			$connectionString = "{$projectSettings['DB']['driver']}:host={$projectSettings['DB']['host']}:{$projectSettings['DB']['port']};dbname={$projectSettings['DB']['dbname']};charset={$projectSettings['DB']['charset']}";
			$db = new PDO ( $connectionString, $projectSettings['DB']['user'],$projectSettings['DB']['password']);
			if ($projectSettings['Dev']['debug']==1) {
				$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			}
			self::$db = &$db;
		} catch (\Exception $e) {
			$s = 0;
			if (php_sapi_name()=='cli') {
				$s = 3;	
			}
			if (ConnectWepps::$projectDev['debug']==1) {
				UtilsWepps::debug($e->getMessage(),$s);
			} else {
				UtilsWepps::debug("connect error",$s);
			}
			exit();
		}
		$this->memcached = new MemcachedWepps();
	}
	function __destruct() {
		self::$db = null;
	}
	public static function getInstance($projectSettings) {
		if (empty ( self::$instance )) {
			self::$instance = new ConnectWepps( $projectSettings );
		}
		return self::$instance;
	}
	public function fetch($sql, $params=[], $group='') {
		$this->count++;
		try {
			$isCache = 0;
			$cacheExpire = ConnectWepps::$projectServices['memcached']['expire'];
			if (strstr($sql,'join ')) {
				$isCache = 1;
			}
			$key = md5($sql.implode(';',$params));
			if (!empty($this->memcached) && $isCache==1 && !empty($res = $this->memcached->get($key))) {
				return $res;
			}
			$sth = self::$db->prepare($sql);
			$sth->execute ($params);
			if ($group == 'group') {
				$res = $sth->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
			} else {
				$res = $sth->fetchAll(PDO::FETCH_ASSOC);
			}
			if (!empty($this->memcached) && $isCache==1) {
				$this->memcached->set($key,$res);
			}
			return $res;
		} catch (\Exception $e) {
			ExceptionWepps::display($e);
		}
	}
	public function query(string $sql,array $params=[]) {
		$this->count++;
		try {
			if (empty($params)) {
				$state = self::$db->query($sql);
				return $state->rowCount();
			} else {
				$this->sth = self::$db->prepare($sql);
				$this->sth->execute($params);
				return $this->sth->rowCount();
			}
		} catch (\Exception $e) {
			ExceptionWepps::display($e);
		}
	}
	public function insert($tableName,$row,$settings=[]) {
		$arr = self::prepare($row);
		$sql = "insert ignore into $tableName (Priority) select round((max(Priority)+5)/5)*5 from $tableName on duplicate key update Id=last_insert_id(`Id`)";	
		self::$instance->query($sql);
		$id = self::$db->lastInsertId();
		if ((int)$id!=0) {
			$arr = self::prepare($row,$settings);
			$sql = "update ignore $tableName set {$arr['update']} where Id='{$id}'";
			self::query($sql,$arr['row']);
		}
		$sql = "update $tableName set {$arr['update']} where Id='{$id}'";
		self::$instance->query($sql,$arr['row']);
		return $id;
	}
	public function selectRegx(string $id='') : string {
		return (string) '(,+|^)'.$id.'(,+|$)';
	}
	public function close($exit=1) {
		self::$db = null;
		if ($exit==1) {
			exit();
		}
	}
	public function prepare($row=[],$settings=[]) {
		$insert = $insert2 = $update = $select = "";
		$keys = array_keys($row);
		$insert = '('.implode(',', $keys).') values ';
		foreach ($keys as $value) {
			if (!empty($settings[$value]['fn'])) {
				$insert2 .= "{$settings[$value]['fn']},";
				$update .= "{$value} = {$settings[$value]['fn']}, ";
				$select .= "{$settings[$value]['fn']} {$value}, ";
			} else {
				$insert2 .= ":{$value},";
				$update .= "{$value} = :{$value}, ";
				$select .= ":{$value} {$value}, ";
			}
			if (!empty($settings[$value]['rm'])) {
				unset($row[$value]);
			}
		}
		$insert .= '('.trim($insert2,',').')';
		$update = trim($update,', ');
		$select = trim($select,', ');
		$output = [
				"insert" => $insert,
				"update" => $update,
				"select" => $select,
				'row'=>$row
		];
		return $output;
	}
	public function in(array $in) : string {
		return str_repeat('?,', count($in) - 1) . '?';
	}
	public function transaction(callable $func, array $args) {
		ConnectWepps::$db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
		try {
			ConnectWepps::$db->beginTransaction();
			if (ConnectWepps::$db->inTransaction()) {
				$func($args);
			}
			ConnectWepps::$db->commit();
			ConnectWepps::$db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
			return true;
		} catch (\Exception $e) {
			ConnectWepps::$db->rollBack();
			echo "Error. See debug.conf";
			UtilsWepps::debug($e,21);
			return false;
		}
	}
	public function cached($isActive='auto') {
		$this->memcached = new MemcachedWepps($isActive);
	}
}