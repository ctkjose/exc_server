window.addEventListener("load", function(e){
	var u = location.pathname.split('/')
	var c = u.pop();
	u.push("backend.init");
	var url = u.join('/');

	//alert(url);
});