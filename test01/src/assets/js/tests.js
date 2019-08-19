var kjose = {
	name: "jose",
};

console.log("here @tests");

function testBackend(){
	var a = exc.backend.action("@(main.testAction)");
	console.log(a);

	a.exec().then(function(data){
		console.log("Action Executed...");
		console.log(data);

	});
}
