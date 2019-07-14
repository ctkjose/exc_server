<?php

namespace exc;


define('EXC_PATH', __DIR__ . '/');

if( defined('STDIN') || in_array(PHP_SAPI, ['cli', 'cli-server', 'phpdbg'], TRUE) ) {
	define('EXC_RUNMODE', 2); //is CLI
}else{
	define('EXC_RUNMODE', 1); //is SAPI, WEB
}
if(file_exists(EXC_PATH . '/exc.config.php')){
	include_once(EXC_PATH . '/exc.config.php');
}
if(!defined('EXC_PATH_APPS_FOLDER')){
	define('EXC_PATH_APPS_FOLDER', dirname(__DIR__) . "/");
}

require_once(EXC_PATH . "helper.error.php");
require_once(EXC_PATH . "core.php");
require_once(EXC_PATH . "exc.app.php");
require_once(EXC_PATH . "exc.session.php");
require_once(EXC_PATH . "exc.helper.js.php");

error_log_dump($_SERVER);

spl_autoload_register(function ($class) { ///NST:MARK:FN:autoloader
	error_log("auto_load[" . $class . "]");

	if(substr($class, 0, 4) == 'exc\\'){
		$o = explode('\\', $class);
		$cn = array_pop($o);
		$f = implode('.', $o);
		
		if(file_exists(__DIR__ . '/' . $f . '.' . $cn . '.php')){
			error_log("[EXC] AUTOLOAD " .  __DIR__ . '/' . $f . '.' . $cn . '.php');
			include_once( __DIR__ . '/' . $f . '.' . $cn . '.php');
			return;
		}

		if(count($o) > 1 && file_exists(__DIR__ . '/' . $f . '.php')){
			error_log("[EXC] AUTOLOAD " .   __DIR__ . '/' . $f . '.php');
			include_once(__DIR__ . '/' . $f . '.php');
			return;
		}
	}
});
class bootloader {

	const RUN_MODE_CLI = 2;
	const RUN_MODE_WEB = 1;
	public static $RUNMODE = 1;
	public static $route = [];
	private static $modules = [];
	public static $options = ['version'=>'1.0', 'version_name'=>'EXC0001.0', 'mode'=> self::RUN_MODE_WEB];
	public static function setOption($name, $value){
		options::$values[$name] = $value;
	}
	public static function processOptionUsing($entries, $scope='*'){
		if(!is_array($entries)) return;

		foreach($entries as $ascope => $set){
			if($ascope != $scope && $ascope != '*') continue;
			foreach($set as $k=>$params){
				\exc\bootloader::addModule($k,$params);
			}
		}
	}
	public static function run(){
		
		self::$RUNMODE = EXC_RUNMODE;
		
		\exc\app::init();
		\exc\app::$RUNMODE = self::$RUNMODE;
		
		if(self::$RUNMODE == self::RUN_MODE_CLI){
			self::initFromCLI();
		}else{
			self::initFromHTTP();
		}


		error_log_dump(self::$route, 'ROUTE');
		exit;
		if(!strlen(self::$route['controller_name']) || (!isset(self::$route['base_path']))){
			error_log("[EXC][BOOTSTRAP][ABORT] Nothing to do!");
			exit;
		}
		
		define('EXC_PATH_BASE', self::$route['base_path']);
	
		
		$fn = self::$route['action_type'];
		if(method_exists('exc\bootloader', $fn)){
			self::$fn();
		}

		

	}

