<?php
namespace exc\views {
	class manager extends \exc\core\base {
		public static $defaulView = null;
		public static $options = ['paths'=>[]];
		public static function initialize($options){

			if(!is_array($options)) return;

			$pv = [];
			$p = \exc\options::$values['app']['paths']['base'] . 'views/';

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
			chdir(\exc\options::$values['app']['path_app']);
			foreach($pv as $i=>$p){
				$p = realpath($p);
				if($p=== false) continue;
				if(substr($p,-1,1) != '/') $p.='/';
				if(in_array($p, \exc\options::$values['app']['paths']['views'])) continue;
				\exc\options::$values['app']['paths']['views'][] = $p;
				self::$options['paths'][] = $p;
			}


			view::constantCopyValues(\exc\options::$values['app']['urls'], 'URL');
			
			
			if(isset($options['views.default']) && is_string($options['views.default']) && (strlen($options['views.default']) > 0)){
				self::$options['default'] = $options['views.default'];
			}

		}
		public static function getView($name){
			///N:get a view
			$view = null;
			$view = view::createWithFile( self::getViewFileWithName($name), $name );
			return $view;
		}
	
		public static function createDefaultView($any = null){
			///N:create a default template and prints its content on commit
			///P:$view:An optional string with a view's name or an instance of \exc\ui\views\view, if non is given the 'default_page' will be loaded.
			$view = null;
			if( is_object($any) && is_a($any, '\exc\ui\views\view') ){
				$view = $any;
			}elseif( is_string($any) && (strlen($any)>0) ){ //name of a view
				$view = view::createWithFile( self::getViewFileWithName($any), $any );
			}

			if(is_null($view)) return null;

			self::$defaulView = $view;

			$app = \exc\controller\appController::instance();

			$fn = function() use ($view, $app){
				error_log("@viewCommit............");
				if($view == null) return;
				if($view->state['commited']) return;

				$client = \exc\client::instance();
				$app->publish("viewCommit", [ $view ] );
			
				$view->inline->write('');

				$js_st = $client->getState();

				$js = "<script type='text/javascript' id='excbl'>\n";
				$js.= file_get_contents( EXC_SERVER_PATH . 'assets/app.js');
				$js = str_replace('{{app_state}}', $js_st, $js);

				$js = str_replace('{{bms}}', 'R' . sha1(\exc\session::key("BS") . '-' . session_id() ), $js);
				$js.= "</script>";

				$view->body_end->write( $js );

				$app->write($view);
			};
			$app->on('appSendOutput', $fn);
			//create our closure...
	


			return self::$defaulView;
		}
		public static function getDefaultView(){
			return self::$defaulView;
		}
		public static function addViewFolder($path){
			self::$options['paths'][] = $path;
			//\exc\options::$values['app']['paths']['views'][] = $path;
		}
		public static function getViewFileWithName($n){
			$paths = self::$options['paths'];
			$n = strtolower($n);
			foreach($paths as $p){
				$f = $p . 'view.'. $n . '.php';
				if(file_exists($f)) return $f;

				$f = $p . 'view.'. $n . '.html';

				if(file_exists($f)) return $f;
			}

			$f = EXC_PATH . 'views/view.blank.php';
			return $f;
		}
	}

	class view {
		public $state = ['name'=>'','file'=>'', 'commited'=>false, 'src'=>'', 'contents'=>[],'currentName'=>'','currentContent'=>null, 'elements'=>[]];
		public static $values =[];
		public static function default($any = null){
			if(is_null(manager::$defaulView)){
				
				if(is_null($any) && isset(manager::$options['default'])){
					return manager::createDefaultView(manager::$options['default']);
				}
				if(!is_null($any)){
					return manager::createDefaultView($any);
				}
				
				return null;
			}
			
			if(!is_null($any)){
				return manager::createDefaultView($any);
			}
			return manager::$defaulView;
			
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
		function src($value, $p=null){
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

				if($m[1][0] == "view") $n = manager::getViewFileWithName($n);
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
							$e['src'] = view::createWithSource( $e['n'], substr($this->state['src'], $x2, $x3-$x2-1) );

							$x2 = $x3 + strlen($n1[0][0]);
						}
					}
					if($e['t'] == 'if'){
						$n = str_replace(']', '\]', str_replace('[', '\[', $m[1][0]));
						$r1 = "/\{\{end if {$n}\}\}/";
						if (preg_match($r1, $this->state['src'], $n1,PREG_OFFSET_CAPTURE, $x2 )){
							$x3 = $n1[0][1];
							$e['src'] = view::createWithSource( $e['n'], substr($this->state['src'], $x2, $x3-$x2-1) );
							$x2 = $x3 + strlen($n1[0][0]);
						}
					}
					if($e['t'] == 'unless'){
						$n = str_replace(']', '\]', str_replace('[', '\[', $m[1][0]));
						$r1 = "/\{\{end unless {$n}\}\}/";
						if (preg_match($r1, $this->state['src'], $n1,PREG_OFFSET_CAPTURE, $x2 )){
							$x3 = $n1[0][1];
							$e['src'] = view::createWithSource( $e['n'], substr($this->state['src'], $x2, $x3-$x2-1) );
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