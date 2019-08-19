//exc interaction
(function(d){
var w = function(){
	if(!core || exc.app.state.appStatus != 10){
		setTimeout(function(){ w(); }, 200);
		return;
	}
	
	var st = {{jsd}};

	console.log("EXC BACKEND INTERACTION RECEIVED");
	console.log(st);
	exc.app.loadInteraction(st);
};
w();
})(document);

var nd = document.getElementById("ecjs");
if(nd) nd.parentNode.removeChild(nd);