	public static function runInclude($app){

	}
	public static function runPassthru($app){
		error_log("@runPassThru--------------");
		$r = \exc\router::instance();
		error_log_dump($r);
		$p = $r->route['base_path'] . $r->route['resource_path'] . $r->route['file'];
		error_log("@runPassThru [" . $p . "]");
		if(!file_exists($p)){
			header("HTTP/1.0 404 Not Found");
			print "";
			die();
		}

		$mime = 'application/octet-stream';
		switch($r->route['file_type']){
			case "js":
				$mime = 'text/javascript';
				break;
			case "css":
				$mime = 'text/css';
				break;
			case "svg":
				$mime = 'image/svg+html';
				break;
			case "png":
				$mime = 'image/png';
				break;
			case "gif":
				$mime = 'image/gif';
				break;
			case "jpeg":
				$mime = 'image/jpeg';
				break;
			case "pdf":
				$mime = 'application/pdf';
				break;
			case "zip":
				$mime = 'application/zip';
				break;
			case "json":
				$mime = 'application/json';
				break;
			case "xls":
			case "xlsx":
				$mime = 'application/vnd.ms-excel';
				break;
			case "csv":
				$mime = 'text/csv';
				break;
		}
		header('Content-Type: ' . $mime);
		header('Content-Length: '.filesize($p));
		
		readfile($p);
		die();
	}
	public static function runController(){

		
		error_log("[EXC][BOOTLOADER] Running controller");
		if( isset(self::$route['base_path'])){
			define('EXC_DIRECTORY_FOR_CONTROLLER', self::$route['base_path']);
			if(!\exc\app::loadApp(self::$route['base_path'])){
				error_log("[EXC][BOOTSTRAP][ERROR] Unable to load controller.app.php.");
				exit;
			}
		}

		$app = \exc\app::controller();
		
		//reasg_dev_dump($app, "APP");
		$cn = 'appController';

		if( isset(self::$route['controller_name']) && (self::$route['controller_name']!='app')){
			//load a controller
			$cn = self::$route['controller_name'];
			$p = EXC_PATH_BASE;
			if(strlen(self::$route['resource_path'])){
				$p .= self::$route['resource_path'];
			}
			$file = 'controller.' . $cn . '.php';
			if(substr($p,0,1) != '/') $p = '/' . $p;
			if(substr($p,-1,1) != '/') $p .= '/';
			$p.= $file;

			$f = \exc\path::normalize($p);
			//error_log_dump($f, 'file');
			if(!$f['exists']){
				error_log("[EXC][BOOTLOADER][ERROR] Unable to load requested controller" . $f['path']);
			}else{
				\exc\options::key('/app/path/controller', $f['path']);
				\exc\app::registerController($cn . 'Controller', $f['path']);
			}

			\exc\app::setFirstResponder($cn);

		
		}
		
		\exc\options::key('/app/controllerClass', $cn);

		\exc\error_log_dump(options::$values, "options");
		
		if(self::$RUNMODE == self::RUN_MODE_WEB){
			\exc\session::initialize();
			if(\exc\session::$enabled) error_log("session ready ----------------------");
		}

		\exc\app::runWithAction(self::$route['action']);
		//$app->end();
		

		
	}
	public static function runApplication($app){
		\exc\session::key("eas", 1);

		
		$app->appStart();
		
		
		/*
		if(is_array(options::$values['app']['manifest1'])){  //refactor...
			foreach(options::$values['app']['manifest1'] as $e){
				$url = ''; $wait = true; $type=null;

				if(is_string($e)){
					$url = $e;
				}elseif(is_array($e) && isset($e['url'])){
					$url = $e['url'];
					if(isset($e['wait'])) $wait = $e['wait'];
					if(isset($e['type'])) $type = $e['type'];
				}

				if($type=='export'){
					\exc\manifest::addExport($url, $e['name'], $wait);
				}elseif($type=='script'){
					\exc\manifest::addScript($url, $wait);
				}else{
					\exc\manifest::addInclude($url, $wait);
				}
			}
		}
		*/
		
		//$p = options::$values['app']['paths']['controller_directory'] . 'assets/js/controller.app.js';
		//$r = \exc\path::normalize($p);
		//error_log_dump($r, 'controller.app.js');
		//if($r['exists']){
			//$appjs = file_get_contents($r['path']);
			//\exc\manifest::addScript($r['url'], $wait);
		//}
	}
	public static function detectRun(){

		self::$route = [
			'request_url' => $url,
			'base_url'=>'', 'base_path'=>'',
			
			'action'=> 'default', 'action_type'=>'runPassthru',
			'controller_name'=>'', 'controller_class'=>'', 'controller_url'=>'',
			'file_name'=> '','file_type'=>'', 'file_path'=>'',
			'method' => '',
			'resource_path'=>'',
			'return_type'=>'any',
		];

		
	}
	
