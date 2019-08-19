<?php
namespace exc {
	class manager1 extends \exc\base {
		public static $defaulView = null;
		public static $options = ['paths'=>[]];
		public static function initialize($options){

			if(!is_array($options)) return;

			$pv = [];
			$base = EXC_PATH_BASE;
			$p = $base . 'views/';

			if(file_exists($p)){
				$pv[] = $p;
			}

			if(isset(\exc\options::$values['app']['views.paths']) && is_array(\exc\options::$values['app']['views.paths'])){
				$pv = array_merge(\exc\options::$values['app']['views.paths'], $pv);
			}elseif(isset(\exc\options::$values['app']['views.paths']) && is_string(\exc\options::$values['app']['views.paths'])){
				$pv[] = \exc\options::$values['app']['views.paths'];
			}

			//setup locations where we can find our views
			if(isset($options['views.paths']) && is_string($options['views.paths']) && (strlen($options['views.paths']) > 0)){
				$pv[] = $options['views.paths'];
			}elseif(isset($options['views.paths']) && is_array($options['views.paths'])){
				$pv = array_merge($options['views.paths'], $pv);
			}

			\exc\options::$values['app']['paths']['views'] = [];

			//normalize paths
			chdir($base );
			foreach($pv as $i=>$p){
				$p = realpath($p);
				if($p=== false) continue;
				if(substr($p,-1,1) != '/') $p.='/';
				if(in_array($p, \exc\options::$values['app']['paths']['views'])) continue;
				\exc\options::$values['app']['paths']['views'][] = $p;
				self::$options['paths'][] = $p;
			}


			//view::constantCopyValues(\exc\options::$values['app']['urls'], 'URL');
			
			
			if(isset($options['views.default']) && is_string($options['views.default']) && (strlen($options['views.default']) > 0)){
				self::$options['default'] = $options['views.default'];
			}

			\exc\error_log_dump(self::$options, "view-options");

		}
		

		public static function addViewFolder($path){
			self::$options['paths'][] = $path;
			//\exc\options::$values['app']['paths']['views'][] = $path;
		}
		
	}

