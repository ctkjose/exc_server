//exc backend bridge
(function(){
	var {{$sk}} = "{{sk}}";
	var {{$bms}} = "{{bms}}";
	var {{$uid}} = "{{uid}}";
	exc.backend.getMS = function(){
		return {{$bms}};
	};
	exc.backend.getUID = function(){
		return {{$uid}};
	};
	
	var {{$v1}} = function(ks){
		if(!ks || (ks != exc.crypto.SHA256HMAC({{$sk}}, {{$bms}})) ){
			console.log("[EXC][BACKEND][ERROR] UNABLE TO VALIDATE ORIGIN.");
			return false;
		}
		return true;
	}
	var {{$s1}} = function(r, response){
		if(response.lastError){
			console.log("failed here 1");
			exc.backend.requestFailed(r);
			return;
		}

		if(response.headers['CONTENT-TYPE-MIME'] != "text/json"){
			console.log("failed here 2");
			exc.backend.requestFailed(r);
			return;
		}

		var ks = null;
		
		var data = null;
		
		data = response.data;

		if(!data) {
			console.log("[EXC][BACKEND][INTERACTION] NO DATA RETURNED.");
			exc.backend.requestFailed(r);
			return;
		}
	
		console.log(data);
		if(typeof(data) !== "object"){
			console.log("[EXC][BACKEND][INTERACTION] EXPECTING OBJECT IN RESPONSE.");
			exc.backend.requestFailed(r);
			return;
		}

		if(!data.hasOwnProperty('status') || (data.status != 200)){
			console.log("[EXC][BACKEND][INTERACTION] INVALID STATUS IN RESPONSE.");
			exc.backend.requestFailed(r);
			return;
		}

		//if(!{{$v1}}(ks)){
		//	console.log("[EXC][BACKEND][ERROR] UNABLE TO VALIDATE ORIGIN.");
		//	p.reject();
		//}
		var bd = {};
		if(data.hasOwnProperty("payload")){
			if(data.payload.hasOwnProperty("response")){
				if(data.payload.response.hasOwnProperty("data")){
					bd = data.payload.response.data;
				}

				delete data.payload.response;
			}

			exc.app.processInteraction(data);
		}
	
		exc.backend.requestCompleted(r, bd);
	};
	exc.backend.handleResponse = function(r, response){
		{{$s1}}(r, response);
	};
	exc.backend.ready = true;
})();

var nd = document.getElementById("excbl");
if(nd) nd.parentNode.removeChild(nd);