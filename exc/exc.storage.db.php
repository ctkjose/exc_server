<?php
namespace exc\storage {

class db extends \exc\core\base {
	public static $cnx = [];
	public $driver = 'generic';
	public $ready = false;
	public $debug_sql = '';
	public $errorMsg='';
	protected $st = ['dbname'=>'', 'query'=>[]];
	protected $cs=[];
	protected $reuseds=null;
	

	function reuse($ds){
		$this->reuseds = $ds;
	}
	function error(){
		return $this->errorMsg;
	}
	public static function initialize($options){
		//error_log("@exc.db.initialize()");
		//reasg_dev_dump($options, 'db.options');

		if(!is_array($options)) return;
		if(isset($options['connections']) && is_array($options['connections'])){
			foreach($options['connections'] as $connName => $cfg){
				self::addConnectionOption($connName, $cfg);
			}
		}

		//reasg_dev_dump(self::$cnx, 'db.cnx');
	}
	public static function addConnectionOption($connName, $cfg){
		if(is_string($cfg)){
			$cfg = self::parseConnectionString($cfg);
		}
		if(!is_array($cfg)) return false;
		if(!isset($cfg['driver'])) return false;

		$cfg['db'] = null;
		$cfg['name'] = $connName;

		if(array_key_exists($connName, self::$cnx)) return false;
		self::$cnx[$connName] = $cfg;

		
	}
	public static function connection($tag){
		if(!array_key_exists($tag, self::$cnx)) return null;
		
		$e = self::$cnx[$tag];
		if(!is_null($e['db'])){
			return $e['db'];
		}

		$driver = $e['driver'];
		$cls = '\\exc\\storage\\dbDriver' . $driver;
		if(!class_exists($cls) ){
			self::loadDriver($driver);
			if(!class_exists($cls)) return null;
		}

		try{
			$db = new $cls();
		}catch(Exception $err){
			return null;
		}

		if(is_null($db)) return null;
		$db->open($e, $e['username'], $e['password']);

		self::$cnx[$tag]['db'] = $db;
		return $db;
	}
	public static function loadDriver($driver){
		$cls = '\\exc\\storage\\dbDriver' . $driver;
		if(!class_exists($driver) ){
			$f = EXC_DIRECTORY . 'storage/exc.storage.' . strtolower($driver) . '.php';
			if(file_exists($f)) include_once($f);
		}
	}
	public static function parseConnectionString($args){
		///N: Takes anything and attemps to build a connection hash

		$conn = ['driver' => 'mysql'];
		$driver = 'mysql';
		$c = count($args);
		if ($c == 3){
			$conn['host'] = $args[0];
			$conn['user'] = $args[1];
			$conn['password'] = $args[2];
		}elseif ($c == 4){
			$conn['host'] = $args[1];
			$conn['user'] = $args[2];
			$conn['password'] = $args[3];
			$conn['dbname'] = $args[0];
		}elseif ($c == 1){
			//is a connection string
			$cs = $args[0];
			if((strpos($cs,'host') !== false) || (strpos($cs,'Host') !== false) || (strpos($cs,'sqlite:') !== false) ){
				//is plain text
				$csData = $cs;
			}else{
				//must be encrypted
				//$csData = rea_storage_decrypt_cs($cs);
				//$csData = xorDecrypt(rot13ex(substr($cs, 6)), rotEncode(substr($cs, 0, 6)));
			}

			$pairs = explode(';', $csData);

			if(strpos($pairs[0], ':') !== false){
				list($driver, $s) = explode(':', $pairs[0]);
				$pairs[0] = $s;
			}

			$conn['driver'] = $driver;
			if($driver == 'sqlite'){
				$conn['dbname'] = $pairs[0];
				return $conn;
			}

			foreach($pairs as $pair){
				list($name, $value) = explode('=', $pair);
				$name = strtolower(trim($name));
				$conn[$name] = $value;
			}


			//lets convert any ADO, OLE DB, OBDC artifacts that the user may have used
			$compatability_transforms = ['server' => 'host', 'dsn' => 'host' , 'data source' => 'host', 'initial catalog' => 'dbname',
			'user'=>'username', 'uid' => 'username', 'pwd'=>'password','pass'=>'password', 'user id' => 'username', 'port'=> 'port'];

			foreach($compatability_transforms as $k => $v){
				if(array_key_exists($k, $conn)) $conn[$v] = $conn[$k];
			}
		}

		return $conn;
	}
}
class dbDataSet  {
	public $owner=null;
	protected $_owner_st=null;
	public $atEnd=false;
	public $results=null;
	public $fields=[];
	public function __destruct(){
		if(is_null($this->owner)) return;
		$this->owner->dsDispose($this);
		unset($this->fields);
	}
	public function close(){
		if(is_null($this->owner)) return;
		$this->owner->dsDispose($this);
		unset($this->fields);
		$this->fields = [];
	}
	public function attach($owner){
		$this->atEnd = false;
		$this->owner = $owner;
	}
	public function setOwnerState($state){
		$this->_owner_st = $state;
	}
	public function update(){
		if(is_null($this->_owner_st)) return false;
		if(is_null($this->owner)) return false;
		if(count($this->fields) == 0) return false;
		$this->owner->where($this->_owner_st['query']['where'])->update($this->_owner_st['query']['from'], $this->fields);
	}
	public function toDateTime($v){
		if(!is_numeric($v) || (strlen($v) == 0) ) $v = time();
		if( is_string($v) && (strpos($v,'-') !== false) ) return date('Y-m-d H:i:s', strtotime($v));
		//error_log("toDateTime($v)");
		return date('Y-m-d H:i:s', $v);
	}
	public function json($fld){
		$a = [];
		$v = $this->fields[$fld];
		if( strlen($v)==0 ) return $a;
		$v = json_decode($v, true);
		return $v;
	}
	public function arrayField($fld, $default=[]){
		$a = [];
		$v = $this->fields[$fld];
		if( (strlen($v)==0) || ( (substr($v,0,1)!='[') && (substr($v,0,5)!='array')) ) return $default;
		@eval('$a=' . $this->fields[$fld] . ';');
		return $a;
	}
	public function dateField($fld){
		if( ( $this->fields[$fld] == null) || ( $this->fields[$fld] == 'null') ) return null;
		return strtotime( $this->fields[$fld] );
	}
	public function rewind(){
		if(is_null($this->owner)) return false;
		return $this->owner->dsRewind($this);
	}
	public function atEnd(){
		return $this->atEnd;
	}
	public function next(){
		if($this->read()) return $this->fields;
		return null;
	}
	public function iterate($callback){
		while($this->read()){
			call_user_func_array( $callback, [$this, $this->fields] );
		}
	}
	public function read(){
		if($this->atEnd) return false;
		if(is_null($this->owner)) return false;

		$this->atEnd = true;

		$this->owner->dsFetchAssoc($this);
		return !$this->atEnd;
	}
	public function readDictionary($name='name',$value='value'){
		$d = ['values'=>[], 'keys'=> null];
		while($this->read()){
			$f = $this->fields;
			$d['values'][$f[$name]] = $f[$value];

			if($d['keys'] == null){
				unset($f[$name]); unset($f[$value]);
				$d['keys'] = $f;
			}
		}
		return $d;
	}
}

trait dbQueryBuilder { ///NST:MARK:CLASS:dbQueryBuilder
	//supports mysql, maria, postgresql
	public function initQueryBuilder(){
		if(isset($this->queryBuilder)) return;
		$this->resetQueryBuilder();
	}
	public function resetQueryBuilder(){
		$this->queryBuilder = ['where'=>'', 'order'=>'', 'select'=>'*', 'from'=>'', 'limit'=>'','offset'=>'','group'=>''];
	}
	public function buildSQLSelect(){
		$sql = "SELECT " . $this->queryBuilder['select'] . ' FROM ' . $this->queryBuilder['from'];
		if(strlen($this->queryBuilder['where'])>0){
			$sql.= ' WHERE ' . $this->queryBuilder['where'];
		}

		if(strlen($this->queryBuilder['order'])>0){
			$sql.= ' ORDER BY ' . $this->queryBuilder['order'];
		}
		if(strlen($this->queryBuilder['limit'])>0){
			$sql.= ' LIMIT ' . $this->queryBuilder['limit'];
		}
		if(strlen($this->queryBuilder['offset'])>0){
			$sql.= ' OFFSET ' . $this->queryBuilder['offset'];
		}

		$this->resetQueryBuilder();
		return $sql;

	}
	public function buildFieldName($k){
		$nop = "=";
		$lk = trim(strtolower($k));
		$ops = array('!','>', '=','<', 'like', 'in', 'is', 'is not');
		$lk_len = strlen($lk);
		foreach($ops as $op){
			$op_len = strlen($op);
			if($op_len > 1) {
				$op = " $op";
				$op_len++;
			}

			if($lk_len < $op_len) continue;

			if(strpos($lk, $op, $lk_len - $op_len)) {
				$nop = ' ';
				break;
			}
		}
		return $k . $nop;
	}

