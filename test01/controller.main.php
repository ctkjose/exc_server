<?php

class mainController extends \exc\controller\viewController {
	public function initialize(){
		error_log("--- @mainController->initialize() ---");
		
	}
	
	public function unhandled($msg){
		//called when a message is not implemented
		error_log("@mainController->unhandled ---- $msg");
	}
	public function onAction_default(){
		error_log("--- @mainController->default() --- ");

		//$app = $this->appController();
		$client = \exc\client::instance();
		//$client->session("kid", "jose");

		$client->addJSFile('asset://js/tests.js');

		$client->setData('record', ["name"=> "jose", "lname"=>"cuevas"] );
		
		//$view = \exc\views\manager::createDefaultView("default");
		//$view = \exc\views\view::default("default");
		$view = \exc\views\view::default();
		$view->title->set("Welcome");

	
	}
	public function onAction_login(){
		$client = \exc\client::instance();
		
		$client->session("user", "");

		$viewLogin = \exc\views\manager::getView("login");
		$client->addView('loginView', $viewLogin);

		$client->addController('loginController', 'asset://js/controller.login.js');
		$client->done();
	}
	public function onAction_ComputeRate(){
		$client = \exc\client::instance();
		$qty = $client->values['qty'];
		
		$results = ["status"=>200, "cost"=>25 * $qty, "qty"=>$qty];
		$client->sendResponseData($results);

		$client->done(); //finish the interaction
	}
	public function onAction_testDB(){
		
		$db = \exc\storage\db::connection("test1");
		error_log_dump($db, "testDB");

		$ok = $db->insert("excid_users", ["email"=>"jcuevas1"]);
		if(!$ok){
			error_log("DB Error: " . $db->errorMsg );
		}
		$ds = $db->get("excid_users");
		error_log_dump($ds, '$ds');
		
		while($ds->read()){
			error_log_dump($ds->fields, '$ds->fields');
		}

	}
	public function onAppSendState(){
		error_log("@event_appSendState appSendOutput =========================");
	}
	public function onAction_doThis(){
		error_log("--- @mainController->dothis() --- ");

		$app = $this->appController();
		


		$client = \exc\client::instance();
		//$client->session("kid", "jose");

		$client->data['record'] = ["name"=> "jose", "lname"=>"cuevas"];
		
		//$view = \exc\views\manager::createDefaultView("default");
		//$view = \exc\views\view::default("default");
		$view = \exc\views\view::default();

		$viewLogin = \exc\views\manager::getView("login");
		\exc\manifest::addView("loginView", $viewLogin);
	}
	public function onAction_testAction(){
		error_log("--- @mainController->event_action_testAction() --- ======================================================");
		
		$client = \exc\client::instance();	
		error_log("fn=" . $client->session("fn"));

		//\exc\error_log_dump($client, '$client');
		$client->data['record'] = ["name"=> "jose1", "lname"=>"cuevas garcia"];
		$client->data['a'] = 'joe';
		
		$client->publish("testMessage", ['carrier'=> 'USPS', 'cost'=>50.25]);
		$client->sendResponseData(['name'=>'Jose', 'v'=>35]);

		$client->done();
	}
	public function event_action_getShippingCost(){
		$client = \exc\client::instance();
		
		//error_log(print_r($values, true) );
		error_log("values----------------------------");
		error_log(print_r($client->values, true) );
		$zipcode = $client->values['zipcode'];
		
		//$client->callback("USPS", 50.25);
		$ok = true;
		if($ok){
			$client->publish("updateShippingCost", ['carrier'=> 'USPS', 'cost'=>50.25]);
		}else{
			$client->publish("errorWithShippingCost", ['error'=> 'We do not ship to the continental US']);
		}
		
		$client->done();
    }
	public function event_action_test02(){
		error_log("--- @mainController->event_action_test01() --- ======================================================");
		
		$client = \exc\client::instance();	
		error_log("fn=" . $client->session("fn"));
		
		$client->showMessage("hello " . $client->session("fn") );
		$client->sessionRemove("ln");
		
		
		
		$client->data['name'] = "Jose L Cuevas";
		$client->data['cost'] = "123.50";
		
		$client->callback("Jose Cuevas", 50.25);
		
		$client->done();
		
	}
	public function event_action_showrecord(){
		error_log("--- @mainController->showrecord() --- ======================================================");
		
		
		$app = $this->appController();
		
		//$view = \exc\views\manager::createDefaultView("default");
		//$view = \exc\views\view::default("default");
		$view = \exc\views\view::default();
		
		$view->app_title->set("My App");
		
		$view_record = \exc\views\manager::getView("record");
		\exc\manifest::addView("record", $view_record);
		
		$view_search = \exc\views\manager::getView("search");
		\exc\manifest::addView("search", $view_search);
		
		\exc\manifest::addInclude('@assets/js/record.controller.js');
		\exc\manifest::addController('record_controller', null);
		
		$client = \exc\client::instance();
		$client->session("fn", "jose");
		$client->session("ln", "Cuevas");
		//$client->showMessage("hello jose");
		
		//$client->setModuleController("record_controller", "@assets/js/record.controller.js");
	}
	public function onAppInit(){
		//This is the callback for a message named "AppInit"
		//This message is called whenever an interaction is started.
		//onAppInit we setup or initialize things this application needs to run

		error_log("--- @mainController->message_appInit() ---");
	}
	public function onAppStart($app){
		//This is the callback for a message named "AppStart"
		//Here we build and pack our main landing page and send it to the user.
		//This event is triggered if this is the first time a user loads this app or if the session has expired.

		error_log("--- @mainController->onAppStart() ---");
		\exc\error_log_dump($app, 'app');

		$this->onAction_default();
	}
}
?>