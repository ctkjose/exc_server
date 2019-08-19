<?php
namespace exc\helper;
class script {
	public $js;
	public $values = [];
	public static function load($path){
		if(substr($path, 0,1) != '/'){
			$path = __DIR__ . '/assets/' . $path;
		}
		$o = new script();
		$o->js = file_get_contents($path);
		return $o;
	}
	public function __toString(){
		return $this->source();
	}
	public function __set($name, $value) {
		$this->values[$name] = $value;
	}
	public function __get($name) {
		if(!isset($this->values[$name])) return null;
		return $this->values[$name];
	}
	public function source($opWithTag=false){
		$js = $this->js;
		$vars = [];
		preg_match_all('/\{\{\$([A-Za-z0-9\_]+)\}\}/', $this->js, $m);
		if(isset($m[1])){
			foreach($m[1] as $vn){
				if(array_key_exists($vn, $vars)) continue;
				$vars[$vn] = chr(rand(65, 90)) . count($vars) . rand(1, 90);

				$js = str_replace('{{$' . $vn . '}}', $vars[$vn], $js);
			}
		}
		foreach($this->values as $k => $v){
			$js = str_replace('{{' . $k . '}}', $v, $js);
		}
		return $js;
	}
}
?>