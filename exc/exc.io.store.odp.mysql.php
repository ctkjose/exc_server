<?php
namespace exc\io\store {

class storeDataProviderMYSQL extends \exc\io\store\storeObjectDataProvider {
	use storeQueryBuilder;
	public $debug_sql = '';
	public $db = null;
	public function open($cs){
		$this->db = new \exc\io\store\storeDatabaseMYSQL();
		$this->db->open($cs,  $cs['username'], $cs['password']);
	}
	public function ready(){
		return $this->db->ready;
	}
	public function keysInsert($o){
		$this->db->insert("store_keys", $o);
	}
	public function objectInsert($o){
		$this->db->insert("store_objects", $o);
	}
	public function objectFetchByUID($uid){
		$ds = new storeDataSet();
		$ds->attach($this->db);

		$ds = $this->db->where('uid', $uid)->get('store_objects');
		//error_log("objectFetchByUID sql=" . $this->db->debug_sql);
		//reasg_dev_dump($ds, 'storeFetchWithUID.ds');
		return $ds;
	}
	public function objectFindByKey($keys){
		$items = [];

		$ds = new storeDataSet();
		$ds->attach($this->db);

		$this->db->reuse($ds);

		foreach($keys as $kv){
			$w = md5($kv);
			$ds = $this->db->select('uid')->where('keym', $w)->get('store_keys');
			error_log("sql=" . $this->db->debug_sql);

			while($ds->read()){
				$uid = $ds->fields['uid'];
				$items[$uid] = $uid;
			}
		}
		return array_keys($items);
	}
}

}
?>