	public function buildWhere($args){
		$c = count($args);
		$f = [];

		$search = '';
		if($c == 2){
			$search.= '(' . $this->buildFieldName($args[0]) . $this->escape($args[1]) . ')';
		}elseif($c == 1){
			$a = $args[0];
			if(is_array($a)){
				foreach($a as $k => $v){
					$f[] = $this->buildFieldName($k) . $this->escape($v);
				}
				$search.= '(' . implode(' and ', $f) . ')';
			}elseif(is_string($a)){
				$search.= '(' . $a . ')';
			}
		}elseif(($c % 2) == 0){
			for($i = 0; $i <= $c-1; $i+=2){
				$k = $args[$i];
				$v = $args[$i+1];
				$f[] = 	$this->buildFieldName($k) . $this->escape($v);
			}

			$search.= '(' . implode(' and ', $f) . ')';
		}
		return $search;
	}

	public function where() {
		
		if( strlen( $this->queryBuilder['where'] ) > 0 ){
			$this->queryBuilder['where'] .= ' and ';
		}

		$args = func_get_args();
		$this->queryBuilder['where'].= $this->buildWhere($args);

		return $this;
	}
	public function from() {
		
		$args = func_get_args();
		$c = count($args);

		if( strlen( $this->queryBuilder['from'] ) > 0 ){
			$this->queryBuilder['from'] .= ', ';
		}

		$this->queryBuilder['from'] .= implode(', ', $args);
		return $this;
	}
	function limit($limit, $offset=null) {
		$this->queryBuilder['limit'] = $limit;
		if(!is_null($offset)) {
			$this->queryBuilder['offset'] = $offset;
		}
		return $this;
	}
	public function select(){
		$this->queryBuilder['select'] = '*';

		$args = func_get_args();
		$c = count($args);

		$flgSanitize = true;
		$last_val = $args[$c-1];
		if(is_bool($last_val)) { //check is last value is bool flag to sanitize
			$c--;
			$flgSanitize = $last_val;
		}

		$f = [];
		if($c == 1){
			$a = $args[0];
			if(is_array($a)){
				foreach($a as $k => $v){
					$f[] = ($flgSanitize) ? $this->sanitizeSelect($v) : $v;
				}
			}elseif(is_string($a)){
				$f[] = $a;
			}
		}elseif($c > 1){
			for($i = 0; $i< $c;$i++){
				$f[] = ($do_sanitize) ? $this->sanitizeSelect($args[$i]) : $args[$i];
			}
		}

		$this->queryBuilder['select'] = implode(", ", $f);
		return $this;
	}