	public static function loadController(){
		
		if(!strlen(self::$route['controller_name'])) return;
		
		$af = self::$route['action'];
		self::$route['controller_class'] = '';
		
		$fp = path::normalize( '.' . self::$route['base_url']);
		self::$route['base_path'] = $fp['path'] . '/';
		//error_log_dump($fp, 'filep');


		$p = self::$route['base_path'];
		if(strlen(self::$route['resource_path'])){
			$p .= self::$route['resource_path'];
		}
		$file = 'controller.' . self::$route['controller_name'] . '.php';
		if(substr($p,0,1) != '/') $p = '/' . $p;
		if(substr($p,-1,1) != '/') $p .= '/';
		$p.= $file;

		$f = \exc\path::normalize($p);
		//error_log_dump($f, 'file');
		if(!$f['exists']){
			error_log("EXC ROUTE FILE NOT FOUND::" . $f['path']);
			return;
		}

		self::$route['file_type'] = 'php';
		self::$route['file_name'] = $f['name'];
		self::$route['file_path'] = $f['path'];
		return true;
	}
	public static function initFromCLI(){
		if( isset($_SERVER) && isset($_SERVER['PWD']) && (strlen($_SERVER['PWD']) > 0)){
			$r = $_SERVER['PWD'];
			if(substr($r, -1, 1) != '/') $r.= '/';
			define('EXC_DOCUMENT_ROOT', $r);
		}
		
		\exc\path::setRoot(EXC_DOCUMENT_ROOT);
	}
	public static function initFromHTTP(){

		if( isset($_SERVER) && isset($_SERVER['DOCUMENT_ROOT']) && (strlen($_SERVER['DOCUMENT_ROOT']) > 0)){
			$r = $_SERVER['DOCUMENT_ROOT'];
			if(substr($r, -1, 1) != '/') $r.= '/';
			define('EXC_DOCUMENT_ROOT', $r);
		}

		\exc\path::setRoot(EXC_DOCUMENT_ROOT);
		error_log('[EXC][BOOTLOADER] REDIRECT ' . (isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '<UNDEFINED>'));
		error_log('[EXC][BOOTLOADER] URI ' . $_SERVER['REQUEST_URI']);

		self::$route['method'] = strtolower($_SERVER['REQUEST_METHOD']);
		self::$route['action_type'] = 'runController';
		self::$route['controller_name'] = 'app';
		self::$route['action'] = 'default';

		self::$route['request_url'] = $_SERVER['REQUEST_URI'];
		self::$route['base_url'] = '';
		self::$route['base_path'] = dirname($_SERVER['REQUEST_URI']);


		$a = 'default';

		self::$route['values'] = $_REQUEST;

		if(isset(self::$route['values']['a'])){
			$a = self::$route['values']['a'];
			unset(self::$route['values']['a']);
		}
		if(isset(self::$route['values']['api-action'])) {
			$a = strtolower(self::$route['values']['api-action']);
			unset( self::$route['values']['api-action']);
		}

		if(preg_match('/([A-Za-z0-9-_]+)\.([A-Za-z0-9-_]+)/', $a, $m)){
			self::$route['controller_name'] = $m[1];
			$a = $m[2];
		}
		
		self::$route['action'] = $a;
		//Load values
		
		error_log_dump($_REQUEST, '$_REQUEST');
		

		if( isset(self::$route['values']['api_return']) ){
			self::$route['return_type'] = self::$route['values']['api_return'];
			unset( self::$route['values']['api_return']);
		}

		if( isset(self::$route['values']['api_json_data']) ){
			$v = json_decode(self::$route['values']['api_json_data'], true);
			unset(self::$route['values']['api_json_data']);
			self::$route['values'] = array_merge(self::$route['values'], $v);
		}

		if( isset(self::$route['values']['api_json_state']) ){
			self::$route['state'] = json_decode(self::$route['values']['api_json_state'], true);
			unset(self::$route['values']['api_json_state']);
		}


		

	}
	public static function hasModule($name){
		if(array_key_exists($name,self::$modules)) return true;
		return false;
	}
	public static function addModule($name, $params){

		if(array_key_exists($name,self::$modules)) return true;

		///TODO mode this to use \exc\path
		$paths = [
			'exc'=> EXC_PATH,
			'app'=> '',
			'file'=> EXC_DOCUMENT_ROOT,
			'vendor'=> '',
			'composer'=> '',
		];

		$paths['apps'] = defined('EXC_PATH_APP') ? EXC_PATH_APP : EXC_PATH_BASE;

		$p = explode('://', $name);
		$k = $p[0];
		if(!array_key_exists($k, $paths)) return false;

		$dir = $paths[$k];
		$f = $p[1];
		$fdir = (strpos($f,'/')!==false) ? dirname($f) : '';
		$fn = basename($f);
		
		if(strpos($fn, ".php") !== false){
			$f = $dir . $f;
		}else{
			$f = $dir . ((strlen($fdir) > 0)?$fdir . '/' : '') .  $fn . '.php';
		}

		$e = ['file'=> $f, 'ns'=> $fn, 'manager'=>false, 'cls'=>'', 'loaded'=>false];

		error_log("[EXC] Loading Module $fn, including " . $f);
		error_log("in=[{$p[1]}] f=[$f], [$dir][$fdir][$fn]"); 

		if(!file_exists($f)){
			self::$modules[$name] = $e;
			return false;
		}

		include_once($f);
		$e['loaded'] = true;

		$ns = '\\' . str_replace('.', '\\', $fn);
		if( class_exists($ns . "\\manager") ){
			$e['manager'] = true;
			$e['cls'] = $ns . "\\manager";
		}elseif( class_exists($ns) ){
			$e['cls'] = $ns;
		}else{
			return false;
		}

		error_log("[EXC] Module class=" . $e['cls']);
		

		if(strlen($e['cls']) > 0){
			$cls = $e['cls'];
			if(method_exists($cls, "initialize")){
				$cls::initialize($params);
			}
		}

		self::$modules[$name] = $e;
		return true;
	}
}

