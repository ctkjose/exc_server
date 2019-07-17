<?php
namespace exc\helper\dictionary {
	trait whereFilter {
		public function whereFilterBuild($args){
			$c = count($args);
			$f = [];

			if($c == 2){
				$o = $this->whereFilterField($args[0]);
				$o['v'] = $args[1];
				$this->whereFilter[] = $o;
			}elseif($c == 1){
				$a = $args[0];
				if(is_array($a)){
					foreach($a as $k => $v){
						$o = $this->whereFilterField($k);
						$o['v'] = $v;
						$this->whereFilter[] = $o;
					}

				}elseif(is_string($a)){
					$search.= '(' . $a . ')';
				}
			}elseif(($c % 2) == 0){
				for($i = 0; $i <= $c-1; $i+=2){
					$k = $args[$i];
					$v = $args[$i+1];

					$o = $this->whereFilterField($args[0]);
					$o['v'] = $v;
					$this->whereFilter[] = $o;
				}

			}

		}
		public function whereFilterField($s){

			$o = ['op'=> '=', 'f'=>$s, 'v'=>'' ];
			if(strpos($s,' ') !== false){
				$p = array_map('trim', explode(' ', $s));
				$o['op'] = strtolower($p[1]);
				$o['f'] = $p[0];
			}
			return $o;
		}
		public function whereFilterInit(){
			$this->whereFilter = [];
		}
		public function where() {
			//$this->whereFilterInit();

			if(!is_array($this->whereFilter)) $this->whereFilter = [];
			$this->whereFilterBuild(func_get_args());

			return $this;
		}
	}

	trait iterator {  ///NST:MARK:CLASS:iterator
		public function __toString() {
			$s = '';
			foreach($this->values as $e){
				$s.= implode('|', $this->values) . "\n";
			}
			return $s;
		}
		public function filterWithFormula($where){
			$out = [];
			if(is_string($where)){
				$where = new \exc\helper\formula($where);

			}elseif(is_object($where) && !method_exists($where, 'expressionEvaluate')){
				return $out;
			}elseif(!is_object($where)){
				return $out;
			}

			//print_r($where);

			//return ['kd1'];
			foreach($this->values as $e){
				$matched = true;

				$where->expressionCleanValues();
				$where->expressionSetValue($e);

				$matched = $where->expressionEvaluate();
				if($matched) $out[] = $e;

			}

			return $out;

		}
		public function find($where=null){
			return $this->filter($where);
		}
		public function filter($where=null){
			$out = [];

			if(is_string($where)){
				return $this->filterWithFormula(new \exc\helper\formula($where));
			}elseif(is_object($where)){
				if( method_exists($where, 'expressionEvaluate')) return $this->filterWithFormula($where);
				return $out;
			}

			if(is_null($where)){
				if(!isset($this->whereFilter)){
					return $out;
				}
				if(!is_array($this->whereFilter) || !count($this->whereFilter)){
					return $out;
				}

				$wc = $this->whereFilter;
			}elseif(is_array($where)){
				$fnpk = function($k){

					$o = ['op'=> '=', 'f'=>$k, 'fn'=> 'opLogical'];
					if(strpos($k,' ') !== false){
						$p = array_map('trim', explode(' ', $k));
						$o['op'] = strtolower($p[1]);
						$o['f'] = $p[0];
					}
					return $o;
				};

				$wc = [];
				foreach($where as $wk => $w){
						$fk = $fnpk($wk);
						$fk['v'] = $w;
						$wc[] = $fk;
				}

			}else{
				return $out;
			}



			foreach($this->values as $e){
				$matched = true;
				foreach($wc as $w){

					$k = $w['f'];
					$op = $w['op'];
					$wv = $w['v'];

					if(!array_key_exists($k, $e)){
						$matched = false;
						break;
					}

					$v = $e[$k];

					$ok = false;
					if($op == '!='){
						if(is_array($v)){
							$ok = false;
							foreach($v as $v1){
								if(is_array($wv)){
									$ok = !in_array($v1, $wv);
								}else{
									$ok = ($v1 != $wv);
								}
								if(!$ok) break;
							}
						}else{
							if(is_array($wv)){
								$ok = !in_array($v, $wv);
							}else{
								$ok = ($v != $wv);
							}
						}
					}elseif($op == '>'){
						$ok = ($v > $wv);
					}elseif($op == '<'){
						$ok = ($v < $wv);
					}elseif($op == '>='){
						$ok = ($v >= $wv);
					}elseif($op == '<='){
						$ok = ($v <= $wv);
					}elseif($op == 'between') {
						$ok = ( ($v >= $wv[0]) && ($v <= $wv[1]) );
					}elseif($op == '='){

						if(is_array($v)){
							foreach($v as $v1){
								if(is_array($wv)){
									$ok = $ok || in_array($v1, $wv);
								}else{
									$ok = $ok || ($v1 == $wv);
								}
								if($ok) break;
							}
						}elseif(is_array($wv)){
							$ok = in_array($v, $wv);
						}else{
							$ok = ($v == $wv);
						}
					}elseif($op == 'in'){
						if(is_array($v)){
							foreach($v as $v1){
								if(is_array($wv)){
									$ok = $ok || in_array($v1, $wv);
								}else{
									$ok = $ok || ($v1 == $wv);
								}
								if($ok) break;
							}
						}elseif(is_array($wv)){
							$ok = in_array($v, $wv);
						}else{
							$ok = ($v == $wv);
						}
					}elseif($op == 'like'){
						if(is_array($v)){
							foreach($v as $v1){
								if(is_array($wv)){
									foreach($wv as $wv1){
										$ok = $ok || preg_match('/' . $wv1 . '/', $v );
										if($ok) break;
									}
								}else{
									$ok = $ok || preg_match('/' . $wv . '/', $v );
								}
								if($ok) break;
							}
						}elseif(is_array($wv)){
							foreach($wv as $wv1){
								$ok = $ok || preg_match('/' . $wv1 . '/', $v );
								if($ok) break;
							}
						}else{
							$ok = preg_match('/' . $wv . '/', $v );
						}
					}

					$matched = $matched && $ok;

				}

				if($matched){
					$out[] = $e;
				}
			}
			return $out;
		}

