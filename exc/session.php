<?php
namespace exc;

class session extends \exc\base {
	public static $store = ["ms"=>[]];
	public static $enabled = false;
	public static function initialize(){
		error_log("@exc/session/manager/initialize()");

		self::$enabled = ( EXC_RUNMODE == 1 );
		$ops = [
			"name"=> "BS120",
			"use_only_cookies"=> 1,
			"cookie_httponly"=>1,
			"use_trans_sid"=>0,
			"gc_maxlifetime"=>1400,
			"cookie_lifetime"=> 0,
		];

		if(isset(\exc\options::$values['app']['session'])){
			$ops = \exc\base::extend($ops,\exc\options::$values['app']['session']);
		}

		$app = \exc\app::controller();

		
		if(self::$enabled){
			session_start($ops);
			$fn = function() {
				$_SESSION['BS120'] = \exc\session::$store;
			};

			if($app) $app->on('appEnd', $fn);

			if(isset($_SESSION['BS120'])){
				self::$store = $_SESSION['BS120'];
			}
			
		}

		if(!isset(self::$store['BS'])){
			self::$store['BS'] = strtoupper(uniqid('BS'));
			if($app) $app->publish("sessionInit");
		}
		if(!isset(self::$store['ms'])) self::$store['ms'] = [];
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
		self::$store['BS'] = strtoupper(uniqid('BS'));

		$app = \exc\app::controller();
		if($app) $app->publish("sessionInit");

		if(self::$enabled) $_SESSION['BS120'] = \exc\session::$store;
	}
	public static function removeKey($n){
		if(isset(self::$store[$n])) unset(self::$store[$n]);
	}
}


