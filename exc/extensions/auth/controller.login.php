<?php
/*

*/
namespace ext\auth;

class loginController extends \exc\controller\viewController {
	
	public function initialize(){
	}
	public function handleAction($a){
		if($a == 'exc_auth_login'){
			$this->doActionLogin(['error_html'=>'an error']);
		}
	}
	
	public function validateLogin(){
		error_log("---   @loginController->validateLogin()");
		$cfg_auth_actions = ['exc_auth_login','exc_auth_logout', 'exc_auth_login_do'];

		$app = \exc\app::controller();
		
		$a = $app->currentAction;
		$flgIsAuthAction = in_array($a, $cfg_auth_actions);
		
		error_log("---   app action=" . $a);
		//exit;
		if($flgIsAuthAction){
			if($a == 'exc_auth_login'){
				$this->doActionLogin(['error_html'=>'an error']);
			}
		}

		if(!$this->loadLoginSession()){
			$app->publish('authLoginRequired', [$this]);
			if(manager::$authentication['status'] == EXC_AUTH_STATUS_PENDING){
				$this->redirectToLogin();
			}
		}

		
	}
	
	public function doActionSignOn(){
		
	}
	public function signOn($params=[]){
		$app = \exc\app::controller();

		if(!is_array($params) || !isset($params['user_login']) || !isset($params['user_password']) ){
			return null;
		}
		$auth_user_login = $params['user_login'];
		$auth_user_pass = $params['user_login'];

		
		if(manager::$authentication['status'] != EXC_AUTH_STATUS_PENDING){
			manager::authInvalidate(EXC_AUTH_STATUS_PENDING, '');
		}


		$user = manager::authenticate($auth_user_login, $auth_user_pass);
		if(empty($user)){
			$app->publish('auth_login_failed', ['user_login'=>$auth_user_login, 'user_password'=>$auth_user_pass]);
			return null;
		}

		$app->publish('auth_login_success', ['user'=>$user]);

		if(!$this->loginSessionCreate($user)){
			error_log('EXC|WARN|AUTH|AUTHENTICATION FAILED, UNABLE TO CREATE SESSION USER|' . $user->data['user_login'] . '|' . $user->uid );
			return null;
		}


		return $user;
	}
	public function doActionLogin($params=[]){
		if(manager::$authentication['status'] != EXC_AUTH_STATUS_PENDING){
			manager::authInvalidate(EXC_AUTH_STATUS_PENDING, '');
		}

		$app = \exc\app::controller();

		if($app->options->hasKey('app.renetry.url')){
			///save redirect
			$rentry_url = $app->options->key('app.renetry.url');
			$rentry_url = \exc\app::applyFilter('auth_login_set_rentry_url', $rentry_url);
			\exc\session::key('exc.auth.renetry.url', $rentry_url);
		}
		

		$ops = [
			'op_reveal_password'=> true,
			'message_html' => 'Nice message in one line',
			'footer_html' => 'This is a legal disclaimer',
			'legal_html' => 'By logging onto this system you consent and agree to the terms and conditions set by %app.company.name%.',
			'js_validate' => '',
			'js' => '',
			'btn_login_caption'=>'Login',
		];

		$ops = \exc\app::applyFilter('auth_login_set_options', $ops);


		$page = \exc\view::load('exc_login');
		if(isset($ops['js']) && !empty($ops['js'])){
			$page->js_includes->write($ops['js']);
		}
		if(isset($ops['btn_login_caption']) && !empty($ops['btn_login_caption'])){
			$page->btn_login_caption->set($ops['btn_login_caption']);
		}
		if(isset($ops['legal_html']) && !empty($ops['legal_html'])){
			$s = str_replace('%app.company.name%', $app->options->key('app.company.name'), $ops['legal_html']);
			$s = '<span class="exc-auth-login-legal">' . $s . '</span>';
			$page->legal_html->write($s);
		}
		if(isset($ops['message_html']) && !empty($ops['message_html'])){
			$s = $ops['message_html'];
			$s = '<span class="exc-auth-login-msg">' . $s . '</span>';
			$page->message_html->write($s);
		}

		if(is_array($params) && isset($params['error_html'])){
			$s = $params['error_html'];
			$s = '<span class="exc-auth-login-error">' . $s . '</span>';
			$page->message_html->write($s);
		}
		//$page->msg->write($vredirect);
	

		$app->publish('authLoginPageShow', [$this, $page]);
		$app->sendView($page);
	}
	public function redirectToLogin(){
		$app = \exc\app::controller();
		$app->sendRedirect('./','Login required', 'exc_auth_login', ['n'=>'jose'] );
	}
	public function loginSessionLoad(){
		error_log("---   @loginController->loginSessionLoad()");
		

		$app = \exc\app::controller();
		$sdata = null;
		if(\exc\session::hasKey('ext.auth.login_user')){
			$sdata = \exc\session::key('ext.auth.login_user');
		}

		$sdata = \exc\app::applyFilter('auth_login_fetch_session_data', $sdata);

		if( !is_array($sdata) || !isset($sdata['user_uid']) ){
			manager::authInvalidate(EXC_AUTH_STATUS_PENDING, '');
			return false;
		}

		return true;
	}
public function loginSessionCreate($user){
		error_log("---   @loginController->loginSessionCreate()");
		
		$app = \exc\app::controller();

		$sdata = [
			'user_uid' => $user->uid,
			'email' => $user->data['user_email'],
			'login' => $user->data['user_login'],
			'user_signin_type' => $user->data['user_login_type']
		];

		$sdata = \exc\app::applyFilter('auth_login_create_session_data', $sdata);

		if( !is_array($sdata) || !isset($sdata['user_uid']) ){
			return false;
		}

		\exc\session::key('ext.auth.login_user', $sdata);

		return true;
	}
}