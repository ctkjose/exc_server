<?php
//our app identifier...
$options['uid'] = "TEST01";

//controllers that are installed
$options['controllers']=[
	"mainController" => "app://controller.main.php",
	//"employeeController" => "./controller.employee.php",
];

$options['pages'] = [

	"landing" => [
		'controller' => ["url"=>'asset://controller.app.js'],
		'dafault_view' => 'view.default.php',
	],
];

//files to include in the application manifest
$options['manifest'] = [
	["url"=>'app://assets/js/tests.js'], //global code
	["url"=>'app://assets/js/lib1.js', 'type'=>'export', 'name'=>'mylib'], //an export of a CommonJS Module
];

//configure session
$options['with_session'] = 1;
$options['session'] = [
	"gc_maxlifetime" => 1800,
];

$options['using'] = [   //which modules to load
	"exc://exc.storage.db"=>[
		"connections"=>[
			"test1" => ["driver"=>"mysql", "host"=>"192.168.100.175", "port"=>3306, "dbname"=>"testdb", "username"=>"ctk","password"=>"jose"]
		]
	],
	"exc://modules/db/exc.module.sessiondb"=>[
		"connections"=>[
			"test1" => ["driver"=>"mysql", "host"=>"192.168.100.175", "port"=>3306, "dbname"=>"testdb", "username"=>"ctk","password"=>"jose"]
		]
	],
	"exc://exc.ui"=>[
		"views.default" => "default",
		"views.paths"=>[
			'./views/',
		]
	],
];

?>