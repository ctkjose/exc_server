<?php
namespace exc\io\files {

	class manager extends \exc\core\base {
		static $paths = [];
		public static function initialize($options = []){

			$paths = [
				'home' => (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '') . '/',
				'app' => (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__FILE__)) . '/',
				'exc' => (defined('EXC_PATH') ? EXC_PATH : dirname(__FILE__) . '/'),
				'includes' => []
			];

			if(isset($options['paths']) && is_array($options['paths']) ){

			}

		}
		public static function upload($name=null){

			if(is_string($name)){
				return upload::get($name);
			}

			$o = new upload();
			$o->init();
			return $o;
		}
		public static function normalizePath($path, $relativeTo = null){
			$r = '/';

			if( isset($_SERVER) && isset($_SERVER['DOCUMENT_ROOT']) && (strlen($_SERVER['DOCUMENT_ROOT']) > 0)){
				$r = $_SERVER['DOCUMENT_ROOT'];
			}elseif( isset($_SERVER) && isset($_SERVER['PWD']) && (strlen($_SERVER['PWD']) > 0)){
				$r = $_SERVER['PWD'];
			}

			if(is_string($relativeTo)) $r = $relativeTo;

			if(substr($r,-1,1) != '/') $r.='/';

			$rp = explode('/', $r);
			if(substr($r,0,1) == '/') array_shift($rp);
			array_pop($rp);

			$parts = [];

			$path = str_replace('\\', '/', $path);
			$path = preg_replace('/\/+/', '/', $path);
			$path = str_replace($r,'', $path);


			$p1 = explode('/', $path);

			$test = '';// Initialize testing variable
			foreach($p1 as $p){
				if($p == '') continue;
				if($p == '.') continue;
				if($p == '..'){
					if(count($parts) > 0){
						array_pop($parts);
					}elseif(count($rp) > 0){
						array_pop($rp);
					}
				}else{
					$parts[] = $p;
				}
			}

			$out = [
				'parent' => implode('/', $rp ),
				'relative' => implode('/', $parts ),
				'path' => '/' . implode('/', array_merge($rp,$parts) )
			];

			return $out;

		}
	}
	class upload {
		public $files = [];
		public function init(){
			if( !isset($_FILES) ){
				$this->files = [];
				return;
			}

			$this->files = @$_FILES;

		}
		public static function get($name){
			if( !isset($_FILES) ) return null;
			if(!is_array($_FILES) || !isset($_FILES[$name]) ){
				return null;
			}

			return tempFile::createFromUpload($_FILES[$name]);
		}
		public function __get($name){

			return upload::get($name);
		}

	}
	class tempFile extends fileItem {
		public $path = '';
		public $name = '';
		public $size = '';
		public $mime = '';
		public $status = 1;
		public $lastErrorMessage = '';
		public static function create($prefix=null, $tempPath = null){

			$p = (!is_null($prefix)) ? $prefix : '';
			$pt = (!is_null($tempPath)) ? $tempPath : sys_get_temp_dir();
			$f = tempnam($p, $p);
			if($f === false) return null;

			$out = new tempFile();
			$out->path = $f;
			$out->name = basename($f);
			return $out;
		}
		public static function createFromUpload($entry){
			$out = new tempFile();

			if((1 == $entry['error']) or (2 == $entry['error'])){
				$out->setError(501,'File too big. Max Allowed: ' . ini_get('upload_max_filesize') );
				return $out;
			} elseif( (3 == $entry['error']) or (4 == $entry['error']) ){
				$out->setError(502,'Unable to read file');
				return $out;
			}

			$out->path = $entry['tmp_name'];
			$out->name = $entry['name'];
			$out->mime = $entry['type'];
			$out->size = $entry['size'];

			return $out;
		}

	}
	class fileItem extends \exc\core\base {
		public $path = '';
		public $name = '';
		public $size = '';
		public $mime = '';
		public $status = 1;
		public $lastErrorMessage = '';
		private $fp = null;

		public static function createFromPath($path){
			$out = new fileItem();

			$out->path = $path;


			$out->name = basename($path);
			//$out->mime = $entry['type'];
			//$out->size = $entry['size'];

			return $out;
		}
		public function child($name){
			$this->path.= ( (substr($this->path,-1,1) != '/') ? '/' : '') . $name;

			error_log("fileItem.path=[" . $this->path . "]");
			return $this;
		}
		public function __get($child){
			$this->path.= ( (substr($this->path,-1,1) != '/') ? '/' : '') . $child . '/';

			error_log("fileItem.path=[" . $this->path . "]");
			return $this;
		}
		public function open($mode='rw'){
			$this->fp= fopen('data.txt', 'rw');
			return (is_resource($this->fp) === true);
		}
		public function write($data){
			if(is_null($this->fp)){
				if(!$this->open()) return false;
			}

			if(is_resource($this->fp) !== true) return false;

			$ok = fwrite($this->fp, $data);
			if($ok === false) return false;
			return true;
		}
		public function close(){
			if(!is_null($this->fp)) fclose($this->fp);
			return $this;
		}
		public function exists(){
			return file_exists($this->path);
		}
		public function delete(){
			if(!file_exists($this->path)) return false;
			return unlink($this->path);
		}
		public function move($path){
			if(!file_exists($this->path)) return false;
			$ok = copy($this->path, $path);
			if($ok){
				unlink($this->path);
				$this->path = $path;
				$this->name = basename($path);
			}
			return $ok;
		}
		public function size(){
			return filesize($this->path);
		}
		public function extension($path = null){
			$pt = (is_null($path) ? $this->path : $path);

			$p = explode('.', $pt);
			if(!is_array($p) || (count($p) == 0) ) return '';
			return strtolower(array_pop($p));
		}

		public function isWritable(){
			return is_writable($this->path);
		}
		public function isUpload(){
			return is_uploaded_file($this->path);
		}
		public function isReadable(){
			return is_readable($this->path);
		}
		public function setError($status=500, $msg='Error'){
			$this->status = $status;
			$this->lastErrorMessage = $msg;
		}
	}
}

?>