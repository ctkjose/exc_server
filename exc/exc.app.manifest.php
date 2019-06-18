<?php
namespace exc;

class manifest {
	static public $manifest = [];
	static public $compiled = ['js'=>'', 'css'=>''];
	static public $compile = [];
	static public function addInclude($p, $wait=true){
		$path = \exc\path::normalize($p);
		if(!is_array($path) || !$path['exists'] || !isset($path['url']) || (strlen($path['url'])==0) ) return;
		self::$manifest['include'][]= ['url'=>$path['url'], 'wait'=>$wait];
	}
	static public function addScript($p, $wait=true){
		$path = \exc\path::normalize($p);
		if(!is_array($path) || !$path['exists'] || !isset($path['url']) || (strlen($path['url'])==0) ) return;
		self::$manifest['include'][]= ['url'=>$path['url'], 'wait'=>$wait, 'type'=>'script'];
	}
	static public function addExport($p, $expName, $wait=true){
		$path = \exc\path::normalize($p);
		if(!is_array($path) || !$path['exists'] || !isset($path['url']) || (strlen($path['url'])==0) ) return;
		self::$manifest['include'][]= ['url'=>$path['url'], 'wait'=>$wait, 'type'=>'export', 'name'=>$expName];
	}
	static public function addView($name, $v){
		if(!isset(self::$manifest['views'])) self::$manifest['views'] = [];
		
		$o = ['name'=> $name];
		if(is_string($v)){
			$o['url'] = $v;
		}elseif(is_array($v)){
			$o = $v;
		}elseif(is_object($v)){
			$o['html'] = $v->getHTML();
		}
		
		self::$manifest['views'][] = $o;
		
	}
	static public function addController($name, $p=null){
		if(empty($p)){
			$path = ['url'=>''];
		}else{
			$path = \exc\path::normalize($p);
			
		}

		if(is_array($path) && isset($path['url']) && (strlen($path['url']) > 0) ){
			self::$manifest['includes'][]= ['url'=>$path['url'], 'wait'=>true];
		}
		
		self::$manifest['controllers'][]= [ 'name'=>$name ];
	}
	static public function pathCompile($p){
		$path = \exc\path::normalize($p);

		self::$compile[] = $path['path'];

		$data = file_get_contents( $path['path'] );
		if($path['ext'] == 'js' ){
			self::$compiled['js'].= "\n//FILE " . $path['url'] . "\n" . $data;
		}elseif($ext == 'css' ){
			self::$compiled['css'].= "\n/*FILE " . $path['url'] . " */\n" . $data;
		}

	}
	static public function getHTML(){
		$s = "<script type='text/javascript'>\n//MANIFEST\n" . self::$compiled['js'] . "\n</script>";
		return $s;
	}
	static public function &getManifest(){
		return self::$manifest;
	}
	
	static public function dump(){
		error_log('test 1 ---------------------------------------------------');
		error_log_dump(self::$manifest, 'manifest');
		error_log_dump(self::$compiled, 'compiled');
	}
}




?>