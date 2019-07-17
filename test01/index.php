<?php
//this the startup script
require_once('../exc/exc.php');

\exc\bootloader::run([
	'src'=> __DIR__ . '/src/'
]);
?>