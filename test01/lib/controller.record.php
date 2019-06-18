<?php

class recordController extends \exc\controller\viewController {
	public function initialize(){
		error_log("--- @recordController->initialize() ---");
		
	}
	public function onAction_main(){
		error_log("--- @recordController->main() --- ");
	}
}