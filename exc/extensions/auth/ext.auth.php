<?php
/*

*/
namespace ext\auth;

define('EXC_AUTH_STATUS_NONE', 0);
define('EXC_AUTH_STATUS_PENDING', 1);
define('EXC_AUTH_STATUS_EXPIRED', 2);
define('EXC_AUTH_STATUS_FAILED', 3);
define('EXC_AUTH_STATUS_COMPLETED', 10);

error_log("@module auth.....................");

class manager {
	public static $db = null;
	public static $user = null;
	public static $options = [];
	public static $loginController = null;
	public static $authentication = ['status'=>EXC_AUTH_STATUS_NONE, 'required'=>false, 'user'=>false, 'msg'=>''];
	public static function initialize(){
		$app = \exc\app::controller();
		$app->on("appInit", ['\ext\auth\manager', 'loadAuthentication']);
	}
	public static function loadAuthentication(){
		error_log('-------  @loadAuthentication()');

		$default = [
			'db.connection'=> 'auth',
			'enabled'=> true,
			'require.login'=> false,
			'login.controller' => ['class'=>'\ext\auth\loginController', 'file'=>'extensions://auth/controller.login.php']
		];
	
		$app = \exc\app::controller();
		$options = $app->getOption('extension.auth');   //\exc\options::key('/app/extension.auth');
	
		
		self::$options = \exc\base::extend($default, $options);

		

		if(!self::$options['enabled']){
			return;
		}

		self::$db = \exc\storage\db::connection(self::$options['db.connection']);
		
		\exc\view::addViewFolder(__DIR__ . '/views/');
			
		self::$authentication['status'] = EXC_AUTH_STATUS_PENDING;
		self::$loginController = \exc\app::registerController(self::$options['login.controller']['class'], self::$options['login.controller']['file']);
		if(!is_object(self::$loginController)){
			self::authInvalidate(EXC_AUTH_STATUS_FAILED, 'Internal Error, [ERR-AUTH-404]');
			error_log('EXC|ERROR|AUTH|LOGIN CONTROLLER NOT LOADED|' . self::$options['login.controller']['class'] . '|' . self::$options['login.controller']['file']);
			return;
		}
		

		if( self::isAuthAction() ){
			self::$loginController->handleAction(\exc\app::$appController->currentAction);
			return;
		}
		if( self::$options['require.login']){
			self::$loginController->validateLogin();
		}

	}
	public static function isAuthAction(){
		$cfg_auth_actions = ['exc_auth_login','exc_auth_logout', 'exc_auth_login_do'];
		$a = \exc\app::$appController->currentAction;
		return in_array($a, $cfg_auth_actions);
	}
	public static function authenticate($user_login, $pass){
		$app = \exc\app::controller();

		error_log('EXC|INFO|AUTH|AUTHENTICATE REQUEST|' . $user_login);

		$user = apply_filters( 'authenticate', null, $user_login, $pass );
		if( !empty($user) && ($user instanceof \ext\auth\user) ){
			error_log('EXC|INFO|AUTH|AUTHENTICATED BY EXTERNAL SOURCE|' . $user_login . '|' . $user->uid . '|' . $user->user_email);
		}
		 
		$db = self::$db;

		$user = user::getUserByField('user_login', $user_login);
		if(empty($user)){
			error_log('EXC|WARN|AUTH|AUTHENTICATION FAILED, USER NOT FOUND|' . $user_login );
			return null;
		}

		error_log('EXC|INFO|AUTH|FOUND USER|' . $user_login . '|' . $user->uid . '|' . $user->user_email);


		$ok = $user->passwordVerify($pass);
		if(!$ok){
			error_log('EXC|WARN|AUTH|AUTHENTICATION FAILED, BAD PASSWORD|' . $user_login . '|' . $user->uid . '|' . $user->user_email );
			return null;
		}
		error_log('EXC|WARN|AUTH|AUTHENTICATION SUCCESS|' . $user_login . '|' . $user->uid . '|' . $user->user_email );
		
		return $user;
	}
	public static function authCompleteWithUser($user){
		self::$authentication['status'] = EXC_AUTH_STATUS_COMPLETED;
		self::$authentication['msg'] = '';
		self::setCurrentUser($user);
	}
	public static function authInvalidate($status=EXC_AUTH_STATUS_FAILED, $msg=null){
		self::$authentication['status'] = $status;
		self::$authentication['msg'] = is_string($msg) ? $msg : '';
		self::$authentication['user'] = null;
	}
	public static function setCurrentUser($user){
		self::$authentication['user'] = $user;
	}
	public static function getCurrentUser(){
		return self::$user;
	}
	public static function isEmailRegistered($email){
		$db = self::$db;
		$rs = $db->where('user_email', $email)->get('exc_auth_users');
		if(!$rs->read()) return false;
		return $value . '2';
	}
}
class user {
	//password_hash("rasmuslerdorf", PASSWORD_BCRYPT);
	public $data = [];
	public $attributes = [];
	public $site = 'ALL';
	public $uid = '';
	protected $dbID = 0;
	protected $role_attributes = [];
	protected $role_permissions = [];
	protected $roles = [];

