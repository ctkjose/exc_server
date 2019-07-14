<?php
namespace exc{
class app {
	public static $RUNMODE = 1;
	public static $appController = null;
	public static $firstResponder = null;
	public static $scopeAction = 'main.default';
	public static $controllers = [];
	
	public static $app_state = [ 'header'=>[], 'buffer'=>'', 'commited'=>false, 'config'=>[] ];
	public $properties = array();

	public static function controller(){
		return self::$appController;
	}
	public static function setFirstResponder($any){
		$cn = $any;
		if(!isset(self::$controllers[$cn])) return; //fail silently?

		$o = self::$controllers[$cn]['instance'];
		if(!self::$appController->performMessage("canBecomeFirstResponder", [$cn, $o])) return;
		self::$firstResponder = $o;
		
		\exc\options::$values['app']['firstresponder'] = $cn;

		if(self::$firstResponder instanceof \exc\controller\viewController){ //do we need a client
			//if( \exc\options::key('/app/with_client') == 1) return;
			//\exc\bootloader::addModule('exc://exc.ui', []);
		}

		
		//error_log_dump(self::$firstResponder, '$firstResponder');
	}
	public static function init(){
	
	}
	public static function findPathForController($cn, $basePath = null){
		$r = is_string($basePath) ? $basePath : EXC_PATH_BASE;
		chdir($r);

		$cn = 'controller.' . $cn  . '.php';
		$p = realpath('./' . $cn);
		while($p === false){
			$r = realpath('../');
			if($r === false) break;
			
			if(strpos($r, EXC_DOCUMENT_ROOT) !== 0){
				error_log('[EXC][APP][ERROR] Exausted paths looking for [' . $cn . '] reached [' . $r . '].' );
				break;
			}
			chdir($r);
			$p = realpath('./' . $cn);
		}

		return $p;
	}
	public static function loadApp($basePath = null){

		$p = self::findPathForController('app', $basePath);
		$app_path = $basePath;
		if($p === false) {
			$o = new \exc\controller\appController();
			\exc\options::key('/app/path/base', $basePath);


			
		}else{
			error_log('[EXC][APP] FOUND APP CONTROLLER AT [' . $p . ']');
			$app_path = dirname($p) . '/';
			\exc\options::key('/app/path/controller', $p);
			$o = \exc\core\controller::loadControllerWithPath('appController', $p);
		}

		if(!$o){
			error_log('[EXC][APP][ERROR] Unable to create instance of appController');
			return false;
		}

		define('EXC_PATH_APP', $app_path);
		\exc\path::addUP('app', $app_path);
		\exc\options::key('/app/path/base', $app_path );

		self::$appController = $o;
		self::$firstResponder = self::$appController;
		

		$options =[];
		if( method_exists($o, 'config') ){
			$options = call_user_func([$o, 'config'],[]);

			if(is_array($options)){
				
				if(is_array($options['using'])){
					\exc\bootloader::processOptionUsing($options['using']);
				}
				
				foreach($options as $k=>$v){
					\exc\options::$values['app'][$k] = $v;
				}
			}
		}
		

		return true;
	}
	public static function runWithAction($a){
		$app = self::$appController;

		self::$scopeAction = \exc\app::$firstResponder->scopeName;
		self::$scopeAction .= '.' . strtolower($a);

		$app->publish('appInit', []);
		
		
		if(isset(\exc\bootloader::$route['request_url'])){
			$app->publish('withRequestURL', [\exc\bootloader::$route['request_url']]);
		}

		if(!\exc\session::hasKey('AISR')){
			error_log('[EXC][APP] Forcing action AppStart instead of [' . $a . ']');
			//$a = 'AppStart';
		}

		$a1 = $app->performMessage("willDispatchAction", [$a] );
		if(is_string($a1) && strlen($a1) && ($a!=$a1) ){
			error_log('[EXC][APP] Action modified from [' . $a . '] to [' .$a1 . ']');
			$a = $a1;
		}

		if(!\exc\session::hasKey('eas')){
			//self::runApplication($app);
		}else{
		
		}

		error_log_dump(self::$firstResponder , 'firstResponder');
		if( \exc\core\controller::isControllerInstance(self::$firstResponder) ){
			self::$firstResponder->performMessage('action_' . $a,[]);
		}


		$app->end();
	}
	public static function getController($cn){
		if(!isset(self::$controllers[$cn])) return null; //return $this;
		return self::$controllers[$cn]['instance'];
	}
	public function registerController($cc, $p=null){
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

		$o->scopeName = $cn;

		error_log("[EXC][APP] appControllerLoad [" . $cp . '][' . $cc . ']' );
		self::$controllers[$cn] = ['name'=>$cn, 'class'=>$cc, 'instance'=>$o, 'path'=>$cp];
	}
	
