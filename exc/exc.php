<?php

namespace exc;

$__here = dirname(__FILE__) . '/';

require_once($__here . "exc.core.php");
require_once($__here . "exc.app.router.php");
require_once($__here . "exc.app.php");
require_once($__here . "exc.app.manifest.php");
require_once($__here . "exc.session.php");
//require_once($__here . "exc.io.files.php");

spl_autoload_register(function ($class) {
	error_log("auto_load[" . $class . "]");

	if(substr($class, 0, 4) == 'exc\\'){
		$o = explode('\\', $class);
		$cn = array_pop($o);
		$f = implode('.', $o);

		if(file_exists(__DIR__ . '/' . $f . '.php')){
			error_log("[EXC] AUTOLOAD " .   __DIR__ . '/' . $f . '.php');
			include_once(__DIR__ . '/' . $f . '.php');
		}else if(file_exists(__DIR__ . '/' . $f . '.' . $cn . '.php')){
			error_log("[EXC] AUTOLOAD " .  __DIR__ . '/' . $f . '.' . $cn . '.php');
			include_once( __DIR__ . '/' . $f . '.' . $cn . '.php');
		}
	}
});
class bootloader {

	const RUN_MODE_CLI = 2;
	const RUN_MODE_WEB = 1;
	private static $modules = [];
	public static $options = ['version'=>'1.0', 'version_name'=>'EXC0001.0', 'mode'=> self::RUN_MODE_WEB];
	public static function setOption($name, $value){
		options::$values[$name] = $value;
	}

	public static function run(){
		define('EXC_SERVER_PATH', dirname(__FILE__) . '/');
		define('EXC_PATH', dirname(__DIR__) . '/');
		define('EXC_DIRECTORY', __DIR__ . '/');

		if( isset($_SERVER) && isset($_SERVER['DOCUMENT_ROOT']) && (strlen($_SERVER['DOCUMENT_ROOT']) > 0)){
			$r = $_SERVER['DOCUMENT_ROOT'];
		}elseif( isset($_SERVER) && isset($_SERVER['PWD']) && (strlen($_SERVER['PWD']) > 0)){
			$r = $_SERVER['PWD'];
		}
		if(substr($r, -1, 1) != '/') $r.= '/';
		define('EXC_DIRECTORY_ROOT', $r);
		path::setRoot($r);
		
		options::$values['version'] = '1.0';
		options::$values['version_name'] = 'EXC0001.0';
		options::$values['mode'] = isset($_SERVER['SHELL']) ? self::RUN_MODE_CLI: self::RUN_MODE_WEB;
		
		options::$values['path_exc'] = EXC_PATH;

	
		define('EXC_RUNMODE', options::$values['mode']);

		options::$values['app'] = [
			'uid'=>'EXCAPP',
			'path_app'=>'',
			'path_views'=> EXC_PATH . 'views/',
			'firstresponder'=> null,
			'controllers'=>[],
			'with_ui' => 0,
			'with_client' => 0,
			'with_session' => 0,
			'paths'=>[
				'base'=>'',
				'views'=>[]
			],
			'urls' => [

			]
		];

		\exc\controller\appController::init();
		$app = \exc\controller\appController::instance();

		$r = \exc\router::load();


		error_log_dump($r->route, 'ROUTE');

		if($r->route['method']== 'cli'){
			options::$values['app']['paths']['base'] = $r->route['base_path'];
		}else{
			options::$values['app']['paths']['base'] = $r->route['base_path'];
			options::$values['app']['paths']['controller'] = $r->route['file_path'];
			options::$values['app']['paths']['controller_directory'] = dirname($r->route['file_path']) . '/';
			options::$values['app']['paths']['resource'] = $r->route['resource_path'];

			options::$values['app']['urls']['base'] = $r->route['base_url'];
			options::$values['app']['urls']['controller'] = $r->route['controller_url'];
			options::$values['app']['urls']['controller_directory'] = $r->route['controller_directory_url'];
			options::$values['app']['urls']['assets_directory'] = $r->route['assets_directory_url'];


			path::addUP('app', $r->route['base_path']);
		}

		if( isset($r->route['base_path'])){
		   define('EXC_DIRECTORY_FOR_CONTROLLER', $r->route['base_path']);
		   $app->loadAppDefinition($r->route['base_path']);
		}

		if(is_array(options::$values['app']['using'])){
			foreach(options::$values['app']['using'] as $k=>$params){
				self::addModule($k,$params);
			}
		}

		


		error_log_dump(options::$values, "options");
		//reasg_dev_dump($app, "APP");

		$fn = $r->route['action_type'];
		if(method_exists('exc\bootloader', $fn)){
			self::$fn($app);
		}

		
		
		$app->end();

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
	public static function runController($app){

		error_log("@runController--------------");
		$r = \exc\router::instance();

		if( (options::$values['app']['with_ui']==1) && (options::$values['app']['with_client']==0) ){
			$app->initializeUI();
		}

		$app->loadAppControllers();

		if( isset($r->route['controller_name']) && (strlen($r->route['controller_name']) > 0) ){
			$cn = $r->route['controller_name'];
			if(!isset($app->controllers[$cn])){
				$app->addAppController($cn . 'Controller', $r->route['file_path']);
			}

			$app->setFirstResponder($cn);
		}

		if(is_null($app->controller) && (count($app->controllers) > 0)){
			$app->setFirstResponder($app->controllers[0]);
		}

		\exc\session::initialize();
		$app->publish("appInit", []);
		$app->publish("requestStart", []);
		if(!\exc\session::hasKey("eas")){
			self::runApplication($app);
		}else{
		
			if( \exc\core\controller::isControllerInstance($app->controller) ){
				$app->controller->performMessage('action_' . $r->route['action'],[]);
			}
		}

		$app->end();
	}
	public static function runApplication($app){
		\exc\session::key("eas", 1);

		$bs = \exc\session::key("BS");
		$ms = 'R' . sha1( $bs );
		$app->publish("appStart", [$app]);
		
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
	public static function hasModule($name){
		if(array_key_exists($name,self::$modules)) return true;
		return false;
	}
	public static function addModule($name, $params){

		if(array_key_exists($name,self::$modules)) return true;


		$paths = [
			'exc'=> EXC_DIRECTORY,
			'app'=> EXC_DIRECTORY_FOR_CONTROLLER,
			'file'=> EXC_DIRECTORY_ROOT,
			'vendor'=> '',
			'composer'=> '',
		];


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
			if(isset(self::$store[$n])) return self::$store[$n];
			return null;
		}
		self::$values[$n] = $v;
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
		$ext = isset($a['extension']) ? strtolower($a['extension']) : '';
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