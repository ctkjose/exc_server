<?php
/*
	Store session data in a database
	Requires exc.storage.db.php
		
	options:
		"connection" => "connectionName"

*/
namespace exc\module {

class sessiondb extends \exc\core\base {
	public static function initialize($options){
		error_log("@exc/module/sessiondb/initialize()");


	}
}


}