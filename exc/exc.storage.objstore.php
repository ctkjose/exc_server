<?php
/*
 *
 /sis/students/RUM/2016S1/802129999
 */

namespace exc\storage {
class store extends \exc\core\base {
	public static $engines=[];
	public static $engineMaster=null;
	public static $enginesForCopy=[];
	public static function initialize($options){

		require_once(__DIR__ . "/exc.io.store.db.php");
		require_once(__DIR__ . "/exc.io.store.db.mysql.php");
		require_once(__DIR__ . "/exc.io.store.odp.mysql.php");

		//reasg_dev_dump($options, 'store_options');

		//error_log("here-------------------------");
		//$a = ['m'=> 'The MySQL mascot is a dolphin named "Sakila"'];
		//$a = [];
		//error_log(count($a));
		//error_log(json_encode($a));

		if(isset($options['engines']) && is_array($options['engines'])){
			foreach($options['engines'] as $def){
				self::addEngine($def);
			}
		}
		return;
		//reasg_dev_dump(self::$engines, 'engines');
		error_log(".");
		error_log("TEST STORE START ================================================================================");
		error_log(".");

/*
		$db = self::$engines[0];

		$ds = $db->where("keym", "d6ad4de7fb09dbc511942f3bbd4f0083")->get("store_keys");
		reasg_dev_dump($ds, '$ds');
		reasg_dev_dump($db, '$db');

		while($ds->read()){
			reasg_dev_dump($ds->fields, '$ds->fields');
		}
*/
		$store = self::store("/emr/records/");



		//$store->value("fname", "Jose", "lname", "Cuevas");
		//$store->value(["fname"=>"Jose", "lname"=>"Cuevas", "dob"=>"1977SEP06"]);


		$store->addIndexField('pid', "location");
		$store->addIndexField('conditions');
		$store->addIndexValue('%location%/2016');
		$store->addIndexField(['location','conditions', 'pid']);

		$store->pid = "1320";
		$store->location = 'AR';
		$store->fname = "Jose";
		$store->mname = "L";
		$store->lname = "Cuevas";
		$store->dob = "1977SEP06";
		$store->conditions = [
			'DEF01'=> ['somefield'=>'somedata1'],
			'DEF19'=> ['somefield'=>'somedata2'],
		];

		$store->in->pathologies->create("S-12349-2016");


		$items = $store->in->pathologies->where("pid", "1320")->find();
		foreach($items as $record){
			reasg_dev_dump($record, 'record');
			error_log("NAME=". $record->fname . "");
		}

		error_log(".");
		error_log("TEST STORE DONE ================================================================================");
		error_log(".");
	}
	public static function addEngine($def){
		//reasg_dev_dump($def, 'store_engine');
		$cls = "\\exc\\io\\store\\storeDataProvider" . $def['engine'];
		if(!class_exists($cls)){
			error_log('[EXC] UNABLE TO LOAD STORE DATA PROVIDER ENGINE OF TYPE [' . $def['engine'] . ']');
			return false;
		}

		$role = (count(self::$engines)>0) ? 'mirror' : 'master';

		$cs = $def['connection'];
		$e = new $cls();
		$e->role = isset($def['role']) ? $def['role'] : $role;
		$e->open($cs);

		$e->flgUseDP =  (($e->role == 'master')||($e->role == 'mirror')) ? 'true': 'false';

		if(($e->role == 'master') && (is_null(self::$engineMaster))){
			self::$engineMaster = $e;
		}

		self::$engines[] = $e;
		if($e->flgUseDP){
			self::$enginesForCopy[] = $e;
		}

		return true;
	}
	public static function store($path = null){
		$st = new \exc\io\store\storeObject();
		if( is_string($path) ){
			$st->init($path);
		}
		return $st;
	}
	public static function objectFetchByUID($uid){

		$dp = self::$engineMaster;
		if(is_null($dp)){
			error_log('[EXC][STORE][objectFetchByUID] No master data provider available.');
			return [];
		}

		$ds = $dp->objectFetchByUID($uid);
		reasg_dev_dump($ds, 'objectFetchWithUID.ds');

		if($ds->read()){
			return self::objectCreateWithDS($ds);
		}

		return null;
	}
	public static function objectCreateWithDS($ds){


		$st = new \exc\io\store\storeObject();
		$st->loadObject($ds->fields['uid'], $ds->fields['path'], json_decode($ds->fields['data'],true));

		return $st;
	}
	
