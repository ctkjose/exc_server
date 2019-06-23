<?php

function excErrorHandler($errno, $errstr, $errfile, $errline){
	$p = explode("\n", $errstr);
	$flgWithJSON = false;
	$flgAbort = false;
	if( isset($_REQUEST) && isset($_REQUEST["api_json_state"])) $flgWithJSON = true;
	$errMsg = "";
	switch ($errno) {
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_STRICT:
		case E_USER_ERROR:
			$errMsg = "[FATAL ERROR]";
			$flgAbort = true;
			break;
		case E_USER_WARNING:
			$errMsg = "[WARNING]";
			break;
		case E_USER_NOTICE:
			$errMsg = "[WARNING]";
			break;
		default:
			$errMsg = "[OTHER]";
			break;
	}
	
	error_log("[PHP][ERROR][$errno] $errMsg LINE:$errline FILE:$errfile") ;
	$p = explode("\n", $errstr);
	foreach($p as $s){
		error_log("[PHP][ERROR][$errno] " . $s) ;
	}

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
	array_shift($backtrace);
	foreach($backtrace as $i=>$l){
		$cls = (isset($l['class'])) ? $l['class'] : '';
		$cls.= (isset($l['type'])) ? $l['type'] : '';
		$s = "[PHP][TRACE][$i] in function {$cls}{$l['function']}";
		if(isset($l['line'])) $s.= " [LINE: {$l['line']}]";		
		if(isset($l['file'])) $s.= " [FILE: {$l['file']}]";
		error_log($s);
	}

	if($flgAbort){
		if($flgWithJSON){
			if(!headers_sent()) header('content-type: text/json');
			print '{"_bst":"1.0","status":500,"error":"Unhandled backend error"}';
		}else{
			$s = file_get_contents(dirname(__FILE__) . "/assets/onerror.html");
			$s = str_replace('{{msg}}', "", $s);
			print $s;
		}

		exit(1);
	}
    /* Don't execute PHP internal error handler */
    return true;
}

function excShutDownFunction() { 
    $error = error_get_last();
	if(!is_null($error)) excErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
}

register_shutdown_function('excShutDownFunction');
set_error_handler("excErrorHandler");

require_once( $_SERVER['DOCUMENT_ROOT'] . '/exc/engine2/server/reasg.helper.dev.php');


require_once("./exc.php");
error_log("[EXC] BOOTSTRAPPING APP");
define("EXCSERVERMODE", "1");



\exc\bootloader::run();
?>