		public function offsetSet($offset, $value) {
			if (is_null($offset)) {
				$this->values[] = $value;
			} else {
				$this->values[$offset] = $value;
			}
		}
		public function offsetExists($offset) {
			return isset($this->values[$offset]);
		}

		public function offsetUnset($offset) {
			unset($this->values[$offset]);
		}

		public function offsetGet($offset) {
			return isset($this->values[$offset]) ? $this->values[$offset] : null;
		}
		public function rewind() {
		   $this->idx = 0;
		}

		public function current() {
			return $this->values[$this->idx];
		}

		public function key() {
			return $this->idx;
		}

		public function next() {
			++$this->idx;
		}

		public function valid() {
		   return isset($this->values[$this->idx]);
		}
	}


	//Key Value Pairs Interface and Helpers
	//implements a flat Key Value Store
	class kvMapper {
		use \exc\helper\dictionary\iterator, \exc\objectExtendable;

		public $values =[];
		public $idx=0;

		public static function valueSet(&$values, $args, $prefix=''){
			$c = count($args);
			if( ($c == 1) && (is_array($args[0]) || is_object($args[0])) ){
				self::copyValues($values, $args[0], $prefix);
			}elseif($c > 1){
				if(($c % 2) == 0){
					for($i = 0; $i <= $c-1; $i+=2){
						$k = strtoupper($args[$i]);
						if(!empty($prefix)) $k = $prefix . '_' . $k;
						$values[$k] = $args[$i+1];
					}
				}
			}
		}
		public static function copyArray(&$d,$a,$prefix='', $pk='_'){
			if(strlen($prefix)){
				if(count($d)>0) $pk = ':';
				$prefix .= $pk;
			}else{
				$pk ='_';
			}
			$flg_has_count = false;
			$flg_count=0;
			$flg_count_fx = 0;
			foreach($a as $k => $v){
				if(is_numeric($k)){
					$flg_count++;
					if(!$flg_has_count) $flg_count_fx = ($k==0) ? 1:0;
					$flg_has_count=true;

					$k = '[' . ($k+$flg_count_fx) . ']';
					$k = substr($prefix,0,-1) . $k;
				}else{
					$k = str_replace(' ', '', str_replace('|',':', $k));
					$k = $prefix . $k;
				}
				$k = strtoupper($k);

				if(is_array($v)){
					self::copyArray($d,$v,$k,$pk);
				}elseif(is_object($v)){
					self::copyValues($d,$v,$k);
				}elseif(is_bool($v)){
					$d[$k] = $v ? 1:0;
				}elseif(is_string($v) || is_numeric($v)){
					$d[$k] = $v;
				}
			}
			if($flg_has_count){
				$k = strtoupper(substr($prefix,0,-1)) . '[]:COUNT';
				$d[$k] = $flg_count;
			}

		}
		public static function copyValues(&$d, $o, $prefix='', $opRemove=[], $pk='_'){
			if(!is_array($d)) return;

			$a = null;
			if(is_object($o)){
				$a = get_object_vars($o);
			}elseif(is_array($o)){
				$a = $o;
			}else{
				return;
			}

			if(is_null($a)) return;
			if(is_array($opRemove)){
				foreach($opRemove as $k){ unset($a[$k]); }
			}

			self::copyArray($d,$a, $prefix, $pk);
		}
		public static function decorate($o){
			$o->delegate('setValue', function() use ($o){
				\exc\helper\dictionaryKVMapper::valueSet($o->values, func_get_args());
			});
			$o->delegate('copyValues', function($any, $prefix='')  use ($o){
				\exc\helper\dictionaryKVMapper::valueSet($o->values, [$any], $prefix);
			});
		}
		public function create(){
			$o = new \exc\helper\dictionaryKVMapper();
			self::decorate($o);

			return $o;
		}

	}
}
namespace exc\helper {
	class dictionary {
		use \exc\helper\dictionary\iterator, \exc\objectExtendable;

		public $idx = 0;
		public $values =[];
	}
}
?>