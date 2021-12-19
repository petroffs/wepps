<?
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
	private function __construct($projectSettings = array()) {
		try {
			$connectionString = "{$projectSettings['DB']['driver']}:host={$projectSettings['DB']['host']};dbname={$projectSettings['DB']['dbname']};charset={$projectSettings['DB']['charset']}";
			//$db = new PDO ( $connectionString, $projectSettings ['DB'] ['user'], $projectSettings ['DB'] ['password'] );
			$db = new PDO ( $connectionString, $projectSettings ['DB'] ['user'], $projectSettings ['DB'] ['password'] , array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
			if ($projectSettings ['Dev'] ['debug'] == 1)
				$db->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			self::$db = &$db;
			self::$projectInfo = $projectSettings['Info'];
			self::$projectDev = $projectSettings['Dev'];
			self::$projectDB = $projectSettings['DB'];
			self::$projectServices = $projectSettings['Services'];
		} catch ( \Exception $e ) {
			echo "sql connect error.";
			exit();
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
	public function fetch($sql, $params = array(), $group = '') {
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
	public function insert($tableName,$row) {
		$arr = UtilsWepps::getQuery($row);
		//$sql = "insert into $tableName (Priority) select max(Priority)+1 from $tableName";
		$sql = "insert into $tableName (Priority) select round((max(Priority)+5)/5)*5 from $tableName";
		self::$instance->query($sql);
		$id = self::$db->lastInsertId();
		$sql = "update $tableName set {$arr['update']} where Id='{$id}'";
		self::$instance->query($sql);
		return $id;
	}
	public function selectRegx ($id) {
		return "(,+|^)".$id."(,+|$)";
	}
	public function close($exit=1) {
		self::$db = null;
		if ($exit==1) exit();
	}
}
?>
