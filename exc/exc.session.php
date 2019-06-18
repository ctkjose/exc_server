<?php
namespace exc;

class session extends \exc\core\base {
	public static $store = ["ms"=>[]];
	public static function initialize(){
		error_log("@exc/session/manager/initialize()");

		$ops = [
			"name"=> "BS120",
			"use_only_cookies"=> 1,
			"cookie_httponly"=>1,
			"use_trans_sid"=>0,
			"gc_maxlifetime"=>1400,
			"cookie_lifetime"=> 0,
		];

		if(isset(\exc\options::$values['app']['session'])){
			$ops = \exc\core\base::extend($ops,\exc\options::$values['app']['session']);
		}

		session_start($ops);

		$app = \exc\controller\appController::instance();

		if(isset($_SESSION['BS120'])){
			self::$store = $_SESSION['BS120'];
			if(!isset(self::$store['ms'])) self::$store['ms'] = [];
		}else{
			self::$store['BS'] = strtoupper(uniqid('BS'));
			$app->publish("sessionInit");
		}

		global $session;
		$session = new \stdClass();
		$session->store = [];
		


		$fn = function() {
			$_SESSION['BS120'] = \exc\session::$store;
		};

		$app->on('requestEnd', $fn);
		
	}
	public static function hasKey($n){
		return isset(self::$store[$n]);
	}
	public static function key($n, $v=null){
		if(is_null($v)){
			if(isset(self::$store[$n])) return self::$store[$n];
			return null;
		}

		self::$store[$n] = $v;
	}
	public static function destroy(){
		\exc\session::$store = ["ms"=>[]];
		$_SESSION['BS120'] = \exc\session::$store;
		self::$store['BS'] = strtoupper(uniqid('BS'));

		$app = \exc\controller\appController::instance();
		$app->publish("sessionInit");
	}
	public static function removeKey($n){
		if(isset(self::$store[$n])) unset(self::$store[$n]);
	}
}


