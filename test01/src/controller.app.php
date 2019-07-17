<?php

class appController extends \exc\controller\appController {
	public function config(){
		$options = [];

		$options['route'] = [ //options for individual routes...
			'*' => [ //use wildcard for all routes
				'use'=> [
					"exc://storage.db"=>[
						"connections"=>[
							"test1" => ["driver"=>"mysql", "host"=>"192.168.100.175", "port"=>3306, "dbname"=>"testdb", "username"=>"ctk","password"=>"jose"]
						]
					],
				],
				'view.copy'=> [ //add to the view
					['type'=>'js', "url"=>'app://assets/js/tests.js'], //global code
				]
			],
			'app.view01'=>[
				'use'=> [
					"exc://storage1.db"=>[
						"connections"=>[
							"test1" => ["driver"=>"mysql", "host"=>"192.168.100.175", "port"=>3306, "dbname"=>"testdb", "username"=>"ctk","password"=>"jose"]
						]
					],
				],
			]
		];
		//modules I need
		$options['using'] = [
			'*'=> [ //use always...
				//"composer" => [], //enable composer autoload
				
				"exc://storage.db"=>[
					"connections"=>[
						"test1" => ["driver"=>"mysql", "host"=>"192.168.100.175", "port"=>3306, "dbname"=>"testdb", "username"=>"ctk","password"=>"jose"]
					]
				],
			],
			'main.dothis' => [ //only when for a 'testaction' request
				"exc://exc.ui"=>[
					"views.default" => "default",
					"views.paths"=>[
						'./views/',
					]
				],
			]
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

		\exc\storage\db::initialize([]);
	}
	public function onAction_view01(){
		error_log("@appController->onAction_view01 ---------");
		//$view = \exc\view::load();
		//\exc\error_log_dump($view);
		//$this->makeViewDefault($view);

		$this->makeViewDefault('default');
	}
}
?>