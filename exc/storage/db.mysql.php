<?php
/*
DB Diver for MYSQL/Maria
Requires: msqli
*/
namespace exc\storage {

class dbDriverMYSQL extends \exc\storage\db {
	use \exc\storage\dbQueryBuilder;

	public $link=null;
	public function __construct(){
		$this->driver = 'MYSQL';
		$this->initQueryBuilder();
	}

	public function open($cfg, $user, $pass){
		$this->ready = false;
		$this->cs = $cfg;
		$this->link = new \mysqli();

		$port = (isset($cfg['port']) && (strlen($cfg['port'])>0)) ? 1*$cfg['port'] : 3306;
		$dbname = (isset($cfg['dbname']) && (strlen($cfg['dbname'])>0)) ? $cfg['dbname'] : "excstore";

		$this->link->real_connect($cfg['host'],$user,$pass, $dbname, $port);


		if (mysqli_connect_errno()) {
			$this->errorMsg = mysqli_connect_error();
			return;
		}
		$this->link->set_charset("utf8");

		if(!$this->link->select_db($dbname) ){
			$this->errorMsg = mysqli_connect_error();
		}

		$this->ready = true;
	}
	public function selectDB($n){
		$this->st['dbname'] = $n;
		$this->link->select_db($n);
	}
	public function error(){
		return $this->link->error;
	}
	public function insert($table, $values){
		$sql = "INSERT INTO {$table} SET ";
		$f = array();
		if(is_object($values)){
			$a = get_object_vars($values);
		}elseif(is_array($values)){
			$a = $values;
		}else{
			$a = [$values];
		}

		foreach($a as $k => $v){
			if($k[0] != '`') {
				$k = "`" . $this->sanitizeValue($k) . "`";
			}
			$f[] = "{$k}=" . $this->escape($v);
		}
		$sql.= implode(",", $f);
		$ok = $this->execute($sql);

		return $ok;
	}
	
	public function update($table, $values){
		$sql = "UPDATE {$table} SET ";
		$f = array();
		if(is_object($values)){
			$a = get_object_vars($values);
		}elseif(is_array($values)){
			$a = $values;
		}else{
			$a = [$values];
		}

		foreach($a as $k => $v){
			if($k[0] != '`') {
				$k = "`" . $this->sanitizeValue($k) . "`";
			}
			$f[] = "{$k}=" . $this->escape($v);
		}
		$sql.= implode(",", $f);
		if(strlen($this->queryBuilder['where']) > 0){
			$sql.= " WHERE " . $this->queryBuilder['where'];
		}

		$this->resetQueryBuilder();
		$ok = $this->execute($sql);

		return $ok;
	}
	public function delete($table, $values = null){
		$sql = "DELETE FROM {$table}";

		if(!empty($values)){
			$this->where($values);
		}

		if(strlen($this->queryBuilder['where']) > 0){
			$sql.= " WHERE " . $this->queryBuilder['where'];
		}

		$this->resetQueryBuilder();
		$this->execute($sql);

		return $this;
	}
	public function execute($sql){
		$this->debug_sql = $sql;

		if(!$this->ready) return;

		$ok = $this->link->query($sql);
		if (!$ok) {
			$this->errorMsg = $this->link->error;
		}

		return $ok;
	}

	function get($values = null, $limit = null, $offset = null, $orderby = null){
		
		if(!empty($values)){
			$this->from($values);
		}

		if(!empty($orderby) ){
			$this->queryBuilder['order'] = $orderby;
		}

		if(!is_null($limit)){
			$this->queryBuilder['limit'] = $limit;
		}
		if(!is_null($offset)){
			$this->queryBuilder['offset'] = $offset;
		}

		if(!is_null($this->reuseds)){
			$this->st['query'] = $this->queryBuilder;
			$this->reuseds->setOwnerState($this->st);
		}
		$sql = $this->buildSQLSelect();

		//print $sql;
		return $this->query($sql, $this->reuseds);
	}
	public function getInsertId(){
		return $this->link->insert_id;
	}
	public function query($sql, $ds=null){

		$r = (is_null($ds) ? new dbDataSet() : $ds);
		$r->attach($this);

		$this->debug_sql = $sql;
		if ($result = $this->link->query($sql)){
			$this->errorMsg = '';
			$r->results = $result;
			return $r;
		}

		$this->errorMsg = $this->link->error;
		return $r;
	}

	public function dsDispose($ds){
		if(isset($ds->results) && is_object($ds->results) ){
			$ds->results->close();
			$ds->results = null;
		}
	}
	public function dsFetchAssoc($ds){
		if(is_null($ds->results)) return;
		$ds->atEnd = !($ds->fields = $ds->results->fetch_assoc());

		return $ds->atEnd;
	}
	public function dsRewind($ds){
		if(is_null($ds->results)) return;
		$ds->results->data_seek(0);
	}

}

}
?>