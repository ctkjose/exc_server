<?php
namespace exc\helper\formula {
	define('dmk', chr(6));
	define('dst', 130);

	trait expression {
		public function expressionInitializeValues($params=null){
			$this->_dictionary = [];
			$this->_dictionaryReferences = [];

			if(is_array($params)){
				$this->_dictionary = $params;
				$this->expressionResetCompiled();
			}
		}
		public function expressionSetFormula($expr){
			$this->_expressionCompiled = $this->expressionCompile($expr);
		}
		public function expressionCompile($expr){

			$cfg_re_var = '\%([A-Za-z0-9_\[\]]+)\%';
			$cfg_re_arr = '/\[([A-Za-z0-9_]+)\]/';

			$expr = str_replace(' Y ', ' && ', $expr);
			$expr = str_replace(' O ', ' || ', $expr);
			$expr = str_replace(' y ', ' && ', $expr);
			$expr = str_replace(' o ', ' || ', $expr);
			$expr = str_replace(' AND ', ' && ', $expr);
			$expr = str_replace(' OR ', ' || ', $expr);
			$expr = str_replace(' and ', ' && ', $expr);
			$expr = str_replace(' or ', ' || ', $expr);
			$expr = str_replace(' = ', ' == ', $expr);
			$expr = str_replace(' <> ', ' != ', $expr);

			$patterns = [
				['\#(\d{4}\-\d{2}\-\d{2})\#', 'dateliteral', 1],
				['\#(\d{2}\-\d{2}\-\d{4})\#', 'dateliteral', 1],
				['\#(\d{2}\-\d{2}\-\d{2})\#', 'dateliteral', 1],
				['\#(\d{4}\-\d{2}\-\d{2}\s+\d{1,2}\:\d{1,2}\:\d{1,2})\#', 'dateliteral', 1],
				['\#(NOW)\#', 'now', 1],
				['\#(TODAY)\#', 'today', 1],
				['\#(YEAR)\#', 'year', 1],
				['\#(MONTH)\#', 'month', 1],
				['\#(DAY)\#', 'day', 1],
				['\#(DATETIME)\#', 'dt', 1],
				['\#([dDJlNSwWzFmMntLoYyaABgGhHisueIOPTZcru\s\:\-\,\/]+)\#', 'dateformat', 1],
			];

			foreach($patterns as $rp){
				$re = '/' . $rp[0] . '/';
				if(preg_match_all($re, $expr, $m) < 1) continue;
				if(!isset($m[1]) || (count($m[1]) == 0)) continue;
				foreach($m[1] as $k){
					if($rp[1]=='dateliteral'){
						$expr = str_replace('#' . $k . '#',''.strtotime($k) , $expr);
					}elseif($rp[1]=='dateformat'){
						$expr = str_replace('#' . $k . '#',''.date($k) , $expr);
					}elseif($rp[1]=='now'){
						$expr = str_replace('#' . $k . '#',''.time(), $expr);
					}elseif($rp[1]=='today'){
						$expr = str_replace('#' . $k . '#',''.mktime(0,0,0), $expr);
					}elseif($rp[1]=='year'){
						$expr = str_replace('#' . $k . '#',''.date("Y"), $expr);
					}elseif($rp[1]=='day'){
						$expr = str_replace('#' . $k . '#',''.date("j"), $expr);
					}elseif($rp[1]=='month'){
						$expr = str_replace('#' . $k . '#',''.date("n"), $expr);
					}elseif($rp[1]=='dt'){
						$expr = str_replace('#' . $k . '#',''.date('Y-m-d H:i:s'), $expr);
					}
				}
			}

			$patterns = [
				['\%([A-Za-z0-9_\[\]]+)\%', 'fn'=>'value', 'var_idx'=>1],
				['\#([A-Za-z0-9_\[\]]+)\#', 'fn'=>'valuedate', 'var_idx'=>1],
				['([A-Z]+)\(\x06([\x58-\xf0]+)\x06\s*(\,([^\)]+){0,1}){0,1}\)', 'fn'=> 'method', 'ref_idx'=>2, 'fn_idx'=>1,'args_idx'=>4 ],
			];

			$parts = [];
			$compiled = [];
			foreach($patterns as $rp){
				$re = '/' . $rp[0] . '/';
				$t = $rp['fn'];

				preg_match_all($re, $expr, $m);

				while( count($m) >= 1){
					//if(!isset($m[0]) || (count($m[0]) == 0)) continue;

					$value_type = 'v';
					if(isset($rp['ref_idx'])){
						$vidx = $rp['ref_idx'];
						$value_type = 'r';
					}elseif(isset($rp['var_idx'])){
						$vidx = $rp['var_idx'];
					}


					foreach($m[$vidx] as $i => $k){
						//print "$i=$k\n";
						if(in_array($m[0][$i], $compiled)) continue;

						$idx = count($parts);
						$idxk = dmk . chr($idx+dst) . dmk;

						$var = $k;
						$p = ['k'=> $idxk, 'idx'=> $idx, 'resolved'=>0, 'm'=>$m[0][$i], 'type'=>$t, 'fn'=> null, 'value'=>null, 'value_ref'=> $k, 'value_type'=>$value_type, 'value_arr_index'=> null, 'args'=>null ];
						$compiled[] = $m[0][$i];
						if(($value_type == 'v') && preg_match($cfg_re_arr, $p['value_ref'], $vm)){
							$p['value_ref'] = str_replace($vm[0], '', $p['value_ref']);
							$p['value_arr_index'] = $vm[1];
						}
						if($value_type == 'r'){
							$p['value_ref'] = str_replace(dmk,'', $p['value_ref']);
							$p['value_ref'] = ord($p['value_ref']) - dst;
						}

						if(isset($rp['args_idx'])){
							$p['args'] = $m[$rp['args_idx']][$i];
						}
						if(isset($rp['fn_idx'])){
							$p['fn'] = $m[$rp['fn_idx']][$i];
						}
						$parts[] = $p;

						$expr = str_replace($p['m'],$idxk, $expr);
						//print "expr=" . $expr . "\n";
					}

					if( preg_match_all($re, $expr, $m) < 1) break;
				}
			}

			$out = ['parts'=> $parts, 'expr'=> $expr];

			return $out;
		}
		public function expressionGetPartValue($idx, &$cexpr){
			$p = $cexpr['parts'][$idx];

			if($p['resolved']) return $p;

			$pv = null;
			if($p['value_type'] == 'r'){
				$pv = $this->expressionGetPartValue($p['value_ref'], $cexpr);
				if(is_null($pv)) return null;
			}elseif($p['value_type'] == 'v'){
				if(!$p['resolved']){
					$cexpr['parts'][$idx]['value'] =  $this->expressionGetDictValue(	$p['value_ref'], $p['value_arr_index'] );
					$cexpr['parts'][$idx]['resolved'] = 1;
					$pv = $cexpr['parts'][$idx];
				}else{
					$pv = $p;
				}
			}

			$cfg_methods = [
				'UPPER' => function($v1, $args=null){
					return strtoupper($v1);
				},
				'LOWER' => function($v1, $args=null){
					return strtolower($v1);
				},
				'TRIM' => function($v1, $args=null){
					return trim($v1);
				},
				'LTRIM' => function($v1, $args=null){
					return ltrim($v1);
				},
				'RTRIM' => function($v1, $args=null){
					return rtrim($v1);
				},
				'MID' => function($v1, $args=null){
					$s = '';
					@eval( '$s=substr(' . $this->expressionGetSafeLiteral($v1) . ',' . $args . ');');
					return $s;
				},
				'REPLACE' => function($v1, $args=null){
					$s = '';
					@eval( '$s=str_replace(' . $args . ',' . $this->expressionGetSafeLiteral($v1)  . ');');
					return $s;
				},
				'RPAD' => function($v1, $args=null){
					$s = '';
					@eval( '$s=str_pad(' . $this->expressionGetSafeLiteral($v1) . ',' . $args . ');');
					return $s;
				},
				'LPAD' => function($v1, $args=null){
					$s = '';
					@eval( '$s=str_pad(' . $this->expressionGetSafeLiteral($v1) . ',' . $args . ', STR_PAD_LEFT);');
					return $s;
				},
				'DATE' => function($v1, $args=null){
					$s = '';
					@eval( '$s=date(' . $args . ', ' . $this->expressionGetSafeLiteral($v1) . ');');
					return $s;
				},
				'NUMBER' => function($v1, $args=null){
					$s = '';
					@eval( '$s=number_format(' . $this->expressionGetSafeLiteral($v1) . ',' . $args . ');');
					return $s;
				},
				'DATEVALUE' => function($v1, $args=null){
					$s = '';
					@eval( '$s=strtotime(' . $this->expressionGetSafeLiteral($v1) . ');');
					return $s;
				},
				'DAY' => function($v1, $args=null){
					$s = '';
					@eval( '$s=date("j", strtotime(' . $this->expressionGetSafeLiteral($v1) . '));');
					return $s;
				},
				'YEAR' => function($v1, $args=null){
					$s = '';
					@eval( '$s=date("Y", strtotime(' . $this->expressionGetSafeLiteral($v1) . '));');
					return $s;
				},
				'MONTH' => function($v1, $args=null){
					$s = '';
					@eval( '$s=date("n", strtotime(' . $this->expressionGetSafeLiteral($v1) . '));');
					return $s;
				},
				'WEEKNUM' => function($v1, $args=null){
					$s = '';
					@eval( '$s=date("W", strtotime(' . $this->expressionGetSafeLiteral($v1) . '));');
					return $s;
				},
			];


			$v1 = $pv['value'];
			if($p['type'] == 'method'){
				if(isset($p['fn']) && isset($cfg_methods[$p['fn']])){
					$fn = $cfg_methods[$p['fn']];
					$v1 = $fn($v1, $p['args']);
				}else{
					$v1 = '';
				}
			}

			$cexpr['parts'][$idx]['value'] = $v1;
			$cexpr['parts'][$idx]['resolved'] = 1;
			return $cexpr['parts'][$idx];

		}
		public function expressionResetCompiled(){
			if(!isset($this->_expressionCompiled)) return;
			foreach($this->_expressionCompiled['parts'] as $i=> $e){
				$this->_expressionCompiled['parts'][$i]['resolved'] = 0;
				$this->_expressionCompiled['parts'][$i]['value'] = null;
			}
		}
		public function expressionGetDictValue($n, $var_index){

			$v1 = null;
			$found = false;
			if(isset($this->_dictionary[$n])){
				$v1 = $this->_dictionary[$n];
				$found = true;
			}elseif(isset($this->_dictionaryReferences) && is_array($this->_dictionaryReferences) && (count($this->_dictionaryReferences) >0) ){
				foreach($this->_dictionaryReferences as $ctx => $values){
					if(strpos($n, $ctx . '_') !== 0) continue;
					$vark = substr($n, strlen($ctx.'_'));
					//error_log('vark=' . $vark  .'=' . $var . '=' . $var_index);
					if(is_array($values)){
						if(isset($values[$vark])) return $values[$vark];
						$vark = strtolower($vark);
						if(isset($values[$vark])) return $values[$vark];
						$vark = strtoupper($vark);
						if(isset($values[$vark])) return $values[$vark];
					}elseif(is_object($values)){
						if(isset($values->$vark)) return $values->$vark;
						$vark = strtolower($vark);
						if(isset($values->$vark)) return $values->$vark;
						$vark = strtoupper($vark);
						if(isset($values->$vark)) return $values->$vark;
					}
					$found = true;
					$v1 = $values[$vark];
					break;
				}
			}

			if(!$found) return null;

			if(!is_null($var_index)){
				if(is_array($v1)){
					if(isset($v1[$var_index])) return $v1[$var_index];
					$var_index = strtolower($var_index);
					if(isset($v1[$var_index])) return $v1[$var_index];
					$var_index = strtoupper($var_index);
					if(isset($v1[$var_index])) return $v1[$var_index];
				}elseif(is_object($v1)){
					if(isset($v1->$var_index)) return $v1->$var_index;
					$var_index = strtolower($var_index);
					if(isset($v1->$var_index)) return $v1->$var_index;
					$var_index = strtoupper($var_index);
					if(isset($v1->$var_index)) return $v1->$var_index;
				}
				return null;
			}

			return $v1;
		}
		public function expressionEvaluate($expr = null){
			$expr = $this->expressionInterpolate($expr);

			if(empty($expr)) return false;
			//print "expr =" . $expr . "\n";

			$v = false;
			@eval('$v=(' . $expr . ');');
			return $v;
		}
		public function expressionInterpolate($expr = null){
			if(is_string($expr)){
				$cexpr = $this->expressionCompile($expr);
				$this->_expressionCompiled = $cexpr;
			}elseif(is_null($expr) ){
				if(isset($this->_expressionCompiled)){
					$cexpr = $this->_expressionCompiled;
				}else{
					return null;
				}
			}

			$eval = $cexpr['expr'];

			for( $i=0; $i< count($cexpr['parts']); $i++){
				$this->expressionGetPartValue($i, $cexpr);
				$v = $cexpr['parts'][$i]['value'];
				if(is_null($v)) $v='NULL';
				if(is_object($v)) $v='';

				$eval = str_replace($cexpr['parts'][$i]['k'], $v, $eval);
				//print "eval[$i]=" . $eval . " =====================================================\n\n\n";
				//break;
			}

			return $eval;


		}
		public function expressionsApplyTransforms($transforms, $params){
			if(!is_array($transforms)) return $params;

			$fnNP = function($a){
				return $a;
			};
			$fnWith = function($xp, $w, $fn){

				$o = [];
				if(is_array($w)){
					foreach($w as $k){

						$v = isset($xp[$k]) ? $xp[$k] : '';
						$o[$k] = $fn($v);
					}
				}elseif(is_string($w)){
					$v = isset($xp[$w]) ? $xp[$w] : '';
					$o[$w] = $fn($v);
				}
				return $o;
			};
			$xp = $params;


			foreach($transforms as $e){

				$v1 = '';
				if(isset($e['with']) && strlen($e['with'])>0){
					$v1 = $this->expressionValue($e['with']);
				}


				$s = '';
				$do = $e['do'];
				print_r($e);



				print_r($o);
				if( isset($e['set']) ){

				}
			}


			return $xp;
		}
		public function expressionGetValue(){
			$args = func_get_args();
			$c = count($args);
			if($c == 0) return $this->_dictionary;

			if($c >= 1){
				$out = [];
				foreach($args as $k){
					$out[$k] = isset($this->_dictionary[$k]) ? $this->_dictionary[$k] : '';
				}
				if($c==1) return array_pop($out);
				return $out;
			}

			return null;
		}
		public function expressionSetValue(){
			$args = func_get_args();
			$c = count($args);
			if( ($c == 1) && is_array($args[0]) ){
				foreach($args[0] as $k=>$v){
					$this->_dictionary[strtoupper($k)] = $v;
				}
			}elseif($c > 1){
				if(($c % 2) == 0){
					for($i = 0; $i <= $c-1; $i+=2){
						$k = strtoupper($args[$i]);
						$this->_dictionary[$k] = $args[$i+1];
					}
				}
			}

			$this->expressionResetCompiled();
		}
		public function expressionUseValues(&$values, $context){
			//allows to use the $values under the specified context
			//without copying the values into the object
			//$values is an array or object

			if($values === false) return;
			if(is_null($values)) return;
			if(!isset($this->_dictionaryReferences)) $this->_dictionaryReferences = [];
			$this->_dictionaryReferences[$context] = &$values;
			return;
		}
		public function expressionCleanValues($opAll=false){
			$this->_dictionary = [];
			if($opAll) $this->_dictionaryReferences = [];
		}
		public function expressionCleanReferences($opAll=false){
			$this->_paramsReferences = [];
		}
		public function expressionCopyValues(&$values, $context = NULL, $optUseReference=false){
			///N:Copy value pairs from the hash $values to the formula's dictionary
			if($values === false) return;
			if(is_null($values)) return;


			if($optUseReference){
				$this->_dictionaryReferences[$context] = &$values;
				return;
			}

			if(is_object($values)){
				if( method_exists($values, 'convert_toDictionary')){
					$values = $values->convert_toDictionary();
				}else{
					$values = get_object_vars($values);
				}
			}

			if( count($values) <= 0 ) return;
			foreach($values as $k => $v){
				set_time_limit(30);
				if(!is_null($context)) $k = $context . '_' . $k; //build a new key scoped with context
				$k = strtoupper($k);
				$this->_dictionary[$k] = $v;
			}
		}
		public function expressionGetSafeLiteral($v){
			if( is_string($v) ){
				if( ( substr($v,1,1) == chr(34) ) && ( substr($v,-1,1) == chr(34) ) ) return $v;
				$v = str_replace('"', '', $v);
				$v = chr(34) . $v . chr(34);
			}elseif(is_bool($v)){
				$v = ($v) ? 'true' : 'false';
			}elseif(is_null($v)){
				$v = 'false';
			}

			return $v;
		}
	};
}
namespace exc\helpers {
	class formula {
		use \exc\helper\formula\expression;
		public function __construct($expression = null){
			if(is_string($expression)){
				$this->expressionSetFormula($expression);
			}
		}
		public function setFormula($s){
			if(is_string($s)){
				$this->expressionSetFormula($s);
			}
		}
		public function when(){
			$v = $this->expressionEvaluate();
			if($v) return true;
		}
		public function value(){
			return $this->expressionEvaluate();
		}
	}
}
?>