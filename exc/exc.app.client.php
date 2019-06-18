<?php
namespace exc;
class client_interactions {
	public static $cmd = [];
	public static function getCommands(){
		return self::$cmd;
	}
	public static function addCMD($e){
		self::$cmd[] = $e;
	}

}
class action {
	const TYPE_BACKEND=1;
	const TYPE_URL=2;
	public $type = 1;
	public $action = 'default';
	public $controller = 'main';
	public $scope = '';
	public $url = null;
	public $params = [];

	public static function init($action, $params=[]){
		$a = new action();

		if(preg_match('/\@\(([A-Za-z0-9_\.\-\/]+)\)/', $action, $m)){
			$a->type = action::TYPE_BACKEND;
			$a->action = $m[1];

			$p = explode('/', $a->action);

			$i = 0;
			$c = count($p);
			if( strlen($p[0]) == 0 ){ $c--; $i++; }
			if($c > 1){
				$a->action = array_pop($p);
				$u = implode('/',$p);
				$a->scope = $u;
		   }

			if(preg_match('/([A-Za-z0-9_\-\/]+)\.([A-Za-z0-9_\-\/]+)/', $a->action, $m)){
				//has controller
				$a->controller = $m[1];
				$a->action = $m[2];
			}
		}elseif(preg_match('/url\(([A-Za-z0-9_\.\-\/\?\+\=\&\:\@]+)\)/', $action, $m)){
			$a->type = action::TYPE_URL;
			$a->url = $m[1];
		}

		$a->params = $params;
		return $a;
	}
	public function getURL(){
		if($this->type == action::TYPE_URL){
			return $this->url;
		}
		if($this->type == action::TYPE_BACKEND){

		}
	}
}
class client {
	private static $instance=null;
	public $data = [];
	public $session = [];
	public $values = [];
	public $views = [];
	public $payloads = [];
	public $pathsCompiled = [];