	private function sanitizeSelect($fld) {
		$fld = $this->sanitizeValue($fld);
		$pcs = explode(".", $fld);

		$clean = array();
		foreach($pcs as $pc) {
			if($pc != "*") {
				$clean[] = "`{$pc}`";
			} else {
				$clean[] = "*";
			}
		}
		return implode(".", $clean);
	}
	public function escape($v){
		$rule = array('\x00'=>'\\x00', '\n' => '\\n', '\r' => '\\r',
					  '\x1a' => '\\x1a', '"' => '\"', '\0' => '\\0',
					  '\Z' => '\\Z');
		if(is_string($v)){
			$s = $v;

			$s = str_replace("\\",'\\\\',$s);
			$s = str_replace("'","\\'",$s);
			$s = str_replace(";","\;",$s);
			$s = "'" . $s . "'";

		}elseif(is_array($v)){
			$s = var_export($v, true);
			//print "s=$s";

			$s = str_replace("\\","\\\\",$s);
			$s = str_replace("'","\\'",$s);
			$s = str_replace(";","\;",$s);
			$s = "'" . $s . "'";
		}elseif(is_bool($v)){
			$s = ($v === false) ? 0 : 1;
		}elseif(is_null($v)){
			$s = 'NULL';
		}else{
			return $v;
		}
		return $s;
	}
	public function sanitizeValue($v){
		$s = $v;
		$s = str_replace(";","\;",$s);
		$s = str_replace("\'",chr(1),$s);
		$s = str_replace("'","\\'",$s);
		$s = str_replace( chr(1), "\\'",$s);

		return $s;
	}
}


}
?>