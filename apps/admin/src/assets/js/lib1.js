testModal = function(){
	var ops = {
		name: "testModal1",
		title: "My App",
		body: "Hello world",
		options: {
			closeButton: false,
		},
		buttons: [
			{"name":"testModal1Close", "caption":"Close", "color":"red", "closeOnClick":1, },
			{"name":"testModal1Save", "caption":"Save", "color": "blue", "closeOnClick":0, "publishOnClick":"saveDialog1"},
		]
	};

	var m = exc.views.modal.create(ops);
	m.show();
}