	public static $ready = '';
	public static $tplInteraction = '';
	public static $datasets = [];
	private static  $datasets_paths = [];
	public static $state = ['name'=> 'main', 'config'=>[], 'datasets'=>[] ];

	
	public static function content($a=null){
		self::$state['name'] = 'content';
		return self::instance($a);
	}
	public static function app($a=null){
		self::$state['name'] = 'main';
		return self::instance($a);
	}
	public static function instance($a=null){
		if(!is_null(self::$instance)) return self::$instance;

		self::$instance = new client();
		self::$state['config'] = ['uid'=>'', 'directory'=> '', 'url'=>'' ,'lng'=>'en','location'=> 'DEFAULT',  'action'=> '', 'action_default'=> '' ];

		self::$tplInteraction = file_get_contents(__DIR__ . '/assets/interaction.js');
		
		$app = \exc\controller\appController::instance();

		if(is_object($a) && is_a($a, 'exc\router')){
			error_log("@client::instance() with router");
			self::$instance->values = $a->route['values'];
			//unset($a->route['values']);
			$state = $a->route['state'];
			if(is_array($state)){
				if(isset($state['session'])){
					self::$instance->session = $state['session'];
				}
				unset($a->route['state']['session']);
			}
		}elseif(is_array($a)){
			if(isset($a['api_json_state'])){
				$state = json_decode($a['api_json_state'], true);
				if(is_array($state)){
					if(isset($state['session'])){
						self::$instance->session = $state['session'];
					}
				}
			}
			if(isset($a['api_json_data'])){
				self::$instance->values = json_decode($a['api_json_data'], true);
			}
		}else{
			error_log("@client::instance() with no values");
		}

		if(!isset(self::$state['name'])) self::$state['name'] = 'main';
		if(!isset(self::$state['datasets'])) self::$state['datasets'] = [];


		$app->publish( 'appClientReady', [self::$instance] );
		return self::$instance;
	}
	public function session($n, $v=null){
		$ms = \exc\session::key("ms");
		if(is_null($v)){
			if(isset($ms[$n])) return $ms[$n];
			return null;
		}
		
		$ms[$n] = $v;
		\exc\session::key("ms", $ms);
		return $this;
	}
	public function sessionHasKey($n){
		$ms = \exc\session::key("ms");
		return isset($ms[$n]);
	}
	public function sessionRemove($n){
		$ms = \exc\session::key("ms");
		if(isset($ms[$n])) unset($ms[$n]);
		\exc\session::key("ms", $ms);
		client_interactions::addCMD(['cmd'=>'msr', 'args'=>[$n]]);
		
		return $this;
	}
	public function done(){
		if(isset($this->with_selector) && !is_null($this->with_selector)){
			$this->with_selector = null;
			return $this;
		}
		$app = \exc\controller\appController::instance();
		$js = $this->getState();
		
		$app->sendJS( $js );
	}
	public function publish($msg, $params){
		client_interactions::addCMD(['cmd'=>'publish', 'args'=>[$msg, $params]]);
		return $this;
	}
	public function addView($name, $v){
		if( !isset($this->payloads['views']) ) $this->payloads['views'] = [];
		
		
		$o = ['name'=> $name ];
		if(is_string($v)){
			$o['url'] = $v;
		}elseif(is_array($v)){
			$o = $v;
		}elseif(is_object($v)){
			$o['html'] = $v->getHTML();
		}
		
		$this->payloads['views'][] = $o;
		
	}
	public function setData($name, $value){
		if( !isset($this->payloads['data']) ) $this->payloads['data'] = [];
		$this->payloads['data'][$name] = $value;
		$this->runCode('if(self.app.data) self.app.data.' . $name . '=' . json_encode($value) . ';');
		return $this;
	}
	public function sendResponseData( $value){
		$this->payloads['rdata'] = $value;
		if( !isset($this->payloads[$key]) ) $this->payloads[$key] = '';
		return $this;
	}
	public function addJSFile($p){
		$this->addFileToKey($p, 'js');
		return $this;
	}
	public function addCSSFile($p){
		$this->addFileToKey($p, 'style');
		return $this;
	}
	public function addController($n, $p=null){
		if(!is_null($p)) $this->addFileToKey($p, 'js');
		$this->runCode('exc.app.loadControllerByName("' . $n . '");');
		return $this;
	}
	public function addFileToKey($p, $key){
		$path = '';
		if(is_string($p)){
			$f = \exc\path::normalize($p);
			if(!$f['exists']) return;
			$path = $f['path'];
		}elseif(is_array($p) && isset($p['path'])){
			$path = $p['path'];
		}elseif(is_array($p) && isset($p['url'])){
			$path = $p['url'];
		}
		//error_log("@client->addFileToKey({$path})");
		if(in_array($path, $this->pathsCompiled)) return;
		$this->pathsCompiled[] = $path;
		$s = file_get_contents($path);
		if(strlen($s) == 0) return;

		if( !isset($this->payloads[$key]) ) $this->payloads[$key] = '';
		$this->payloads[$key] .= $s;

		return $this;
	}
	public function callback(){
		
		if(!isset($this->callback)){
			$this->callback = ['params'=>[]];
		}
		$args = func_get_args();
		if(count($args) > 0){
			$this->callback['params'] = $args;
		}
	
	}
	static public function ready($js){
		///N:Add code to the ready event...
		self::$ready .= "\n\n//--------\n" . $js;
	}
	public function toJSON(){
		global $config;

		$l = 'en';
		$o =[ 'lang' => $l];

		$o['manifest'] = \exc\manifest::getManifest();
		$o['datasets'] = [];
		$o['interactions'] = client_interactions::getCommands();
		$o['data'] = $this->data;

		if(function_exists("session_id")) $o['ms'] = session_id();

		$o['config'] = self::$state['config'];

		if(isset($o['config']) && isset($o['config']['lng']) ){
			$o['lang'] = $o['config']['lng'];
		}

		return json_encode($o);
	}
	
