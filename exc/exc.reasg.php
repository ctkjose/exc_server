<?php
//Support Legacy REASG code
//EXC Bridge 1.0
namespace exc;

$__here = dirname(__FILE__) . '/';
require_once($__here . "exc.php");
require_once($__here . "exc.app.client.php");

class reasg_bootloader {
	const RUN_MODE_CLI = 2;
	const RUN_MODE_WEB = 1;
	private static $modules = [];
	public static $options = ['version'=>'1.0', 'version_name'=>'EXC0001.0', 'mode'=> self::RUN_MODE_WEB];
	public static function setOption($name, $value){
		\exc\bootloader::setOption($name, $value);
	}
	public static function run(){


		define('EXC_PATH', dirname(__DIR__) . '/');
		define('EXC_DIRECTORY', __DIR__ . '/');
		define('EXC_DOCUMENT_ROOT', (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '') . '/');

		self::setOption('mode', isset($_SERVER['SHELL']) ? self::RUN_MODE_CLI : self::RUN_MODE_WEB );
		self::setOption('path_exc', EXC_PATH);


		define('EXC_RUNMODE', self::$options['mode']);

		$a = [
			'uid'=>'EXCAPP',
			'path_app'=>'',
			'path_views'=> EXC_PATH . 'views/',
			'path_controller_main'=> null,
			'url_scope'=>'',
			'url_controller'=>'',
			'firstresponder'=> null,
			'controllers'=>[],
			'with_ui' => 0,
			'with_client' => 0,
			'paths'=>[
				'views'=>[]
			]
		];



		//error_log("@reasg_bootloader::run()================");
		global $config;
		global $rea_app_route;

		define('EXC_DIRECTORY_FOR_CONTROLLER', $config->application['attributes']['path']);

		$a['path_app'] = $config->application['attributes']['path'];
		$a['url_controller'] = $config->application['attributes']['action'];
		$a['uid'] = $config->application['uid'];

		//rea_dev_dump($config->application['attributes'], 'attributes');
		self::setOption('app', $a);

		if(isset($config->application['attributes']['exc']) && is_array($config->application['attributes']['exc']) ){
			foreach($config->application['attributes']['exc'] as $k => $v){
				\exc\bootloader::$options['app'][$k] = $v;
			}
		}

		//rea_dev_dump(\exc\bootloader::$options, '\exc\bootloader::$options');

		if(isset(\exc\bootloader::$options['app']['using']) && is_array(\exc\bootloader::$options['app']['using'])){
			foreach(\exc\bootloader::$options['app']['using'] as $k=>$params){
				\exc\bootloader::addModule($k,$params);
			}
		}

		\exc\controller\appController::init();
		$app = \exc\controller\appController::instance();

		$l = 'en';
		if(in_array($config->lng, [0,1]) ){
			$l = ($config->lng=='1') ? 'es': 'en';
		}

		$exc_config = ['uid'=>$config->application['uid'], 'directory'=> $config->application['attributes']['full_base_url'], 'url'=>$config->application['attributes']['url'] ,'lng'=>$l,'location'=> REA_LOCATIONID,  'action'=> $config->application['attributes']['action'], 'action_default'=> isset($config->application['attributes']['action_default']) ? $config->application['attributes']['action_default'] : '' ];
		$app->setAppState('config', $exc_config);

		//self::$app_state['config'] = ['uid'=>'', 'directory'=> '', 'url'=>'' ,'lng'=>'en','location'=> 'DEFAULT',  'action'=> '', 'action_default'=> '' ];
		//rea_dev_dump(\exc\controller\appController::$app_state);
		//\exc\router::instance()

		$client = \exc\client::instance($rea_app_route['values']);
		\rea_app::registerForEvent('rea_view_show', '\exc\reasg_bootloader::bridge_sendApplication');


		$app->publish("app_init", []);
	}
	public static function bridge_sendClient($params){
		//error_log("here at bridge_sendClient ");

	}
	public static function bridge_sendApplication($params){
		//error_log("here at bridge_sendApplication --------------------------------------");
		//rea_dev_dump($params);
		global $config;
		//$params[0]->pre($config);

		$page = $params[0];

		$client = \exc\client::instance();
		$js = "<script type='text/javascript'>\n//EXC 1.0, APP BOOTLOADER\n";

		$js.= 'exc.controller.on("module_loaded_' . \exc\client::$state['moduleName'] . '", function(msg){console.log("[EXC][REASG][VIEWS][MODULE LOADED][' . \exc\client::$state['moduleName'] . ']"); exc.views.install("' .  \exc\client::$state['moduleName'] . "\");});\n";

		$js.= "exc.app.state.load = true;\n";
		$js.= $client->getState();

		//$js.= 'if(!exc.views.find("' .  \exc\client::$state['moduleName'] . "\")){\n";
		//$js.= "}\n";

		//$js.= "exc.views.install('" .  \exc\client::$state['moduleName'] . "');\n";

		$js.= "</script>";
		$page->view->tpl_contents['exc-view-name'] = \exc\client::$state['moduleName'];
		$page->view->js_payload->write($js);

	}
}

//REA_BASE_PATH
class reasgBridge {
	use \exc\core\ObjectBase, \exc\core\ojectExtendable;

	public function sendClient(){
		global $config;

		if(in_array($config->lng, [0,1]) ){
			$l = ($config->lng=='1') ? 'es': 'en';
		}
		$reasg_app_config = ['uid'=>$config->application['uid'], 'directory'=> $config->application['attributes']['full_base_url'], 'url'=>$config->application['attributes']['url'] ,'lng'=>$l,'location'=> REA_LOCATIONID,  'action'=> $config->application['attributes']['action'], 'action_default'=> isset($config->application['attributes']['action_default']) ? $config->application['attributes']['action_default'] : '' ];
		\exc\controller\appController::setAppState('config', $reasg_app_config);

	}
}

//initialize the exc/bridge code
//$reasgBridge = new reasgBridge();
//$a = \exc\controller\appController::instance();
//$a->on("app_send_client", $reasgBridge->delegateFor("sendClient"));

if(class_exists('\rea_app')){
	\rea_app::registerForEvent('rea_app_model_app_start', '\exc\reasg_bootloader::run');
}
?>