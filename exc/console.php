<?php
namespace exc;

class console {
	public static function getArgument($k, $default=''){
		global $argv;

		$l =strlen($k);
		foreach($rea_app_route['values'] as $o){
			if(substr($o,0,1 + $l) == '-' . $k){
				$v = substr($o,1 + $l);
				//print "[{$k}]=[{$o}]=[{$v}]\n";
				if(substr($v,0,1) == '='){
					$v = substr($v,1);
					return $v;
				}
				return true;
			}elseif(substr($o,0,2 + $l) == '--' . $k){
				$v = substr($o,2 + $l);
				//print "[{$k}]=[{$o}]=[{$v}]\n";
				if(substr($v,0,1) == '='){
					$v = substr($v,1);
					return $v;
				}
				return true;
			}
		}
		
		return false;
	}
	public static function writeln($s){
		print $s . "\n";
	}
}