	public static function getJSObject1(){
		//returns the JS for the client module
		error_log("here @ getJSObject");
		$d = debug_backtrace();
		$c = count($d);

		for($i = 1; $i< $d; $i++){
			if(!isset($d[$i])) break;

			$fn = $d[$i]['function'];
			$f = (isset($d[$i]['file'])) ? $d[$i]['file'] : '';
			$ln = (isset($d[$i]['line'])) ? $d[$i]['line'] : '--';
			if ((substr($fn, 0, 7) == 'include') || (substr($fn, 0, 7) == 'require')) {
				$fn = '';
			}else{
				$fn .= '( )';
			}

			$f = str_replace($_SERVER['DOCUMENT_ROOT'], '', $f);
			error_log("DEV ERROR TRACE::{$f}::LINE {$ln}::{$fn}");
		}

		$uid_name = 'main';
		$js_app = "<script type='text/javascript'>//exc datasets\n";

		foreach(self::$datasets_paths as $k => $d){
			$js_app.= 'var ' . $d['uid'] . '=' . $d['contents'] . ";\n";
		}

		$js_app.= "</script>\n";
		$js_app.= "<script type='text/javascript'>//exc app 2\n";

		$uid = uniqid('APPK');
		if(isset(self::$state['name'])){
			if( isset(self::$state['moduleController']) && (self::$state['name'] == self::$state['moduleController']) ){
				 self::$state['name'] .= '_' . $uid;
			}
			$uid_name = self::$state['name'];
		}

		if(isset(self::$state['moduleController']) && !empty(self::$state['moduleController']['js']) ){
			$js_app.= self::$state['moduleController']['js'] . "\n\n";
		}

		$js_app.= "var {$uid} = " . self::$instance->toJSON() . ";\n";
		$js_app.= "{$uid}.name = '{$uid_name}';\n";
		$js_app.= "{$uid}.moduleLoaded = false;\n";

		if(isset(self::$state['moduleController'])){
			$js_app.= $uid . '.moduleController = "' . self::$state['moduleController']['name'] . "\";\n";
		}

		$js_app.= "//assign named datasets\n";
		foreach(self::$state['datasets'] as $k => $d){
			$js_app.= "{$uid}.datasets." . $k . "=" . $d . ";\n";
		}

		$js_app.= $uid . '.app_event_start = function(){' . self::$ready . "};\n";

		$js_app.= 'exc.app.bootstrapModule(' . $uid . ");\n";
		$js_app.= "</script>";
		return $js_app;
	}
	public function getState(){

		$app = \exc\controller\appController::instance();
		$app->publish("appClientCommit", []);
		


		$uid = strtoupper(uniqid('EXCMOD'));
		
		$js = self::$tplInteraction;
		$jsp = '';
		$e = [
			'_bst'=> '1.0',
			'status'=> 200,
			'uid' => $uid,
			'payloads'=> []
		];
		
		foreach(['js', 'style', 'views'] as $kc){
			if(!isset($this->payloads[$kc])) continue;
			$e['payloads'][$kc] = &$this->payloads[$kc];
		}

		if(isset($this->payloads['rdata'])){
			$jsp.= 'if(bd) bd = ' . json_encode($this->payloads['rdata']) . ";\n";
		}

		$url = $app->getOption('urls',[]);
		
		$e['opSessionName'] = $app->getOption('uid') . "_BS";
		//$e['bms'] = 'R' . sha1(\exc\session::key("BS") . '-' . session_id() );

		//save session		
		$ms = \exc\session::key("ms");
		foreach($ms as $k => $v){
			client_interactions::addCMD(['cmd'=>'mss', 'args'=>[$k, $v]]);
		}

		if(isset(self::$state['moduleController'])){
			$m = self::$state['moduleController'];
			$e['defaultController'] = $m['name'];
			$js.= $m['js'] . "\n\n";
		}
		
		//$e['manifest'] = \exc\manifest::getManifest(); //redo manifest
		$e['interactions'] = client_interactions::getCommands();
		
		$js = str_replace('{{jsr}}',self::$ready, $js);

		$jsst = json_encode($e);
		$js = str_replace('{{js}}','var st=' . $jsst . ";\n", $js);

		$e['data'] = $this->data;
		$js = str_replace('{{payload}}',$jsp, $js);
		return $js;
	}
	public function getState1(){

		$app = \exc\controller\appController::instance();
		$app->publish("app_client_commit", []);


		$uid = strtoupper(uniqid('EXCMOD'));
		$stn = \exc\client::$state['name'];
		
		$js = '';
		
		$e = [
			'uid' => $uid,
			'name' => $stn,
			'defaultController'=>'',
			'origin' => [],
		];
		
		$url = $app->getOption('urls',[]);
		
		$e['origin']['base'] = $url['base'];
		$e['origin']['controller'] = $url['controller'];
		$e['origin']['controller_directory'] = $url['controller_directory'];
		
		error_log(print_r($_SERVER, true));
		
		if(isset(self::$state['moduleController'])){
			$m = self::$state['moduleController'];
			$e['defaultController'] = $m['name'];
			$js.= $m['js'] . "\n\n";
		}
		
		$e['manifest'] = \exc\manifest::getManifest();
		$e['interactions'] = client_interactions::getCommands();
		$e['data'] = $this->data;
		$e['session'] = $this->session;
		$e['config'] = \exc\controller\appController::$app_state['config'];
		
		if(function_exists('session_id')) $e['ms'] = session_id();
		

/*
		$js.= $e['name'] . '.datasets = {}' . ";\n";

		if(isset(self::$state['datasets']) && is_array(self::$state['datasets'])){
			foreach(self::$state['datasets'] as $k => $d){
				$js.= $e['name'] . '.datasets.' . $k . "=" . json_encode($d) . ";\n";
			}
		}
*/

		$js.= "\nexc.app.loadState(" . json_encode($e) . ");\n";

		if(strlen(self::$ready) > 0){
			$js.= 'exc.controller.ready(function(msg){' . self::$ready . "});\n";
		}
		return $js;
	}
	public function setName($name){
		self::$state['name'] = $name;
	}
	