	public static function getUserByAttribute($value, $key=null){
		$db = manager::$db;

		if(!is_null($key)) $db->where('user_attr_name', $key);
		$rs = $db->where('user_attr_value', $value)->select('user_uid')->get('exc_auth_user_attributes');
		//error_log($db->debug_sql);
		if(!$rs->read()) return null;

		return self::getUserByField('user_uid', $rs->fields['user_uid']);
	}
	public static function getUserByField($key, $value){
		$db = manager::$db;

		$rs = $db->where($key, $value)->get('exc_auth_users');
		if(!$rs->read()) return null;

		$a = ['src'=>'db', 'data'=> $rs->fields ];

		$user = new user;
		$user->dbID = $rs->fields['id'];
		$user->loadWithData($a);
		return $user;
	}
	public function __construct( $uid = '', $site = 'ALL' ){
		if(!empty($uid)){
			$this->loadByUID($uid);
		}
		
		if(!empty($this->uid)){
			$this->siteMakeActive($site);
		}
	}
	public function siteMakeActive( $site ){
		$this->site = $site;
	}
	public function loadByUID($uid){
		$db = manager::$db;

		$rs = $db->where('user_uid', $uid)->get('exc_auth_users');
		if(!$rs->read()) return false;

		$a = ['src'=>'db', 'data'=> $rs->fields ];

		$this->dbID = $rs->fields['id'];
		$this->loadWithData($a);
		return true;
	}
	public function loadWithData($a){
		$this->data = [];
		$this->attributes = [];
		$this->roles = [];


		$a['data'] = \exc\app::applyFilter('auth_user_data_load', $a['data']);

		if(isset($a['data']) && is_array($a['data'])){
			$this->data = $a['data'];
		}

		$cfg_required = ['user_uid'=>'', 'user_login'=>'guest', 'user_email'=>'', 'user_type'=>0, 'user_fname'=>'Guest', 'user_lname'=>'User'];
		$cfg_required['user_uid'] = 'G' . time() . '-' . uniqid();


		foreach($cfg_required as $n => $v) {
			if(isset($this->data[$n])) continue;
			$this->data[$n] = $v;
		}

		$this->uid = $this->data['user_uid'];

		if(isset($a['attributes']) && is_array($a['attributes'])){
			$this->attributes = $a['attributes'];
		}else{
			$this->attributesLoad();
		}

		
	}
	public function hasAttr($name){
		return isset($this->attributes[$name]);
	}
	public function removeAttr($name){
		if(isset($this->attributes[$name])) unset($this->attributes[$name]);
		return $this;
	}
	public function attr($name, $value=null){
		if(is_null($value)){
			if(isset($this->attributes[$name])) return $this->attributes[$name];
			if(isset($this->role_attributes[$name])) return $this->role_attributes[$name];
			return null;
		}

		$this->attributes[$name] = $value;
		return $this;
	}
	public function attributesLoad(){
		$this->attributes = [];

		$db = manager::$db;
		
		$rs = $db->where('user_uid', $this->uid )->get('exc_auth_user_attributes');
		while($rs->read()){
			$v = $rs->fields['user_attr_value'];
			$this->attributes[$rs->fields['user_attr_name']] = $v;
		}

		$this->attributes = \exc\app::applyFilter('auth_user_attributes_load', $this->attributes);
	}
	public function attributesSave(){
		
		$attrs = \exc\app::applyFilter('auth_user_attributes_save', $this->attributes, $this);

		$db = manager::$db;
		$rs = $db->where('user_uid', $this->uid )->get('exc_auth_user_attributes');

		$records = [];
		while($rs->read()){
			$n = $rs->fields['user_attr_name'];
			
			if(array_key_exists($n, $attrs)){
				$records[$n] = ['cmd'=>'update', 'id'=> $rs->fields['id'], 'v'=>$attrs[$n]];
			}else{
				$records[$n] = ['cmd'=>'delete', 'id'=> $rs->fields['id']];
			}
		}

		foreach($attrs as $n => $v){
			if(array_key_exists($n, $records)) continue;
			$records[$n] = ['cmd'=>'insert', 'v'=> $v];
		}

		foreach($records as $n => $e){
			$cmd = $e['cmd'];
			if(isset($e['id'])){
				$db->where('id', $e['id'], 'user_uid', $this->uid);
			}
			if($cmd == 'insert'){
				$r = ['user_uid'=> $this->uid, 'user_attr_name'=> $n, 'user_attr_value'=> $e['v']];
				$db->insert('exc_auth_user_attributes', $r);
			}elseif($cmd == 'update'){
				$r = ['user_attr_value'=> $e['v']];
				$db->where('id', $e['id'], 'user_uid', $this->uid);
				$db->update('exc_auth_user_attributes', $r);
			}elseif($cmd == 'delete'){
				$db->where('id', $e['id'], 'user_uid', $this->uid);
				$db->delete('exc_auth_user_attributes');
			}

			error_log('SQL=' . $db->debug_sql);
		}
	}
	public function loadRoles(){
		$roles = role::getRolesForUser($this);
		
		$this->role_attributes = [];
		$this->role_permissions = [];
		if(!is_array($roles)) return false;
		$this->roles = $roles;

		foreach($roles as $k => $e){
			if(!$e['enabled']) continue;
			foreach($e['attributes'] as $k => $attr){
				$this->role_attributes[$k] = $attr['value'];
			}
		}
		
		return true;
	}
	public function hasRole($ruid){
		if(!is_array($this->roles)) return false;
		if(!isset($this->roles[$ruid])) return false;

		return $this->roles[$ruid]['enabled'];
		
	}
	public function hasPermission($tag){
		if(!is_array($this->roles)) return false;
		foreach($this->roles as $ruid => $role){
			if(!$role['enabled']) continue;
			if(array_key_exists($tag, $role['permissions'])) return true;
		}

		return false;
	}
	public function passwordSet($npass = null){
		$npass = \exc\app::applyFilter('auth_user_password_set', $npass, $this);
		if(empty($npass)){
			return false;
		}

		$pass = base64_encode('PEXC' . $npass);
		$this->data['user_auth_key'] = password_hash($pass, PASSWORD_BCRYPT);
		
		return true;
	}
	public function passwordVerify($pass){
		
		if(empty($pass)){
			return false;
		}

		$pass1 = base64_encode('PEXC' . $pass);
		$this->data['user_auth_key'] = password_hash($pass, PASSWORD_BCRYPT);
		
		return password_verify($pass1, $this->data['user_auth_key']);
	}
	public function save(){
		$id = 1 * $this->dbID;
		$action = 'update';
		if($id <= 0){
			$action = 'insert';
		}

		error_log("@user save-------------------");
		\exc\error_log_dump($this);
		$data = $this->data;
		$data = \exc\app::applyFilter('auth_user_data_save', $data);


		if(!empty($data['user_auth_key'])){
			$pass = base64_encode('PEXC' . $data['user_auth_key']);
			$data['user_auth_key'] = password_hash($pass, PASSWORD_BCRYPT);
		}

		$cfg_required = ['user_uid'=>'', 'user_login'=>'', 'user_email'=>'', 'user_type'=>1, 'user_fname'=>'', 'user_mname'=>'', 'user_lname'=>'', 'user_enabled'=>1,];
		

		foreach($cfg_required as $n => $v) {
			if(isset($data[$n])) continue;
			$data[$n] = $v;
		}

		if(empty($data['user_uid'])){
			if($action!= 'insert'){
				return false;
			}

			$data['user_uid'] = 'U' . time() . '-' . strtoupper(uniqid());
			$this->data['user_uid'] = $data['user_uid'];
			$this->uid = $data['user_uid'];
		}

		$ok = false;
		$db = manager::$db;
		if($action == 'update'){
			unset($data['id']);
			unset($data['user_uid']);
			$db->where('id', $id, 'user_uid', $this->uid);
			$ok = $db->update('exc_auth_users', $data);
		}elseif($action == 'insert'){
			$ok = $db->insert('exc_auth_users', $data);
			if($ok){			
				$this->dbID = $db->getInsertId();
				$this->data['id'] = $this->dbID;
			}
		}
	
		error_log('SQL=' . $db->debug_sql);
		if(!$ok){
			error_log('EXC|AUTH|ERROR|Unable to create user account');
			error_log('EXC|AUTH|ERROR|DB ERROR|' . $db->error() );
			return false;
		}

		$this->attributesSave();

		return true;
	}
	public function __get($name){
		if(!isset( $this->data[$name])) return null;
		return $this->data[$name];
	}
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
}
class role {
	public $data = [];
	public $permissions = [];
	public $attributes = [];
	private static $cache = [ 'roles'=>[], 'perms'=>[] ];
	
