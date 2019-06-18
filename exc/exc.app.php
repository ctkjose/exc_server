<?php
namespace exc{
class app {
	static private $last_js_controller_file = null;
	static public function ready($js){
		$c = client::instance();
		$c->ready($js);
	}
	public static $app_state = [ 'header'=>[], 'buffer'=>'', 'commited'=>false, 'config'=>[] ];
	public $properties = array();

	public static function controller(){
		global $app_controller;
		return $app_controller;
	}
	public static function setModuleName($name){
		$c = client::instance();
		$c->setModuleName($name);
	}
	public static function setModuleController($className, $url = null){
		$c = client::instance();
		$c->setModuleController($className, $url);
	}
	public static function addDataset($name, $path){
		$c = client::instance();
		$c->addDataset($name, $path);
	}
	public static function getJSObject(){
		global $config;

		$uid_name = 'main';
		$js_app = "<script type='text/javascript'>//exc app\n";

		$uid = uniqid('APPK');
		if(isset(self::$app_state['moduleName'])){
			if( isset(self::$app_state['moduleController']) && (self::$app_state['moduleName'] == self::$app_state['moduleController']) ){
				 self::$app_state['moduleName'] .= '_' . $uid;
			}
			$uid_name = self::$app_state['moduleName'];
		}

		if(isset(self::$app_state['moduleController']) && !empty(self::$app_state['moduleController']['js']) ){
			$js_app.= self::$app_state['moduleController']['js'] . "\n\n";
		}


		$js_app.= "var {$uid} = " . self::toJSON() . ";\n";
		$js_app.= "{$uid}.moduleName = '{$uid_name}';\n";
		$js_app.= "{$uid}.moduleLoaded = false;\n";

		if(isset(self::$app_state['moduleController'])){
			$js_app.= $uid . '.moduleController = "' . self::$app_state['moduleController']['name'] . "\";\n";
		}

		foreach(self::$datasets as $k => $d){
			$js_app.= "{$uid}.datasets." . $k . "=" . $d . ";\n";
		}

		$js_app.= $uid . '.app_event_start = function(){' . self::$ready . "};\n";

		$js_app.= 'exc.app.bootstrapModule(' . $uid . ");\n";

		$js_app.= "</script>";
		return $js_app;
	}
	public static function client($a=null){
		return client::instance($a);
	}
	public static function sendPage($page){
		global $app_forms;

		if( !empty($app_forms) && (count($app_forms) > 0)){
			foreach($app_forms as $f){
				if($f->clean) {
					$page->write($f->getHTML());
				}
			}
		}

		self::raise('default_view_commit', [$page]);

		self::commit();
		$page->show( $config->application['attributes']['template_view']);

	}
}


} //namespace exc
namespace exc\controller {


class appController extends \exc\core\controller {
	public $controller = null;
	public $controllers = [];
	public static $app_state = [ 'headers'=>[], 'buffer'=>'', 'commited'=>false, 'ended'=>false, 'config'=>[] ];
	public static function init(){
		global $app_controller, $client_controller, $app;
		$app_controller = new appController();
		$app = $app_controller;

		self::$app_state['config'] = ['uid'=>'', 'directory'=> '', 'url'=>'' ,'lng'=>'en','location'=> 'DEFAULT',  'action'=> '', 'action_default'=> '' ];

	}
	public static function setAppState($n, $v){
		self::$app_state[$n] = $v;
	}
	public function controller(){
		return $this->controller;
	}
	public static function instance(){
		global $app_controller;
		return $app_controller;
	}
	public function getOption($n, $default=null){
		if(!isset(\exc\options::$values['app'][$n])) return $default;
		return \exc\options::$values['app'][$n];
	}
	public function setOption($n, $v){
		\exc\options::$values['app'][$n] = $v;
	}
	public function loadAppDefinition($basePath = null){

		$r = $basePath;
		chdir($r);
		$p = realpath("./app.php");
		while($p === false){
			$r = realpath('../');
			if($r === false) break;
			chdir($r);
			$p = realpath("./app.php");
		}
		if($p === false) return false;
		error_log("[EXC] FOUND APP DEFINITION AT [" . $p . ']');

		$app = $this;
		$options = \exc\options::$values['app'];

		$options['path_app'] = dirname($p) . '/';

		//\exc\bootloader::setOption()
		include($p);

		if(!is_array($options)) return false;

		foreach($options as $k=>$v){
			\exc\options::$values['app'][$k] = $v;
		}



		return false;
	}
	public function addAppController($cc, $p=null){

		$o = null;
		$cp = '';
		$cn = 'stdClass';
		if(is_string($cc)){
			
			if(!is_null($p)){
				$cp = realpath($p);
				if($cp === false) return;
				$o = \exc\core\controller::loadControllerWithPath($cc, $p);
			}else{
				if(!class_exists($cc)) return;
				if( substr($cc, 0,1) != '\\') $cc = '\\' . $cc;
				$o = new $cc;
			}
		
			$cn = substr($cc,0, strlen($cc)-10);
		}elseif(is_object($cc)){
			$o = $cc;
			$cc = get_class($o);
		}

		if(is_null($o)) return;
		if( !\exc\core\controller::isControllerInstance($o) ) return;
		error_log("[EXC] appControllerLoad [" . $cp . '][' . $cc . ']' );
		$this->publish("appControllerLoaded", [$o]);
		$this->controllers[$cn] = ['name'=>$cn, 'class'=>$cc, 'instance'=>$o, 'path'=>$cp];
		//\exc\core\controller::registerHandlers($this, $o);
	}
	public function setFirstResponder($any){
		$cn = $any;
		if(!isset($this->controllers[$cn])) return; //fail silently?

		$this->controller = $this->controllers[$cn]['instance'];
		\exc\options::$values['app']['firstresponder'] = $cn;

		if($this->controller instanceof \exc\controller\viewController){ //do we need a client
			$this->initializeUI();
		}
	}
	public function loadAppControllers(){
		$o = \exc\options::$values['app'];
		$cr = ( isset($o['controllers']) && is_array($o['controllers']) ) ? $o['controllers'] : [];

		$i = 0;
		foreach($cr as $cc => $p){
			$r = \exc\path::normalize($p);
			$this->addAppController($cc, $r['path']);
		}


	}
	public function getController($cn){
		if(!isset($this->controllers[$cn])) return null; //return $this;
		return $this->controllers[$cn]['instance'];
	}
	public function initializeUI(){
		if( \exc\options::key('/app/with_client') == 1) return;
		\exc\bootloader::addModule('exc://exc.ui', []);
	}
	function publish($evtName, $param=[]){
		///N:Raises an event, the event gets send to observers registered for this event
		$kmsg = $evtName;
		foreach($this->controllers as $cn => $ce){
			$o = $ce['instance'];
			if(is_null($o)) continue;
			$handler = $o->getMessageHandler($kmsg);
			if(!is_null($handler)){
				call_user_func_array($handler, $param);
			}
		}

		error_log("[EXC] APP PUBLISH " . $evtName . "");
		parent::publish($evtName, $param);
	}
	public function header($n, $v){
		$k = strtolower($n);
		self::$app_state['headers'][$n] = $v;
	}
	public function sendHeaders(){
		global $app_controller;
		if(self::$app_state['commited']) return;
		$this->publish("appSendHeaders", [$app_controller]);

		foreach(self::$app_state['headers'] as $k=>$v){
			header($k . ': ' . $v, true);
		}
	}
	public function sendJSON($a){

		$app = \exc\controller\appController::instance();
		$app->publish("appSendJSON", []);
		
		$this->header('content-type','text/json');

		if(is_array($a)){
			$s = json_encode($a);
			$this->write($s);
		}else{
			$this->write($a);
		}

		$this->end();
	}
	public function sendJS($s){
		$app = \exc\controller\appController::instance();
		$app->publish("appSendJS", []);
		$this->header('content-type','text/javascript');

		if(is_string($s)){
			$this->write($s);
		}

		$this->end();
	}
	public function sendView($a){
		$this->sendHeaders();


		if( is_string($a) ){
			$this->commit($a);
		}elseif( is_object($a) && method_exists($a, "getHTML") ){
			$this->commit($a->getHTML());
		}

		$this->end();
	}
	public function end(){
		self::$app_state['ended'] = true;

		if(!self::$app_state['commited']){
			self::commit();
		}

		$this->publish("requestEnd",[]);

		//flush();
		exit;
	}
	public function commit($data=null){

		if(self::$app_state['commited']) return;
		//error_log("at_commit");

		$this->publish("appSendOutput", []);

		$this->sendHeaders();
		self::$app_state['commited'] = true;

		print self::$app_state['buffer'];
		if(!is_null($data)) print $data;
	}
	public function abort(){
		self::$app_state['ended'] = true;

		$this->publish("requestAbort", []);
		$this->publish("requestEnd", []);
		flush();
		exit;
	}
	public static function sendDownloadWithData($mime, $data, $filename=null){

		if(self::$app_state['commited']) return;

		$this->header('content-type', $mime);

		if(!is_null($filename)){
			$this->header('content-disposition', 'attachment; filename="' . urlencode($filename) . '');
		}else{
			$this->header('content-disposition', 'attachment;');
		}

		$size = strlen($data);
		$this->header('content-length', $size);

		$this->commit($data);
		$this->end();
	}
	public function sendDownloadWithFile($mime, $file){ ///FIXME

		if($app_state['commited']) return;

		$this->header('content-type', $mime);

		$basename = basename($filename);
		$this->header('content-disposition', 'attachment; filename="' . urlencode($basename) . '');

		$this->sendHeaders();
		self::$app_state['commited'] = true;

		$size = filesize($filename);
		$fp = fopen($filename, "r");
		while( !feof($fp) ){
			print fread($fp, 65536);
			flush(); // this is essential for large downloads
		}
		fclose($fp);

		$this->end();
	}
	public function write($a){
		if( is_string($a) ){
			self::$app_state['buffer'] .= $a;
		}elseif( is_object($a) && method_exists($a, "getHTML") ){
			self::$app_state['buffer'] .= $a->getHTML();
		}
	}
}
class processController extends \exc\core\controller {
	public $location = '';
	public $scope = '';

	public static function run(){

	}
	public function appController(){
		global $app_controller;
		return $app_controller;
	}
}
class viewController extends \exc\core\controller {
	public $location = '';
	public $scope = '';

	public static function run(){

	}
	public function appController(){
		global $app_controller;
		return $app_controller;
	}
}

}
?>