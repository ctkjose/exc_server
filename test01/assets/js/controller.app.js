self.app = {
	opSessionName: "myAddData",
	manifest: {
		require: [
			{controller: "loginController", url: "../a/js/controller.login.js", wait: true},
		]
	},
	initialize: function(){
		//called to initialize this controller instance
		console.log("@app.initialize()");

		//During initialization be careful when making assumptions about what other things are loaded and available.
		//Only guarantee is that this object and its related scope is ready.
	},
	onAppStart: function(){
		//called to start an application
		console.log("@app.onAppStart()");
		return true; //return false to cancel application run
	},
	onLoadFailed: function(){
		//called if loading of app failed
		alert("Sorry unable to load this application. Please resfresh or try later.");
	},
	onTest: function(){
		console.log("@app.onTest1()");
		console.log(arguments);
	},
	onDoRecordEditDone: function(){
		console.log("@app.onDoRecordEditDone()");
		exc.app.stage.closeCurrent();
	},
	onStageReady: function(){
		console.log("@app.onStageReady()");
		exc.stage.show("loginView");
	},
	loginUser_change: function(msg){
		console.log("@app.loginUser_change() %o", msg);
	},
};


function testLogin(){
	exc.app.stage.show("loginView");

}