	public function setDeafultController($className, $url = null){ //TODOFIX using REA_BASE_PATH
		$e = ['name'=> $className];

		if(!empty($url)){
			$path = \exc\path::normalize($url);

			if(!is_array($path) || !isset($path['url']) || (strlen($path['url'])==0) ) return;
			//$e['js'] = file_get_contents(REA_BASE_PATH . substr($path['url'],1));

			$e['js'] = file_get_contents($path['path']);
		}

		self::$state['moduleController'] = $e;
	}
	public function addDataset($name, $path){
		if(isset(self::$datasets_paths[$path])){
			self::$state['datasets'][$name] = self::$datasets_paths[$path]['uid'];
			return;
		}


		//error_log("dataset==" . $path1);
		if(!isset(self::$datasets_paths[$path])){
			$path1 = \exc\path::normalize($path);
			self::$datasets_paths[$path] = ["contents"=>file_get_contents($path1['path']), "uid"=> 'dataset_' . uniqid()];
		}

		self::$state['datasets'][$name] = self::$datasets_paths[$path];
	}
	public static function addClientInteractions($fnList = null){
		$aList = $fnList;
		if(!is_array($fnList)){
			$list = get_defined_functions();
			$aList = $list['user'];
		}

		$js = "";
		$js_methods = [];
		foreach($aList as $fn_name){ ///TODO refactor this
			if( substr($fn_name,0,17 ) != 'app_client_event_') continue;
			$evt = substr($fn_name,17);
			$fn = 'app_event_' . $evt  . ':function(msg){' . "\nconsole.log('at client event'); console.log(msg); ";

			$fn.= 'var p = {field_name: "", event_name: "", value:""}; if(typeof(msg) !== "object") return;
			if(msg.hasOwnProperty("name")) p.field_name = msg.name;
			if(msg.hasOwnProperty("event_name")) p.event_name = msg.event_name;
			console.log(msg.widget);
			p.value = msg.widget.getValue();
			';
			$fn.= 'exc.app.backend.sendAction( new exc.app.backend.action("@(' . $evt . ')"), p, function(){ console.log("action done 1"); }, function(){ console.log("action done 2"); } );' . "\n";
			$fn.= "}";
			$js_methods[] = $fn;
		}

		if(count($js_methods) > 0){
			$js = 'var app_generic_controller = {' . "\n";
			$js.= implode(",\n", $js_methods);
			$js.= "};\n";
			$js.= "exc.controller.installController(app_generic_controller);\n";
			self::ready($js);
		}
	}
	
	public function runCode($js){
		client_interactions::addCMD(['cmd'=>'js', 'args'=>["fn = function(){" . $js . "};"]]);
		return $this;
	}
	public function widget($sel){
		$this->with_selector = $sel;
		return $this;
	}
	public function panel($sel){
		$this->with_selector = $sel;
		return $this;
	}
	public function __call($name, $args){
		if(is_null($this->with_selector)){
			return $this;
		}
		$js_args = [];

		foreach($args as $v){
			if(is_string($v)){
				$js_args[] = var_export($v, true);
			}elseif(is_numeric($v)){
				$js_args[] = '' . $v;
			}elseif(is_bool($v)){
				$js_args[] = ($v)? 'true' : 'false';
			}elseif(is_array($v)){
				$js_args[] = json_encode($v);
			}
		}

		$js = "var any = $.component('" . $this->with_selector . "');";
		$js.= "if(typeof(any) == 'undefined') return;";
		$js.= "any.{$name}(" . implode(',', $js_args) . ');';
		
		$this->runCode($js);
		return $this;

	}
	public function setAction1($action){
		client_interactions::addCMD(['cmd'=>'js', 'args'=>["fn = function(){if(exc.ui.events.submitOwner) exc.ui.events.submitOwner.setAction('" . $action  . "');};"]]);
		return $this;
	}
	public function sendInteraction($cmd, $args=[]){
		client_interactions::addCMD(['cmd'=>$cmd, 'args'=>$args]);
		return $this;
	}
	public function showMessage($m){
		client_interactions::addCMD(['cmd'=>'displayMsg', 'args'=>[$m]]);
		return $this;
	}
	public function displayMessage($msg, $style='green', $sel='.rea-body-with-menu'){
		client_interactions::addCMD(['cmd'=>'displayMessage', 'args'=>[$msg,$style, $sel]]);
		return $this;
	}

	public function showError($sel, $m){
		client_interactions::addCMD(['cmd'=>'errorSet', 'args'=>[$sel, $m]]);
		return $this;
	}
	public function removeError($sel, $m = ''){
		client_interactions::addCMD(['cmd'=>'errorRemove', 'args'=>[$sel, $m]]);
		return $this;
	}
	public function redirect($url, $m = 'Redirecting...'){
		client_interactions::addCMD(['cmd'=>'redirect', 'args'=>[$url, $m]]);
	}
	public function replaceContentWithURL($url, $m = 'Please wait...'){
		client_interactions::addCMD(['cmd'=>'pushReplace', 'args'=>[$m, $url]]);
	}
	public function replaceContentWithHTML($html, $m = 'Please wait...'){
		client_interactions::addCMD(['cmd'=>'pushReplace', 'args'=>[$m, $html]]);
	}
}
?>