	public static function getRolesForUser($user){
		$roles = [];
		$user->roles = [];
		if(!is_object($user) || !($user instanceof user)) return null;

		 
		$db = manager::$db;
		$rs = $db->where('user_uid', $user->uid, 'user_site', $user->site)->get('exc_auth_user_roles');
		while($rs->read()){
			
			$ruid = $rs->fields['user_role_uid'];
			$date_expires = $rs->fields['user_role_expires_date'];

			error_log("got role=" . $ruid);
			$rdef = self::getRoleByUID($ruid);
			\exc\error_log_dump($rdef);

			if(is_null($rdef)){
				error_log('EXC|AUTH|ERROR|ROLE NOT DEFINED|UID|' . $ruid);
				continue;
			}

			$role = ['uid' => $ruid, 'enabled'=> true, 'expires_date'=> $date_expires, 'permissions'=>[], 'attributes'=>[]];
			if(!empty($date_expires)){
				if(strtotime($date_expires) < time()){
					$role['enabled'] = false;
				}
			}
	
			foreach($rdef->permissions as $tag => $permDef){
				$role['permissions'][$tag] = $permDef['name'];
			}

			$rs1 = $db->where('user_uid', $user->uid, 'role_uid', $ruid)->get('exc_auth_user_role_attributes');
			while($rs1->read()){
				$tag = $rs1->fields['tag'];
				$role['attributes'][$tag] = ['tag'=>$tag, 'value'=> $rs1->fields['value']];
			}

			foreach($rdef->attributes as $tag => $attrDef){
				if(!isset($role['attributes'][$tag])){
					$role['attributes'][$tag] = ['tag'=>$tag, 'value'=> $attrDef['default_value']];
				}
				
				$a = $role['attributes'][$tag];
				$a['name'] = $attrDef['name'];

				switch($attrDef['type']){
					case "integer":
						$a['value'] = 1 * $a['value'];
						break;
					case "float":
						$a['value'] = floatval($a['value']);
						break;
					case "bool":
						$a['value'] == (($a['value']=="true") || ($a['value']=="1") ) ? true : false;
						break;
				}

				$role['attributes'][$tag] = $a;
			}

			$roles[$ruid] = $role;
		}

		return $roles;
	}
	public static function getPermissionByUID($uid){
		if(array_key_exists($uid, self::$cache['perms'])) return self::$cache['perms'][$uid];

		$db = manager::$db;
		$rs = $db->where('uid', $uid)->get('exc_auth_def_perms');

		if(!$rs->read()) return null;

		self::$cache['perms'][$uid] = $rs->fields;
		return $rs->fields;
	}
	public static function getRoleByUID($uid){
		if(array_key_exists($uid, self::$cache['roles'])) return self::$cache['roles'][$uid];
		
		$db = manager::$db;
		$rs = $db->select('uid')->where('uid', $uid)->get('exc_auth_def_roles');
		if(!$rs->read()) return null;

		$role = new role($uid);
		return $role;
	}
	public function loadByUID($uid){

		$db = manager::$db;

		$rs = $db->where('uid', $uid)->get('exc_auth_def_roles');
		
		if(!$rs->read()) return false;

		$this->data = $rs->fields;

		$rs = $db->where('role_uid', $uid)->get('exc_auth_def_role_perms');
		while($rs->read()){
			$puid = $rs->fields['perm_uid'];
			$perm = self::getPermissionByUID($puid);
			if(empty($perm)) continue;
			$this->permissions[$puid] = $perm;
		}

		$rs = $db->where('role_uid', $uid)->get('exc_auth_def_role_attributes');
		while($rs->read()){
			$e = $rs->fields;
			$this->attributes[$e['tag']] =$e;
		}

		if(!array_key_exists($uid, self::$cache['roles'])) self::$cache['roles'][$uid] = $this;
		return true;
	}
	public function __construct($uid = null){
		if(!empty($uid)){
			$this->loadByUID($uid);
		}
	}
}
(function($app){
	error_log('Running my extension');

	//check compatibility
	if (!version_compare(EXC_VERSION, '1.0.0', '>=')) {
		error_log('EXC:ERROR:EXTENSION AUTH REQUIRES EXC VERSION 1.0.0 or greater');
		return;
	}
	
	

	manager::initialize();
})($app);