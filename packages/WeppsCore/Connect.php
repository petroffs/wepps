<?php
namespace WeppsCore\Connect;

use PDO;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;

class ConnectWepps {
	public static $db;
	public static $instance;
	public static $projectInfo;
	public static $projectDev;
	public static $projectDB;
	public static $projectServices;
	public $count;
	private $sth;
	private function __construct($projectSettings = array()) {
		self::$projectInfo = $projectSettings['Info'];
		self::$projectDev = $projectSettings['Dev'];
		self::$projectDB = $projectSettings['DB'];
		self::$projectServices = $projectSettings['Services'];
		try {
			$connectionString = "{$projectSettings['DB']['driver']}:host={$projectSettings['DB']['host']}:{$projectSettings['DB']['port']};dbname={$projectSettings['DB']['dbname']};charset={$projectSettings['DB']['charset']}";
			#UtilsWepps::debug($connectionString,1);
			$db = new PDO ( $connectionString, $projectSettings ['DB'] ['user'], $projectSettings ['DB'] ['password']);
			if ($projectSettings ['Dev'] ['debug'] == 1)
				$db->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			self::$db = &$db;
		} catch ( \Exception $e ) {
			ExceptionWepps::writeMessage ( $e );
		}
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
			$sth = self::$db->prepare ( $sql );
			$sth->execute ( $params );
			if ($group == 'group') {
				return $sth->fetchAll ( PDO::FETCH_ASSOC | PDO::FETCH_GROUP );
			} else {
				return $sth->fetchAll ( PDO::FETCH_ASSOC );
			}
		} catch ( \Exception $e ) {
			ExceptionWepps::logMessage( $e );
		}
	}
	public function query($sql,$params=[]) {
		$this->count++;
		try {
			if (empty($params)) {
				$state = self::$db->query ( $sql );
				return $state->rowCount();
			} else {
				$this->sth = self::$db->prepare ( $sql );
				$this->sth->execute( $params );
				return $this->sth->rowCount();
			}
		} catch ( \Exception $e ) {
			ExceptionWepps::writeMessage ( $e );
		}
	}
	public function insert($tableName,$row,$settings=[]) {
		$arr = UtilsWepps::getQuery($row);
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
	public function selectRegx ($id) {
		return "(,+|^)".$id."(,+|$)";
	}
	public function close($exit=1) {
		self::$db = null;
		if ($exit==1) exit();
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
}
?>
