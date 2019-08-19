<form action='{{redirect_url}}' method='POST' name='frmredirect' id='frmr'>
	{{redirect_params}}
</form>
<script type='text/javascript'>
	 window.addEventListener("load", function(event) {
		{{redirect_js}}
		var frm = document.getElementById("frmr");
		frm.submit();
		console.log("sending form....");
	 });
</script>

<b>Redirecting, please wait!</b><br>
{{redirect_msg}}