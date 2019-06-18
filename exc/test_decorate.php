<?php


function decorate($o){
	$fns =[
		"doThis" => function() use($o){
			error_log("@doThis()");
		},
	];


	foreach($fns as $fk => $fn){
		$o->$fk = $fn;
	}

	$o->"doThis"();
}

$o = new stdClass();
decorate($o);
?>