	class view {
		public $state = ['name'=>'','file'=>'', 'commited'=>false, 'src'=>'', 'contents'=>['body'],'currentName'=>'body','currentContent'=>null, 'elements'=>[]];
		public static $values =[];
		public $pathsIncluded = [];
		public static function load($name='default'){
			///N:get a view
			$view = null;
			$view = view::createWithFile( self::getViewPathForName($name), $name );
			return $view;
		}
		public static function getViewPathForName($n){
			$base = defined('EXC_PATH_APP') ? EXC_PATH_APP : EXC_PATH_BASE;
			$p = $base . 'views/';
			
			$paths = [
				$p = $base . 'views/'
			];

			$n = strtolower($n);
			foreach($paths as $p){
				$f = $p . 'view.'. $n . '.php';
				error_log("getViewPathForName $f");
				if(file_exists($f)) return $f;

				$f = $p . 'view.'. $n . '.html';

				if(file_exists($f)) return $f;
			}

			$f = __DIR__ . '/assets/view.blank.php';
			return $f;
		}
		public static function createWithFile($path, $name='default'){
			$view = self::createWithSource(file_get_contents($path), $name);
			$view->state['file'] = $path;
			return $view;
		}
		public static function createWithSource($source, $name='default'){
			$view = new view();
			$view->state['name'] = $name;
			$view->initializeSource($source);
			return $view;
		}
		public static function constantSetValue(){
			\exc\helper\dictionary\kvMapper::valueSet(self::$values, func_get_args());
		}
		public static function constantCopyValues($any, $prefix=''){
			\exc\helper\dictionary\kvMapper::valueSet(self::$values, [$any], $prefix);
		}
		public function __set ( $name, $value ){
			//error_log("view.__set($name, $value)");
			if(!isset($this->state['contents'][$name])) $this->state['contents'][$name] = '';
			$s = '';
			if( is_object($value) && method_exists($value, 'getHTML') ){
				$s = $value->getHTML();
			}elseif( is_string($value)){
				$s = $value;
			}elseif( is_array($value)){
				$s = $value;
			}

			$this->state['contents'][$name] = $s;
		}
		public function __get($name){
			$this->state['currentName'] = $name;
			if(!isset($this->state['contents'][$name])) $this->state['contents'][$name] = '';
			$this->state['currentContent'] = &$this->state['contents'][$name];
			return $this;
		}
		function src($value, $p=null){ ///TODO BROKEN
			///N:includes a css or js, etc
			if(is_null($this->_part_current)) return $this;

			if($this->state['currentName'] == 'js'){
				if(!isset($this->state['contents']['js_includes'])) $this->state['contents']['js_includes']=[];
				$this->state['contents']['js_includes'][]= $value;
			}elseif($this->state['currentName'] == 'css'){
				if(!isset($this->state['contents']['css_includes'])) $this->state['contents']['css_includes']=[];
				$m =(!empty($p)) ? $p : 'all';
				$this->$this->state['contents']['css_includes'][]= ['url'=>$value, 'media'=> $m];
			}else{
				$this->state['currentContent'].= $s;
			}
			return $this;
		}
		function copy($p, $attr=[]){
			///N:includes a css or js or other

			//error_log("view->" . $this->state['currentName'] . "->copy(" . $p . ")");
			$path = '';
			if(is_string($p)){
				$f = \exc\path::normalize($p);
				if(!$f['exists']) return $this;
				$path = $f['path'];
			}elseif(is_array($p) && isset($p['path'])){
				$path = $p['path'];
			}elseif(is_array($p) && isset($p['url'])){
				$path = $p['url'];
			}
			
			if(in_array($path, $this->pathsIncluded)) return $this;
			$this->pathsIncluded[] = $path;

			
			$s = '';
			try{
				$s = file_get_contents($path);
			}catch (Exception $err) {
				error_log('[EXC][VIEW][ERROR] Unable to included path [' . $path . ']');
				error_log('[EXC][VIEW][ERROR] ' . $err->getMessage() );
				return $this;
			}

			if(strlen($s) == 0) return $this;

		
			if($this->state['currentName'] == 'js'){
				$s = '// EXC INCLUDE: ' . basename($path) . "\n" . $s;
				if(!isset($this->state['contents']['js_includes'])) $this->state['contents']['js_includes']='';
				$this->state['contents']['js_includes'] .= $s;
			}elseif($this->state['currentName'] == 'css'){
				$s = '/* EXC INCLUDE: ' . basename($path) . " */ \n" . $s;
				if(!isset($this->state['contents']['css_includes'])) $this->state['contents']['css_includes']='';
				$this->state['contents']['css_includes'] .= $s;
			}else{
				$this->state['currentContent'].= $s;
			}
			return $this;
		}
		public function set($any){


			if(is_null($this->state['currentContent'])) return $this;

			if( is_object($any) && method_exists($any, 'getHTML') ){
				//$s = $any->getHTML();
				$s = $any;
			}elseif( is_string($any)){
				$s = $any;
			}elseif( is_array($any)){
				//error_log("set view with array");
				$s = $any;
			}

			$this->state['currentContent'] = $s;
		}
		public function write($any){

			if(is_null($this->state['currentContent'])) return $this;

			$s = '';
			if( is_object($any) && method_exists($any, 'getHTML') ){
				$s = $any->getHTML();
			}elseif( is_string($any)){
				$s = $any;
			}

			$this->state['currentContent'].= $s;
			//if(!isset($this->contents['body'])) $this->contents['body'] = '';
			//$this->contents['body'].= $s;
		}
		public function process_if($ridx, $e, $v){
			$e['src']->state['contents'] = $this->state['contents'];

			if(!isset($this->state['contents'][$e['n']]) || !($v) ){
				return '';
			}

			return $e['src']->getHTML();
		}
		public function process_unless($ridx, $e, $v){
			$e['src']->state['contents'] = $this->state['contents'];

			if(!isset($this->state['contents'][$e['n']]) || ($v) ){
				return '';
			}

			return $e['src']->getHTML();
		}
		public function getHTML(){
			$s = $this->state['src'];



			if(isset($this->state['contents']['js_includes'])){
				$this->state['contents']['js_includes'] = '<script type="text/javascript">' . $this->state['contents']['js_includes'] . "\n</script>";
			}
			if(isset($this->state['contents']['css_includes'])){
				$this->state['contents']['css_includes'] = '<style>' . $this->state['contents']['css_includes'] . "\n</style>";
			}
			//error_log(var_export(self::$values, true));

			//print "<pre> src=" . htmlentities($s, true) . "</pre><br>";
			//print "<pre> elements_def=" . var_export($this->state['elements'], true) . "</pre><br>";
			//print "<pre> contents=" . var_export($this->state['contents'], true) . "</pre><br>";
			foreach($this->state['elements'] as $ridx => $e){
				$v ='';
				if(substr($e['n'], 0,1) == '@'){
					$const = &self::$values;
					$e['n'] = substr($e['n'], 1);
					if(isset($const[$e['n']])){
						$v= $const[$e['n']];
						$e['n']=$v;
					}
				}

				if( isset($e['idx']) ){
					//print "{$e['t']}::{$e['n']}::array::{$e['idx']}<br>";
					if(isset($this->state['contents'][$e['n']]) && is_array($this->state['contents'][$e['n']]['value']) && isset($this->state['contents'][$e['n']]['value'][$e['idx']]) ){
						$v = $this->state['contents'][$e['n']]['value'][$e['idx']];
						$e['n'] = $e['idx'];
					}else{
						$v = '';
					}
				}else if( isset($this->state['contents'][$e['n']]) ){
					//print "{$e['t']}::{$e['n']}::regular value<br>";
					$v = $this->state['contents'][$e['n']];
				}

				if(is_object($v)){
					if(method_exists($v, 'getHTML') ){
						$v = $v->getHTML();
					}else{
						$v = '';
					}
				}
				//print "<pre> element[{$ridx}]::{$e['t']}::{$e['n']}::{$e['idx']}<blockquote>\n";
				if($e['t'] == 'repeat'){
					//print "{$e['t']}::{$e['n']}::repeat<br>";
					$v = $this->process_repeat($ridx, $e, $v);
				}else if($e['t'] == 'if'){
					$v = $this->process_if($ridx, $e, $v);
				}else if($e['t'] == 'unless'){
					$v = $this->process_unless($ridx, $e, $v);
				}else if($e['t'] == 'ignore'){
					$v = $e['value'];
				}

				if(is_object($v) && !method_exists($v, '__toString') ) $v = '';

				if($e['t'] == 'url'){
					$v = urlencode($v);
				}else if($e['t'] == 'html'){
					$v = htmlentities($v);
				}
				//print "v[$ridx]=" . var_export($v, true) . "<br>";
				//print "</blockquote></pre><br>";
				$s = str_replace('<<' . $ridx . '>>', $v, $s);
			}

			return $s;
		}
		public function initializeSource($src){
			$this->state['src'] = $src;
			$this->state['elements'] = [];
			$rv = "([a-z|A-Z|0-9|_|\[|\]|\.|\-]*)";
			$parts = array(
				['t'=> 'ignore', 'r'=> "(\#ignore)"],
				['t'=> 'repeat', 'r'=> "\#repeat\\s+{$rv}"],
				['t'=> 'if', 'r'=> "\#if\\s{$rv}"],
				['t'=> 'unless', 'r'=> "\#unless\\s{$rv}"],
				['t'=> 'url', 'r'=> "url\({$rv}\)"],
				['t'=> 'html', 'r'=> "html\({$rv}\)"],
				['t'=> 'value', 'r'=> "([a-z|A-Z|0-9|_|\[|\]|\.|\@]*)"],
			);


			$r = "/\{\{\#(view|file|script|style)\s([a-z|A-Z|0-9|_\@\/\.\-\:]*)\}\}/";
			unset($m);
			preg_match($r, $this->state['src'], $m, PREG_OFFSET_CAPTURE);
			while($m != null){
				$x1 = $m[0][1];
				$x2 = $x1 + strlen($m[0][0]);
				$n = $m[2][0];
				$t = $m[1][0];

				if($m[1][0] == "view") $n = self::getViewPathForName($n);
				if(preg_match('/[a-z]+\:\/\//', $n)){
					$p = \exc\path::normalize($n);
					$n = $p['path'];
				}

				$s = file_get_contents($n);
				if($m[1][0] == "script"){
					$s = '<script x="1">' . $s . '</script>';
				}elseif($m[1][0] == "style"){
					$s = '<style x="1" type="text/css">' . $s . '</style>';
				}
				$this->state['src'] = substr($this->state['src'], 0, $x1) . $s . substr($this->state['src'], $x2);
				preg_match($r, $this->state['src'], $m, PREG_OFFSET_CAPTURE);
			}

			//print "k<pre>" . htmlentities($this->src) . "</pre>";
			$ridx = 0;
			foreach($parts as $p){
				$r = "/\{\{" . $p['r'] . "\}\}/";
				unset($m);
				preg_match($r, $this->state['src'], $m, PREG_OFFSET_CAPTURE);


				while($m != null){
					$ridx++;
					//print "<pre>m1[{$p['t']}]=" . var_export($m, true) . "</pre>";
					$x1 = $m[0][1];
					$x2 = $x1 + strlen($m[0][0]);

					$e = ['t'=>$p['t'] , 'n'=> $m[1][0] ];

					if (preg_match("/([a-z|A-Z|0-9|_]*)\[([a-z|A-Z|0-9|_|\.]*)\]/", $e['n'], $n1)){
						$e['n'] = $n1[1];
						$e['idx'] = $n1[2];
					}

					if($e['t'] == 'repeat'){
						$n = str_replace(']', '\]', str_replace('[', '\[', $m[1][0]));
						$r1 = "/\{\{end {$n}\}\}/";
						//print "repeat looking for $r at $x1<br>";
						if (preg_match($r1, $this->state['src'], $n1,PREG_OFFSET_CAPTURE, $x2 )){
							$x3 = $n1[0][1];
							$e['src'] = self::createWithSource( $e['n'], substr($this->state['src'], $x2, $x3-$x2-1) );

							$x2 = $x3 + strlen($n1[0][0]);
						}
					}
					if($e['t'] == 'if'){
						$n = str_replace(']', '\]', str_replace('[', '\[', $m[1][0]));
						$r1 = "/\{\{end if {$n}\}\}/";
						if (preg_match($r1, $this->state['src'], $n1,PREG_OFFSET_CAPTURE, $x2 )){
							$x3 = $n1[0][1];
							$e['src'] = self::createWithSource( $e['n'], substr($this->state['src'], $x2, $x3-$x2-1) );
							$x2 = $x3 + strlen($n1[0][0]);
						}
					}
					if($e['t'] == 'unless'){
						$n = str_replace(']', '\]', str_replace('[', '\[', $m[1][0]));
						$r1 = "/\{\{end unless {$n}\}\}/";
						if (preg_match($r1, $this->state['src'], $n1,PREG_OFFSET_CAPTURE, $x2 )){
							$x3 = $n1[0][1];
							$e['src'] = self::createWithSource( $e['n'], substr($this->state['src'], $x2, $x3-$x2-1) );
							$x2 = $x3 + strlen($n1[0][0]);
						}
					}
					if($e['t'] == 'ignore'){
						$r = "/\{\{end ignore\}\}/";
						if (preg_match($r, $this->state['src'], $n1,PREG_OFFSET_CAPTURE, $x2 )){
							$x3 = $n1[0][1];
							$e['value'] =  substr($this->state['src'], $x2, $x3-$x2-1);

							$x2 = $x3 + strlen($n1[0][0]);
						}
					}

					$this->state['src'] = substr($this->state['src'], 0, $x1) . '<<' . $ridx . '>>' . substr($this->state['src'], $x2);
					$this->state['elements'][$ridx] = $e;
					preg_match($r, $this->state['src'], $m, PREG_OFFSET_CAPTURE);
				}
			}
		}
	}
	class dialog extends view {
		public $name = '';
		public $options = [];
		public static function createWithURL($url,$name= null){
			$dlg = new dialog;
			$dlg->name = strtoupper(uniqid('DLG'));
			if(!is_null($name)) $dlg->name = $name;

			$dlg->contents['url'] = $url;

			return $dlg;
		}
		public function getActionJS(){
			$js = 'exc.views.showDialogWithName("' . $this->name . '");';
			$js.= 'if(msg && msg.hasOwnProperty("widget")) msg.widget.setProperty("disabled", false);'; //does not belong here...
			return $js;
		}
		public function getHTML(){
			$s = '<script type="text/javascript">';
			$s.= 'exc.views.named["' . $this->name . '"]=' . json_encode($this->contents) . ";\n";
			$s.= '</script>';

			return $s;
		}
	}
}

?>