function error_log_dump($a, $n='$any', $ind=''){
	$t = 'array';
	$d1 = "['"; $d2="']";
	$s = '';
	
	$out = array();
		
	if( is_object($a) ){
		$t = 'class ' . get_class($a);
		$d1 = '->'; $d2='';
		$a = get_object_vars($a);
	}elseif( is_array($a) ){
		
	}
	
	$tabs = $ind;
	
	$e = null;
	if(is_null($a)){
		$e= array('n'=>"{$n} (NULL)", 'v'=>'<NULL>', 't'=>$tabs);
	}elseif(is_string($a)){
		$out[] = array('n'=>"{$n} (string) (len " . strlen($a) . ')', 'v'=>$a, 't'=>$tabs);
		return $out;
	}elseif(is_numeric($a)){
		$out[] = array('n'=>"{$n} (numeric)", 'v'=>$a, 't'=>$tabs);
		return $out;
	}elseif(is_resource($a)){
		$out[] = array('n'=>"{$n} (resource)", 'v'=>'', 't'=>$tabs);
		return $out;
	}elseif(is_bool($a)){
		$out[] = array('n'=>"{$n} (bool) ", 'v'=> (($a) ? 'TRUE' : 'FALSE'), 't'=>$tabs);
		return $out;
	}
	
	if(!is_null($e)){
		$s = $e['t'] . $e['n'] . ' = ' . $e['v'];
		error_log($s);
		return;
	}
	if(count($a) <= 0){
		$s = $tabs . "{$n} ({$t})" . ' = <empty>';
		error_log($s);
		return;
	}

	error_log($tabs . "{$n} ({$t})");
	
	$tabs.= '  ';
	
	foreach($a as $k => $v){
		
		$name = "{$n}{$d1}{$k}{$d2}";
		if( is_array($v) || is_object($v)){
			
			error_log_dump($v, $n . $d1 . $k . $d2, $tabs);
			continue;
		}elseif(is_null($v)){
			$t = 'NULL';
			$v = '<NULL>';
		}elseif( is_bool($v) ){
			$t = 'BOOL';
			$v = ($v) ? 'true' : 'false';
			
		}elseif( is_string($v) ){
			$t = 'string';
			if(strlen($v) == 0) $t.= ", <empty>";
			$v = htmlentities($v);
			
		}elseif( is_numeric($v) ){
			$t = 'number';
		}elseif(is_resource($a)){
			$t = 'resource';
			$v = '';
		}elseif( is_null($v) ){
			$t = '';
			
		}
		error_log($tabs . "{$name} ({$t}) = {$v}");
	}
}

