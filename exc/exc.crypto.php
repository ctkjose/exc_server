<?php

namespace exc\crypto {
class b64URL {
	public static function encode($s){
		return self::escape(base64_encode($s));
	}
	public static function decode($s){
		return base64_decode(self::unescape($s));
	}
	public static function escape($s){
		return str_replace('=', '', strtr(strtr($s, '+', '-'), '/', '_'));
	}
	public static function unescape($s){
		$v = strlen($s) % 4;
        if ($v) {
            $i = 4 - $v;
            $s .= str_repeat('=', $i);
        }
		return strtr(strtr($s, '-', '+'), '_', '/');

	}
}
class jwt {
	public static function create($header, $payload, $key){
		$js = json_encode($header);
		$p = json_encode($payload);
		print_r($js);
		print_r($p);
		$o = \exc\crypto\b64URL::encode($js) . "." . \exc\crypto\b64URL::encode($p);
		$sig = hash_hmac('SHA256', $o, $key, true);
		$sig = \exc\crypto\b64URL::encode($sig);
		$out = ['sig'=> $sig, 'jwt'=> $o . '.' . $sig ];
		return $out;
	}
	public static function parse($hash){
		$jwt = ['header'=>[], 'payload'=>[], 'sig'=>''];
		$tkp = explode('.', $hash);
		$jwt['sig'] = $tkp[2];
		$jwt['header'] = json_decode(\exc\crypto\b64URL::decode($tkp[0]));
		$jwt['payload'] = json_decode(\exc\crypto\b64URL::decode($tkp[1]));
		
		return $jwt;
	}
	
}

/*
'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),

*/
/*
$key = "your-256-bit-secret";
$payload = ["sub"=>"1234567890", "name"=>"John Doe", "iat"=>1516239022];
$header = ["alg"=>"HS256", "typ"=>"JWT"];

print "<pre>";
$o = \exc\crypto\jwt::create($header, $payload, $key);
print_r($o);

print "-----------<br>";

$tk = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjEyMzQ1IiwibmFtZSI6IkpvZSBEb2UiLCJrIjoiVEVTVCJ9.O9bvjKJDrT8S-nZZyvd6KrmC5lyC0kabwAiNh6qzHq8";

print $tk . "<br>";
$jwt = \exc\crypto\jwt::parse($tk);
print_r($jwt);

print "-----------<br>";
$o = \exc\crypto\jwt::create($jwt['header'], $jwt['payload'], $key);
print_r($o);


print "</pre>";
*/
}


?>