	public static function findItemsByKey($keys, $model ='\exc\io\store\storeGenericObject'){
		$dp = self::$engineMaster;
		if(is_null($dp)){
			error_log('[EXC][STORE][findItemsByKey] No master data provider available.');
			return [];
		}

		$items = $dp->objectFindByKey($keys);
		return $items;
	}
	public static function create($key, $values, $keys=[], $model ='\exc\io\store\storeGenericObject'){

		$r = [
			'uid'=> uniqid('exc'),
			'model'=> $model,
			'path'=> $key,
			'data'=> json_encode($values),
		];

		foreach(self::$enginesForCopy as $dp){
			$dp->objectInsert($r);
		}

		$k = [
			'uid'=> $r['uid'],
			'keym'=>'',
			'key'=>'',
			'model'=> $model,
		];

		foreach($keys as $kv){
			$k['key'] = $kv;
			$k['keym'] = md5($kv);
			foreach(self::$enginesForCopy as $dp){
				$dp->keysInsert($k);
			}
		}

		//reasg_dev_dump($keys, "record_keys");
		return $r['uid'];
	}

}
class storeObjectCollection implements \Iterator {
	public $owner = null;
	private $_itemsIndex=0;
	private $_flgReturnUID = false;
	public function __get($n){
		if($n == 'uid'){
			$this->_flgReturnUID=true;
		}elseif($n == 'objects'){
			$this->_flgReturnUID=false;
		}

		return $this;
	}
	//iterator interfase
	public function rewind(){
		$this->_itemsIndex=0;
	}
	public function current(){
		$uid = $this->owner->_items[$this->_itemsIndex];
		if($this->_flgReturnUID){
			return $uid;
		}

		return \exc\io\store\manager::objectFetchByUID($uid);
	}
	public function key(){
		return $this->_itemsIndex;
	}
	public function next(){
		return ++$this->_itemsIndex;
	}
	public function valid(){
		return isset($this->owner->_items[$this->_itemsIndex]);
	}
}
class storeObject extends \exc\core\base {

	private $uid=null;
	private $_path='';
	private $_pathbase='';
	private $_values = [];
	private $_state = 0;
	private $_keys=[];
	public $_items=[];
	private $_whereKeys=[];

	const STATE_NONE=0;
	const STATE_INPATH=1;
	const STATE_WHERE=2;
	const STATE_RECORD=3;
	public function init($path=""){
		$this->_path = $path;
		$this->_pathbase = $path;
	}
	public function loadObject($uid, $path, $values){
		$this->uid = $uid;
		$this->_path = $path;
		$this->_pathbase = $path;

		$this->_values = $values;

		$this->_state = self::STATE_RECORD;
	}
	public function getIndexKeys(){
		$p = $this->_path;
		$p.= (substr($p,-1,1) != '/') ? '/' : '';

		$keys = [];

		foreach($this->_keys as $e){
			$kv = $e['v'];
			if($e['type'] == 'field'){
				 $kv = $p . '[' . $kv . '=%' . $kv . '%]';
			}elseif($e['type'] == 'value'){
				 $kv = $p . $kv;
			}

			$array_keys = [];
			foreach($this->_values as $k => $v){
				$k1 = '%' . $k . '%';
				if(strpos($kv, $k1) === false) continue;

				if(is_array($v)){
					$j = array_keys($v);
					foreach($j as $v){
						$array_keys[] = [$k1,$v];
					}
					continue;
				}

				$v1 = (is_string($v) || is_numeric($v) ) ? $v : (is_bool($v) ? ($v ? 'true':'false' ) : 'NOTAV');
				$kv = str_replace($k1, $v1, $kv);
			}


			if(count($array_keys) > 0){
				foreach($array_keys as $ke){
					$s = str_replace($ke[0], $ke[1], $kv);
					$keys[] = $s;
				}
			}else{
				$keys[] = $kv;
			}
		}
		//reasg_dev_dump($array_keys, '$array_keys');

		return $keys;
	}
	public function addIndexValue($value){
		$this->_keys[] = ['type'=>'value', 'v'=>$value];
		return $this;
	}
	public function addIndexField(){
		$args = func_get_args();
		$c =count($args);
		if($c == 0) return;

		//if(($c==1) && is_array($args[0])){
		//	$args = $args[0];
		//}

		foreach($args as $n){
			if(is_array($n)){
				$v = '';
				foreach($n as $n1){
					$v.= '[' . $n1 . '=%' . $n1 . '%]';
				}
				$this->_keys[] = ['type'=>'value', 'v'=>$v];
				continue;
			}
			$this->_keys[] = ['type'=>'field', 'v'=>$n];
		}
		return $this;
	}
	public function addIndexPath($path){
		$this->_keys[] = ['type'=>'path', 'v'=>$path];
		return $this;
	}
	public function load($id){
		$dp = \exc\io\store\manager::$engines[0];


	}

