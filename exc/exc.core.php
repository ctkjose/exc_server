<?php
namespace exc\core;

if( defined('EXCSERVERMODE') ){
	define('REASG_ENGINE_PATH', dirname(dirname(__FILE__)) . '/');
	define('REASG_ENGINE_JS_PATH', REASG_ENGINE_PATH . 'js/');
	define('REASG_ENGINE_PHP_PATH', REASG_ENGINE_PATH . 'server/');

	define('REASG_ROOT_PATH', dirname(REASG_ENGINE_PATH) . '/');


	define('REASG_DOC_ROOT', $_SERVER['DOCUMENT_ROOT']);

	ini_set(' memory_limit', '-1');
	ini_set('display_errors', 0);
	ini_set('log_errors', 1);
	ini_set('error_log','');
	error_reporting(E_ALL);

}
class base {
	public static function extend($a) {
		$others = func_get_args();
		for($i=1;$i<count($others); $i++){
			$b = $others[$i];
			if(is_object($b)){
				$keys= array_keys(get_object_vars($b));
				foreach($keys as $k){
					if( is_object($a) ){
						$a->$k = $b->$k;
					}elseif(is_array($a)){
						$a[$k] = $b->$k;
					}
				}
			}elseif(is_array($b)){
				foreach($b as $k => $v){
					if( is_object($a) ){
						$a->$k = $v;
					}elseif(is_array($a)){
						$a[$k] = $v;
					}
				}
			}
		}
		return $a;
	}
	public function __call($key,$params){
		$handler = $this->getMessageHandler($msg);
		if(!is_null($handler)){
			return call_user_func_array($handler, $args);
		}
		return null;
    }
	public function getOwnPropertyNames(){
		return get_object_vars($this);
	}
	public function keys(){
		$a = get_object_vars($this);
		return array_keys($a);
	}
	public function hasOwnProperty($a){
		if(isset($this->$a)) return true;
		if(method_exists($this, $a)) return true;
		return false;
	}
	public function performMessage($msg, $args=[]){
		$handler = $this->getMessageHandler($msg);
		if(is_null($handler)){
			return $this->unhandled($msg);
		}

		return call_user_func_array($handler, $args);
	}
	public function getMessageHandler($msg){
		
		if( method_exists($this, $msg) ){
			return [$this, $msg];
		}
		static $methods = null;

		if(is_null($methods)){
			$methods = get_class_methods(get_class($this));
		}
		//error_log("@base->getMessageHandler(" . $msg . ")");
		$msg = strtolower($msg);
		
		foreach($methods as $mn){
			if( preg_match ( "/^on([A-Za-z0-9\\.\\-_]+)$/",$mn, $m)){
				if(strtolower($m[1]) == $msg) return [$this, $mn];
			}
		}

		$props = get_object_vars($this);
        foreach($props as $mn => $p){
			if( ($p instanceof \Closure)){
				if(strtolower($mn) == $msg) return $p;
				if( preg_match("/^on([A-Za-z0-9\\.\\-_]+)$/",$mn, $m) ){
					if(strtolower($m[1]) == $msg) return [$this, $mn];
				}
				return $p;
			}
        }
		return null;
	}
	public function hasMessage($msg){
		return method_exists($this, $msg);
	}
	public function isInstanceOf($class){
		return is_a($this, $class);
	}
	public function unhandled($msg){
		//message not implemented
		error_log('[EXC] MESSAGE [' . $msg . '] NOT IMPLEMENTED');
	}
	public static function isBaseInstance($o){
		if(!is_object($o)) return false;
		if(!is_a($o, 'exc\core\base')) return false;
		return true;
	}
}

trait objectDecorated {
	public function __call($key, $params){
        if (isset($this->$key) && $this->$key instanceof \Closure) {
            return call_user_func_array($this->$method, $params);
        }
	}
}
trait ObjectBase {
	public function getOwnPropertyNames(){
		return get_object_vars($this);
	}
	public function keys(){
		$a = get_object_vars($this);
		return array_keys($a);
	}
	public function hasOwnProperty($a){
		if(isset($this->$a)) return true;
		if(method_exists($this, $a)) return true;
		return false;
	}
}
trait objectExtendable {
    public function __call($name, $args) {
        if (is_callable($this->$name)) {
			return call_user_func_array($this->$name, $args);
        }else {
            return null;
        }
    }
    public function __setDISABLE($name, $value) {
        $this->$name = is_callable($value) ? $value->bindTo($this, $this) : $value;
    }
	public function delegate($name, $fn) {
		if(!is_callable($fn)) return;
        $this->$name = $fn;
    }
	public function addMethod($name, $fn) {
		if(!is_callable($fn)) return;
        $this->$name = $fn->bindTo($this, $this);
    }
	public function delegateFor($name){
		if( !method_exists($this,$name) ) return function(){};

		$obj = $this;
		$method = $name;

		$fnc = function() use($obj, $method) {
			$args = func_get_args();
			call_user_func_array([$obj,$method], $args);
		};

		$fnc->bindTo($this, $this);
		return $fnc;
	}
}

class controller extends \exc\core\base {
	use \exc\core\objectExtendable;

	private $event_map = [];
	public static function isControllerInstance($o){
		if(!is_object($o)) return false;
		if(!is_a($o, 'exc\core\controller')) return false;
		return true;
	}
	public static function loadControllerWithPath($class, $path){
		if(!file_exists($path)) return;

		$frs = '\\' .  $class;
		include($path);

		$o = new $frs;
		if( method_exists($o, 'initialize') ){
			call_user_func([$o, 'initialize'],[]);
		}

		return $o;
	}
	public static function registerHandlers($target, $controller){

		$methods = get_class_methods(get_class($controller));
		$matches = preg_grep("/^on([A-Za-z0-9\\.\\-_]+)$/", $methods);
		foreach($methods as $mn){
			if( preg_match ( "/^on([A-Za-z0-9\\.\\-_]+)$/",$mn, $m)){
				$evt = strtolower($m[1]);
				error_log("Install " . $mn . "=" . $evt);
				$target->on($evt, [$controller, $mn]);
			}
		}
	}
	public function on($evtName, $callBackMethod, $evtData=NULL, $callbackName=NULL){
		///N:Allows others to register for an event.
		$this->event_map[$evtName][] = ['callback'=> $callBackMethod, 'data'=> &$evtData, 'name' => $evtName];
		return $this;
	}
	public function publish($evtName, $param=[]){
		///N:Raises an event, the event gets send to observers registered for this event
		//error_log('--------------------- @CONTROLLER->PUBLISH(' . strtoupper($evtName) .')');

		if(!array_key_exists($evtName, $this->event_map)) return;
		foreach( $this->event_map[$evtName] as $entry){
			$param['evtData'] = $entry['data'];
			$m = $entry['callback'];
			call_user_func_array($m, $param);
		}

		return $this;
	}
	function off($evtName, $callbackName=null) {
		if(is_null($callbackName)) {
			$this->event_map[$evtName] = [];
		} else {
			foreach($this->event_map[$evtName] as $id => $callback) {
				if($callback['name'] == $callbackName) {
					unset($this->event_map[$evtName][$id]);
				}
			}
		}
	}
}
?>