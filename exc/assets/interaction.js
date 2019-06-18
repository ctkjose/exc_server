(function(d){
var w = function(){
	if(!core || exc.app.state.appStatus != 10){
		setTimeout(function(){ w(); }, 200);
		return;
	}
	{{jsr}}

	{{js}}
	if(!st || st._bst == []._) return;
//	console.log("EXC BACKEND INTERACTION RECEIVED");
//	console.log(st);
	exc.app.loadInteraction(st);
};
w();
})(document);
{{payload}}