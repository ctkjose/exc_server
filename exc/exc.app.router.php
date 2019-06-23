<?php
namespace exc;

class router {
	public static $instance = null;
	public $route=[];
	public static function instance(){
		return self::$instance;
	}
	public static function createFromRequest(){
		if(self::$instance == null){
			self::$instance = new router;
		}

		self::$instance->loadFromRequest();
		return self::$instance;
	}
	public static function load(){

		if(self::$instance == null){
			self::$instance = new router;
		}

		if(isset($_SERVER) && isset($_SERVER['REQUEST_METHOD'])){
			self::$instance->loadFromHTTP();
		}else{
			self::$instance->loadFromCLI();
		}

		return self::$instance;
	}
	public function loadFromCLI(){
		$this->route =  ['location'=>'DEFAULT', 'controller'=>'main', 'action'=> 'main', 'state'=>[], 'values'=> [], 'return_type'=>'any', 'method' => 'get', 'view' => 'default'];
		global $argv;
		$this->route['method']= 'cli';
		$this->route['values'] = [];

		$this->route['base_path'] = getcwd();

		if(is_array($argv)){
			foreach($argv as $i=>$v){
				if($i==0) continue;
				if($v=='--') continue;
				if(strpos($v,'=') === false){
					$values[$i] = $v;
					continue;
				}

				list($n, $v) = explode('=', $v);
				if(substr($n,0,1)=='-') $n = substr($n,1);
				$values[$n] = $v;
			}
			$this->route['values'] = $values;
		}
	}
	public function loadFromHTTP(){

		$url = $_SERVER['REDIRECT_URL'];
		$this->route = [
			'location'=>'DEFAULT',
			'request_url' => $url,
			'base_url'=>'', 'base_path'=>'',
			
			'action'=> 'default', 'action_type'=>'runPassthru',
			'controller_name'=>'main', 'controller_class'=>'', 'controller_url'=>'',
			'file_name'=> '','file_type'=>'', 'file_path'=>'',
			'method' => strtolower($_SERVER['REQUEST_METHOD']),
			'resource_path'=>'',
			'return_type'=>'any',
		];

		

		$re = "((?:[^\/]*\/)*)";
		if( preg_match('/' . $re . 'c\/([A-Za-z0-9-_]+)(\.([A-Za-z0-9-_]+))?/', $url, $m) ){
			//is a controller URL
			$this->route['action_type'] = 'runController';
			$this->route['base_url'] = $m[1];
			$this->route['resource_path'] = '';
			$this->route['controller_name'] = $m[2];
			if(isset($m[3])){
				$this->route['action'] = $m[4];
			}
			
			$this->loadController();
		}elseif( preg_match('/' . $re . 'o\/((?:[^\/]*\/)*)([A-Za-z0-9-_]+)\.(php|js|css|html|htm|pdf|csv|txt|md|png|jpg|svg|zip|xml)/', $url, $m) ){
			//is an assets URL
			$this->route['action_type'] = 'runPassthru';
			$this->route['base_url'] = $m[1];
			$this->route['resource_path'] = 'assets/' . $m[2];
			$this->route['action'] = $m[3] . '.' . $m[4];

			$this->route['file_name'] = $m[3] . '.' . $m[4];
			$this->route['file_type'] = $m[4];

			$fp = path::normalize( '.' . $this->route['base_url']);
			$this->route['base_path'] = $fp['path'] . '/';
			error_log_dump($fp, 'filep');
			$p = $this->route['base_path'];
			if(strlen($this->route['resource_path'])) $p .= $this->route['resource_path'];
		
			$this->route['file_path'] = $p . '' . $this->route['file_name'];
		}
		
		$this->route['state'] = [];
		$this->route['values'] = [];

		$this->loadValuesFromHTTP();

		//print "<pre>" . print_r($this->route, true) . "</pre>";
	}
	public function loadValuesFromHTTP(){
		$this->route['values'] = $_REQUEST;
		foreach($_REQUEST as $k=>$v){
			if(is_array($v)) $v = "ARRAY";
			error_log("\$_REQUEST[$k]=[$v]");
		}

		if( isset($this->route['values']['api_return']) ){
			$this->route['return_type'] = $this->route['values']['api_return'];
			unset( $this->route['values']['api_return']);
		}

		if( isset($this->route['values']['api_json_data']) ){
			$v = json_decode($this->route['values']['api_json_data'], true);
			unset($this->route['values']['api_json_data']);
			$this->route['values'] = array_merge($this->route['values'], $v);
		}

		if( isset($this->route['values']['api_json_state']) ){
			$this->route['state'] = json_decode($this->route['values']['api_json_state'], true);
			unset($this->route['values']['api_json_state']);
		}


		if(isset($this->route['values']['api-action'])) {
			$this->route['action'] = strtolower($this->route['values']['api-action']);
			unset( $this->route['values']['api-action']);
		}elseif( isset($this->route['values']['a']) && ($this->route['action_type'] == 'runInclude')){
			$this->route['action'] = strtolower($this->route['values']['a']);
			unset($this->route['values']['a']);
		}
	}
	public function loadController(){
		$af = $this->route['action'];
		$this->route['controller_class'] = '';
		
		$fp = path::normalize( '.' . $this->route['base_url']);
		$this->route['base_path'] = $fp['path'] . '/';
		//error_log_dump($fp, 'filep');


		$p = $this->route['base_path'];
		if(strlen($this->route['resource_path'])){
			$p .= $this->route['resource_path'];
		}
		$file = 'controller.' . $this->route['controller_name'] . '.php';
		if(substr($p,0,1) != '/') $p = '/' . $p;
		if(substr($p,-1,1) != '/') $p .= '/';
		$p.= $file;

		$f = \exc\path::normalize($p);
		//error_log_dump($f, 'file');
		if(!$f['exists']){
			error_log("EXC ROUTE FILE NOT FOUND::" . $f['path']);
			return;
		}

		$this->route['file_type'] = 'php';
		$this->route['file_name'] = $f['name'];
		$this->route['file_path'] = $f['path'];
		
		$this->route['controller_class'] = $this->route['controller_name'] . 'Controller';
		$this->route['controller_directory'] = dirname($f['path']) . '/';

		$this->route['controller_directory_url'] = $this->route['base_url'] . 'c/';
		$this->route['assets_directory_url'] = $this->route['base_url'] . 'a/';


		$u = $this->route['controller_directory_url'];
		if(strlen($this->route['resource_path'])){
			$u .= $this->route['resource_path'];			
		}
		if(substr($u,-1,1) != '/') $u .= '/';
		$this->route['controller_url'] = $u . $this->route['controller_name'];

	}
	public function setValue($n, $v){
		$this->route['values'][$n] = $v;
	}
}
?>