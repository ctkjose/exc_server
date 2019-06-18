{{app_state}}
(function(){
var bms = "{{bms}}";
exc.backend.getMS = function(){
	return bms;
};
})();
var nd = document.getElementById("excbl");
nd.parentNode.removeChild(nd);