	public static function client($a=null){
		static $c = null;
		if(!is_null($c)) return $c;
		
		$c = client::instance(\exc\bootloader::$route);
		return $c;
	}
}


} //namespace exc
namespace exc\controller {


class appController extends \exc\core\controller {
	
	public $scopeName = 'app';
	public static $app_state = [ 'headers'=>[], 'buffer'=>'', 'commited'=>false, 'ended'=>false, 'config'=>[] ];
	public static $defaulView = null;
	public static $client= null;
	
	public static function init(){
		global $app_controller, $client_controller, $app;
		$app_controller = new appController();
		$app = $app_controller;

		self::$app_state['config'] = ['uid'=>'', 'directory'=> '', 'url'=>'' ,'lng'=>'en','location'=> 'DEFAULT',  'action'=> '', 'action_default'=> '' ];

	}
	public static function setAppState($n, $v){
		self::$app_state[$n] = $v;
	}
	public static function instance(){
		return \exc\app::$appController;
	}
	public function client(){
		if(!is_null(self::$client)) return self::$client;
		self::$client = \exc\client::instance(\exc\bootloader::$route);
		return self::$client;
	}
	public function getOption($n, $default=null){
		if(!isset(\exc\options::$values['app'][$n])) return $default;
		return \exc\options::$values['app'][$n];
	}
	public function setOption($n, $v){
		\exc\options::$values['app'][$n] = $v;
	}
	public function getDefaultView(){
		return self::$defaulView;
	}
	public function makeViewDefault($any){

		if( is_object($any) && is_a($any, '\exc\view') ){
			$view = $any;
		}elseif( is_string($any) && (strlen($any)>0) ){ //name of a view
			$view = \exc\view::load($any);
		}

		if(is_null($view)) return null;


		self::$defaulView = $view;
		
		$fn = function() use ($view){
			error_log("@viewCommit............");
			if($view == null) return;
			if($view->state['commited']) return;

			$this->publish("viewCommit", [ $view ] );
		
			$view->inline->write('');
	
			$js = "<script type='text/javascript' id='excbl'>\n";
			$js.= $this->getJSObject();
			$js.= "</script>";

			$view->body_end->write( $js );
			$this->write($view);
		};

		$fn->bindTo($this, $this);
		$this->on('appSendOutput', $fn);


		$scope = \exc\app::$scopeAction;

		if(is_array(\exc\options::$values['app']['view.copy'])){  //refactor...

			foreach(\exc\options::$values['app']['view.copy'] as $sk => $entries){
				if( ($sk != '*') && ($sk!=$scope)) continue;
				error_log('[EXC][APP] view.copy ' . $sk . '');
				foreach($entries as $e){
					$url = ''; $wait = true; $type='html';
			
					if(is_string($e)){
						$url = $e;
					}elseif(is_array($e) && isset($e['url'])){
						$url = $e['url'];
						if(isset($e['wait'])) $wait = $e['wait'];
						if(isset($e['type'])) $type = $e['type'];
					}

					if($type=='css'){
						$view->css->copy($url);
					}elseif($type=='js'){
						$view->js->copy($url);
					}
				}
			}
		}


	}
	public function canBecomeFirstResponder($cn, $obj){
		//request to change first responder, return true to allow it
		return true;
	}
	public function appStart(){
		error_log("================     @app->appStart =================");
		$bs = \exc\session::key("BS");

		$uid = uniqid('A');
		$sid = session_id();
		$backend_key = sha1( $uid . '-' . $sid);
		
		\exc\session::key("ABK", $backend_key);
		\exc\session::key("AUID", $uid);

		\exc\options::$values['app']['ABS'] = $bs;
		\exc\options::$values['app']['ABK'] = $backend_key;
		\exc\options::$values['app']['AUID'] = $uid;

	
		$fn = function($view) use ($uid, $sid, $backend_key){
			error_log("@appStart viewCommit............");
			if($view == null) return;
			if($view->state['commited']) return;

			$js = "<script type='text/javascript' src='bootloader.init'></script>\n";
			$view->body_end->write( $js );
		};
		$this->on('viewCommit', $fn);


		$this->publish("appStart", [$this]);
	}
	public static function getJSObject(){
		global $config;

		$uid_name = 'main';
		$js_app = "<script type='text/javascript'>//exc app\n";
		
		$uid = uniqid('APPK');
		$sid = session_id();
		$backend_key = sha1( $uid . '-' . $sid);
		
		\exc\session::key("ABK", $backend_key);
		\exc\session::key("AUID", $uid);

		
		$jsb = \exc\helper\script::load("backend.js");
		$jsb->bms = 'R' . sha1(\exc\session::key("BS") . '-' . session_id() );
		$jsb->uid = $uid;
		$jsb->sk = $backend_key;

		if(!is_null(\exc\controller\appController::$client)){
			$js_st = \exc\controller\appController::$client->getState();
		}else{
			$js_st = '';
		}

		$jsa = \exc\helper\script::load("app.js");
		$jsa->bk = $jsb;
		$jsa->app_state = $js_st;

		return $jsa->source();
	}
	public function sendBootloader(){
		$uid_name = 'main';
		$js_app = "<script type='text/javascript'>//exc app\n";
		
		$uid = uniqid('APPK');
		$sid = session_id();
		$backend_key = sha1( $uid . '-' . $sid);
		
		\exc\session::key("ABK", $backend_key);
		\exc\session::key("AUID", $uid);

		
		$jsb = \exc\helper\script::load("backend.js");
		$jsb->bms = 'R' . sha1(\exc\session::key("BS") . '-' . session_id() );
		$jsb->uid = $uid;
		$jsb->sk = $backend_key;

		$client = \exc\client::instance();
		$js_st = $client->getState();

		$jsa = \exc\helper\script::load("app.js");
		$jsa->bk = $jsb;
		$jsa->app_state = $js_st;

		return $jsa->source();$uid_name = 'main';
		$js_app = "<script type='text/javascript'>//exc app\n";
		
		$uid = uniqid('APPK');
		$sid = session_id();
		$backend_key = sha1( $uid . '-' . $sid);
		
		\exc\session::key("ABK", $backend_key);
		\exc\session::key("AUID", $uid);

		
		$jsb = \exc\helper\script::load("backend.js");
		$jsb->bms = 'R' . sha1(\exc\session::key("BS") . '-' . session_id() );
		$jsb->uid = $uid;
		$jsb->sk = $backend_key;

		$client = \exc\client::instance();
		$js_st = $client->getState();

		$jsa = \exc\helper\script::load("app.js");
		$jsa->bk = $jsb;
		$jsa->app_state = $js_st;

		return $jsa->source();
		

	}
	

	
	public function loadAppControllers(){
		$o = \exc\options::$values['app'];
		$cr = ( isset($o['controllers']) && is_array($o['controllers']) ) ? $o['controllers'] : [];

		$i = 0;
		foreach($cr as $cc => $p){
			$r = \exc\path::normalize($p);
			\exc\app::registerController($cc, $r['path']);
		}


	}
	
	public function initializeUI(){
		if( \exc\options::key('/app/with_client') == 1) return;
		\exc\bootloader::addModule('exc://exc.ui', []);
	}
	function publish($evtName, $param=[]){
		///N:Raises an event, the event gets send to observers registered for this event
		$kmsg = $evtName;
		foreach(\exc\app::$controllers as $cn => $ce){
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

		$this->publish("appSendJSON", []);
		
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
		$this->publish("appSendJS", []);
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
	public function sendDownloadWithData($mime, $data, $filename=null){

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
		return \exc\app::controller();
	}
}
class viewController extends \exc\core\controller {
	public $location = '';
	public $scope = '';

	public static function run(){

	}
	public function appController(){
		return \exc\app::controller();
	}
}

}
?>