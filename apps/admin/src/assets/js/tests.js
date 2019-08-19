var kjose = {
	name: "jose",
};

console.log("here @tests");

function testBackend(){
	var a = exc.backend.action("@(main.testAction)");
	console.log(a);

	a.params.value1 = "jose1";
	a.params.value2 = "jose2";

	a.exec().then(function(data){
		console.log("Action Executed...");
		console.log(data);

	});
}
