self.loginController = {
	initialize: function(){
		console.log("@loginController.initialize()");
	},
	viewWillEnterStage: function(msg){
		console.log("@loginController.viewWillEnterStage() %o", msg);
	},
	viewWillClose: function(msg){
		console.log("@loginController.viewWillClose() %o", msg);
	},
	viewShouldClose: function(msg){
		console.log("@loginController.viewShouldClose() %o", msg);
	},
	viewDidEnterStage: function(msg){
		console.log("@loginController.viewDidEnterStage() %o", msg);
	},
	viewBuild: function(msg){
		console.log("@loginController.viewBuild() %o", msg);
	},
	viewShow: function(msg){
		console.log("@loginController.viewShow() %o", msg);

		$.get("loginUser").focus();
	},
	viewReady: function(msg){
		console.log("@loginController.viewReady() %o", msg);
	},
	doLogin: function(msg){
		console.log("@loginController.doLogin_click() %o", msg);
		
		msg.event.preventDefault();
		msg.event.stopImmediatePropagation();

		var u = exc.components.get("loginUser");
		//if(
	}
}