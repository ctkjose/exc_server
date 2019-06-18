<?php
namespace exc\ui {
	class manager extends \exc\core\base {
		public static $defaulView = null;
		public static function initialize($options){
			error_log("@exc/ui/manager/initialize()");
			//reasg_dev_dump($options, 'options');

			require_once(__DIR__ . "/exc.app.client.php");
			require_once(__DIR__ . "/exc.ui.views.php");
			require_once(__DIR__ . "/exc.ui.widgets.php");

			$app = \exc\controller\appController::instance();
			$client = \exc\client::instance(\exc\router::instance());

			\exc\options::key('app/with_ui', 10); //has UI
			\exc\options::key('app/with_client',1); //has client

		
			\exc\views\manager::initialize($options);

			//\exc\error_log_dump(\exc\options::$values['app']['paths']['views'], 'views');
			$app->publish("appUIAvailable", []);

		}
	}
}
?>