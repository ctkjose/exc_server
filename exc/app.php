<?php
require_once( $_SERVER['DOCUMENT_ROOT'] . '/exc/engine2/server/reasg.helper.dev.php');

require_once("./exc.php");
error_log("[EXC] BOOTSTRAPPING APP");
define("EXCSERVERMODE", "1");

\exc\bootloader::run();
$app = \exc\controller\appController::instance();

//print "<pre>" . var_export(\exc\router::instance(),true) . "</pre>";



?>