class options {
	public static $values = [];
	public static function setOption($name, $value=null){
		self::$values[$name] = $value;
	}
	public static function key($n, $v=null){

		$key = null;
		if(strpos($n,'/') !== false){
			$p = explode('/',$n);
			if(strlen(trim($p[0])) == 0) unset($p[0]);
			$key = &self::$values;
			foreach($p as $n){
				if(!isset($key[$n])) $key[$n] = [];
				$key = &$key[$n];
			}
		}elseif(isset(self::$values[$n])){
			$key = &self::$values[$n];
		}

		if(is_null($v)){
			return $key;
		}
		$key = $v;
	}
	public static function hasKey($n){
		return isset(self::$values[$n]);
	}
}
class path {
	public static $up= [];
	public static $rootPath='';
	public static function setRoot($path){
		self::$rootPath = $path;
		if(substr($path,-1,1) != '/')  self::$rootPath.= '/';
		path::addUP('file', self::$rootPath);
		path::addUP('exc', __DIR__);
	}
	public static function addUP($n, $path){ //add url protocol
		self::$up[$n] = $path;
		if(substr($path,-1,1) != '/')  self::$up[$n].= '/';

		if($n == 'app'){
			self::$up['asset'] = self::$up['app'] . 'assets/';
		}		
	}
	public static function info($p){
		$o = pathinfo($p);
		$o['path'] = $p;
		$o['extension'] = isset($o['extension']) ? strtolower($o['extension']) : '';
		return $o;
	}
	public static function tempFile($prefix=null, $tempPath = null){
		$p = (!is_null($prefix)) ? $prefix : '';
		$pt = (!is_null($tempPath)) ? $tempPath : sys_get_temp_dir();
		$f = tempnam($pt, $p);
		return self::info($f);
	}
	public static function combine($p1, $p2){
		if(is_array($p1)){
			if(!isset($p1['path'])) return false;
			$p = $p1['path'];
		}elseif(is_string($p1)){
			$p = $p1;
		}else{
			return '';
		}

		$parts = [];
		if(is_array($p2)){
			$parts = $p2;
		}elseif( func_num_args() >= 2){
			$parts = func_get_args();
			array_shift($parts);
		}

		foreach($parts as $e){
			if(substr($e, 0, 1) != '/'){
				if(substr($p,-1,1) != '/') $p.= '/';
			}
			$p.= $e;
		}
		
		return $p;
	}
	public static function copy($p1, $p2){
		
		if(is_array($p1)){
			if(!isset($p1['path'])) return false;
			$p = $p1['path'];
		}elseif(is_string($p1)){
			$p = $p1;
		}else{
			return false;
		}

		if(is_array($p2)){
			if(!isset($p2['path'])) return false;
			$d = $p2['path'];
		}elseif(is_string($p2)){
			$d = $p2;
		}else{
			return false;
		}

		if(!file_exists($p)) return false;
		$ok = copy($p, $d);
		return $ok;
	}
	public static function normalize($p){
		if(strlen(self::$rootPath)) chdir(self::$rootPath);


		$url = '';
		$path = $p;
		$r = self::$rootPath;
		$exists = false;

		if(strtolower(substr($p, 0, 4)) == 'http'){
			$url = $p;
			$path = $p;
		}elseif(substr($p, 0, 1) == '/'){
			$path = $p;
		}else{
			foreach(self::$up as $k => $up){
				$path = str_replace($k . '://', $up, $path);
			}
		}

		$exists = false;
		$p1 = realpath($path);
		if(strlen($p1) > 0){
			$path = $p1;
			$exists = true;
		}

		$a = pathinfo($path);
		$a['extension'] = isset($a['extension']) ? strtolower($a['extension']) : '';
		
		$name = $a['basename'];
		
		$out = [
			'in'=>$p, 'url'=>'', 'ext'=> $ext,
			'path'=>$path, 'name'=>$name, 'dir'=>dirname($path) . '/',
			'exists'=> $exists, 'isAsset'=> false, 'isController'=>false
		];
		if(strlen($url) == 0){
			//map url
			$url = str_replace(self::$rootPath, '', $a['dirname']);
			if(substr($url,0,1) != '/') $url = '/' . $url;
			if(substr($url,-1,1) != '/') $url .= '/';

			if(preg_match("/^controller\.([A-Za-z0-9\_\-]+)\.php$/", $a['basename'], $m)){
				$url.= 'c/' . $m[1];
				$out['isController'] = true;
			}elseif(preg_match("/\/assets\/([A-Za-z0-9-_]+)\/([A-Za-z0-9-_]+)\.(php|js|css|html|htm|pdf|csv|txt|md|png|jpg|svg|zip|xml)$/", $url . $a['basename'], $m)){
				foreach(['/assets/js/', '/assets/css/'] as $sp){
					if(strpos($url,$sp) !== false) $url = str_replace($sp, '/',$url);
				}
				$url.= 'a/' . $a['basename'];
				$out['isAsset'] = true;
			}else{
				$url.= $a['basename'];
			}
		}
		$out['url'] = $url;
		
		//error_log(print_r($out, true));
		return $out;
	}

}
?>