<?php

class appController extends \exc\controller\appController {
	public function config(){
		$options = [];

		$options['route'] = [ //options for individual routes...
			'*' => [ //use wildcard for all routes
				'use'=> [
					"exc://storage.db"=>[
						"connections"=>[
							"auth" => ["driver"=>"mysql", "host"=>"127.0.0.1", "port"=>3306, "dbname"=>"exc_auth", "username"=>"root","password"=>"R00t!23"],
							"test1" => ["driver"=>"mysql", "host"=>"192.168.100.175", "port"=>3306, "dbname"=>"testdb", "username"=>"ctk","password"=>"jose"]
						],
					],
				],
				'view.copy'=> [ //add to the view
					['type'=>'js', "url"=>'app://assets/js/tests.js'], //global code
				],
				'extension.auth'=>[
					'require.login' => true,
					'db.connection' => 'auth',
				]
			],
			'app.view01'=>[
				'extension.auth'=>[
					'require.login' => false,
				],
			]
		];

		
		$options['extension.auth'] = [
			'test1'=> "jose2",
		];

		//files to include in your default view
		$options['view.copy'] = [
			'*' => [ //copy in all views
				['type'=>'js', "url"=>'app://assets/js/tests.js'], //global code
			],
			'app.view01' => [
				['type'=>'js', "url"=>'app://assets/js/lib1.js', 'name'=>'mylib'], //an export of a CommonJS Module
			]
		];
		return $options;
	}
	public function initialize(){
		error_log("--- @appController->initialize() ---");
		
	}
	public function unhandled($msg){
		//called when a message is not implemented
		error_log("@appController->unhandled ---- $msg");
	}
	public function onAction_default(){
		error_log("@appController->onAction_default ----");

		$this->makeViewDefault('default');
	}
	public function onAction_view01(){
		error_log("@appController->onAction_view01 ---------");
		//$view = \exc\view::load();
		//\exc\error_log_dump($view);
		//$this->makeViewDefault($view);
		
		$this->makeViewDefault('default');
		\exc\error_log_dump(\exc\app::$scopeAction);
		
	}
	public function testAuth(){

		/*
		$u = new \ext\auth\user();
		$u->user_email = 'jcuevas@mac.com';
		$u->user_login = 'jcuevas';
		$u->user_fname = 'Jose';
		$u->user_mname = 'L';
		$u->user_lname = 'Cuevas';
		$u->passwordSet('jose');

		$u->attr('tel','787-464-3264');
		$u->attr('tel-country','+1');

		$ok = $u->save();
		
		if($ok){
			error_log('account created');
		}else{
			error_log('account not created');
		}
		*/

		/*
		$u = new \ext\auth\user('U1565206900-5D4B2974EB9FD');
		\exc\error_log_dump($u);

		$u->attr('k1','jose3');

		$ok = $u->save();
		
		if($ok){
			error_log('account created');
		}else{
			error_log('account not created');
		}
		*/

		/*$role = \ext\auth\role::getRoleByUID('admin');
		\exc\error_log_dump($role);*/

		$u = new \ext\auth\user('U1565206900-5D4B2974EB9FD');
		\exc\error_log_dump($u);
		//$roles = \ext\auth\role::getRolesForUser($u);
		//\exc\error_log_dump($roles);

		$u->loadRoles();

		error_log("Has Role admin=" .  ($u->hasRole("admin") ? 'True' : 'False') );
		error_log("Has Role admin1=" .  ($u->hasRole("admin1") ? 'True' : 'False') );

		error_log("Has Role users_admin_create1=" .  ($u->hasPermission("users_admin_create1") ? 'True' : 'False') );
		error_log("Has Role users_admin_create=" .  ($u->hasPermission("users_admin_create") ? 'True' : 'False') );
	}
}
?>