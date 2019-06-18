<?php
namespace exc\ui\widgets {
	class base {
		public $tag = 'div';
		public $attributes = [];
		public $childs = [];
		public static function create($tag){
			$o = new base;
			$o->tag = $tag;
			return $o;
		}
		public function attr($name, $value=null){
			if(!is_null($value)){
				$this->attributes[$name] = $value;
			}

			return $this->attributes[$name];
		}
		public function hasAttr($name){
			return isset($this->attributes[$name]);
		}
		public function data($name, $value=null){
			if(!is_null($value)){
				$this->attributes['data-' . $name] = $value;
			}

			return $this->attributes['data-' . $name];
		}
		public function hasData($name){
			return isset($this->attributes['data-' . $name]);
		}
		public function addClass($name){
			//chainable, params: array of strs, str
			if(!isset($this->attributes['class'])) $this->attributes['class'] = [];

			if(is_array($name)){
				foreach($name as $k){
					$this->attributes['class'][] = $k;
				}
			}else{
				$this->attributes['class'][] = $name;
			}

			return $this;
		}

		public function hasClass($name){
			if(!isset($this->attributes['class'])){
				$this->attributes['class'] = [];
				return false;
			}

			return in_array($name, $this->attributes['class']);
		}
		public function removeClass($name){

			if(!isset($this->attributes['class'])){
				$this->attributes['class'] = [];
				return $this;
			}
			for($i=0; $i<count($this->attributes['class']); $i++){
				if($this->attributes['class'][$i] == $name){
					unset($this->attributes['class'][$i]);
					return $this;
				}
			}
			return $this;
		}
		public function append($any){
			//append a child;
			$this->childs[] = $any;
			return $this;
		}
		public function getAttributes(){
			$out = [];
			foreach($this->attributes as $k => $v){
				$prop = '';
				if(is_array($v) ){
					$prop = implode(" ", $v);
				}else{
					$prop = $v;
				}

				$prop = str_replace("'", '&#39;', $prop);
				$prop = $k . '=\'' . $prop . '\'';
				$out[] = $prop;
			}

			return join(' ', $out);
		}
		public function renderTag(){
			$cfg_single_tags = ['input'];
			$s = '<' . $this->tag . ' ' . $this->getAttributes() . '>';

			foreach($this->childs as $e){
				if(is_string($e)){
					$s.= $e;
				}elseif(is_object($e)){
					if(method_exists($e, 'renderTag')){
						$s.= $e->renderTag();
					}elseif(method_exists($e, 'getHTML')){
						$s.= $e->getHTML();
					}
				}
			}
			if(in_array($this->tag,$cfg_single_tags )) return $s;
			$s.= '</' . $this->tag . '>';

			return $s;
		}
	}
	class options extends \exc\ui\widgets\base {
		public $name = '';
		public $options = [];
		public static function create($name,$value=0){
			$o = new options;
			$o->tag = 'div';
			$o->name = $name;
			$o->setValue($value);

			$o->attr('name', $o->name);
			$o->addClass(['uiw-options', 'uiw']);

			return $o;
		}
		public static function createYesNo($name,$value=0){
			$o = new options;
			$o->tag = 'div';
			$o->name = $name;
			$o->setValue($value);


			$o->attr('name', $o->name);
			$o->addClass(['uiw-options', 'uiw', 'yesno']);

			return $o;
		}
		public function setOptions($v){
			$this->attr('options', json_encode($v));
		}
		public function setValue($v){
			$this->data('uiw-value', $v);
			$this->attr('default', $v);
		}
		public function getValue(){
			$this->data('uiw-value');
		}

		public function getHTML(){

			$s = $this->renderTag();
			//error_log($s);
			return $s;
		}
	}
	class toggle extends \exc\ui\widgets\base {
		public $name = '';
		public $options = [];
		public static function create($name,$value=0){
			$o = new toggle;
			$o->tag = 'div';
			$o->name = $name;
			$o->setValue($value);

			$o->attr('name', $o->name);
			$o->addClass(['toggle', 'uiw']);

			return $o;
		}
		public function setValue($v){
			$this->data('uiw-value', $v);
			$this->attr('default', $v);
		}
		public function getValue(){
			$this->data('uiw-value');
		}

		public function getHTML(){

			$s = $this->renderTag();
			error_log($s);
			return $s;
		}
	}

	class button extends \exc\ui\widgets\base {
		public $name = '';
		public $caption = '';
		public $options = [];
		public static function create($name){
			$o = new button;
			$o->tag = 'div';
			$o->name = $name;

			$o->addClass(['button', 'blue', 'with-border']);

			return $o;
		}
		public static function createWithCaption($name,$caption, $style='blue'){
			$o = new button;
			$o->tag = 'div';
			$o->name = $name;
			$o->setValue($value);

			$o->caption = $caption;
			$o->color_style = $style;

			$o->attr('name', $o->name);
			$o->addClass(['button', $style, 'with-border']);

			return $o;
		}
		public function setValue($v){
			$this->data('uiw-value', $v);
			$this->attr('default', $v);
		}
		public function getValue(){
			$this->data('uiw-value');
		}
		public function setColor($style){
			$this->removeClass($this->color_style)->addClass($style);
		}
		function setConfirmation($question) {
			$this->attr('data-confirm', $question);
		}
		function setActionSubmit($a) {
			$this->attr('data-action-message', $a);
		}
		function setControllerMessage($a) {
			$this->click_message = $a;
		}
		public function getHTML(){
			if(is_null($this->click_message)){
				$this->click_message = $this->attributes['name'] . '_click';
			}

			$this->addClass('on_ui_click_' . $this->click_message);

			$this->append($this->caption);
			$s = $this->renderTag();
			return $s;
		}
	}

}
?>