	public function find(){
		$p = $this->_path;
		$p.= ( (substr($p,-1,1) != '/') ? '/' : '');

		$pk = join('', $this->_whereKeys);
		$p.= $pk;

		//reasg_dev_dump($this->_whereKeys, 'where_keys');
		//reasg_dev_dump($p, 'path');

		$this->_items = \exc\io\storage\store::findItemsByKey([$p]);
		//reasg_dev_dump($this->_items, 'items_matched');

		$this->_state = self::STATE_NONE;
		$this->_whereKeys=[];

		$collection = new storeObjectCollection();
		$collection->owner = $this;

		return $collection;
	}
	public function create($id, $values=null){
		$p = $this->_path;
		$p.= ( (substr($p,-1,1) != '/') ? '/' : '') . $id;

		if(is_array($values)){
			$this->_values = $values;
		}

		$this->uid = \exc\io\store\manager::create($p, $this->_values, $this->getIndexKeys() );

		$this->_state = self::STATE_NONE;

		return $this;
	}
	public function delete($id){
		if(is_null($this->uid)) return false;

	}
	public function set($n, $v){
		$args = func_get_args();
		$c = count($args);
		if(($c == 1) && is_array($args[0]) ){
			$this->_values = $args[0];
		}elseif(($c == 2) && is_string($args[0]) ){
			$this->_values[$args[0]] = $args[1];

		}elseif(($c % 2) == 0){
			for($i = 0; $i <= $c-1; $i+=2){
				$this->_values[$args[$i]] = $args[$i+1];
			}
		}
	}
	public function __set($n, $value){
		$this->_values[$n] = $value;
	}
	public function __get($n){
		if($this->_state == self::STATE_RECORD){
			if( isset($this->_values[$n]) ){
				return $this->_values[$n];
			}
			return null;
		}
		if($this->_state == self::STATE_INPATH){
			$this->addPath($n);
			return $this;
		}
		if($this->_state == self::STATE_WHERE){
			$args = func_get_args();
			$this->addWhereKeys($args);
			return $this;
		}

		if($this->_state == self::STATE_NONE){
			if($n == "in"){
				$this->_path = $this->_pathbase;
				$this->_state = self::STATE_INPATH;
				return $this;
			}
			if($n == "where1"){
				$this->_whereKeys=[];
				$this->_state = self::STATE_WHERE;
				return $this;
			}
		}
		return $this;
	}
	public function where(){
		$args = func_get_args();
		$this->addWhereKeys($args);
		return $this;
	}
	public function addWhereKeys($values){
		$c = count($values);
		$w = '';

		if($c == 2){
			$w = '[' . $values[0] . '=' . $values[1] . ']';
			$this->_whereKeys[] = $w;
		}elseif($c == 1){
			$a = func_get_arg(0);
			if(is_array($a)){
				foreach($a as $k => $v){
					$w = '[' . $k . '=' . $v . ']';
					$this->_whereKeys[] = $w;
				}
			}elseif(is_string($a)){
				$this->_whereKeys[] = $a;
			}
		}elseif(($c % 2) == 0){
			for($i = 0; $i <= $c-1; $i+=2){
				$w = '[' . $values[$i] . '=' . $values[$i+1] . ']';
				$this->_whereKeys[] = $w;
			}
		}
	}
	public function addPath($n){
		$this->_path.= ( (substr($this->_path,-1,1) != '/') ? '/' : '') . $n . '/';
		error_log("storeObject.path=[" . $this->_path . "]");
	}
}

class storeObjectDataProvider extends \exc\core\base {
	public $ready = false;
	public $errorMsg = '';

	public function open($cs){
		$this->ready = false;
	}
	public function ready(){
		return true;
	}
	public function keysInsert($o){
	}
	public function objectInsert($o){
	}
	public function objectFindByKey($keys){
		return [];
	}
}




}
?>