<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>{{title}}</title>
	<meta name="description" content="EXC PAGE">
	<meta name="viewporte" content="width=device-width">
	<meta name="viewport" content="width=device-width, initial-scale=1">

<style type="text/css">
	body, html {
		box-sizing: border-box;
		padding: 0 0;
		margin: 0 0;
	 }
	.view {
		display: none;
	}
	.appls {
		box-sizing: border-box;
		position: absolute;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		background-color: #2C2C33;

		z-index: 99999;
		height: 100vh;
		width: 100vw;
		left: 0;
		top: 0;
	}
	.appls .spinner {
		height: 200px;
		width: 200px;
		animation: rotate 2s linear infinite;
		transform-origin: center center;
		position: absolute;
		top: 0;
		bottom: 0;
		left: 0;
		right: 0;
		margin: auto;
	}
	.appls .msg {
		position: absolute;
		bottom: 20px;
		text-align: center;
		color: #fff;
		font-family: sans-serif;
		font-size: 14px;
	}
	.appls .spinner .path {
		stroke-dasharray: 1, 200;
		stroke-dashoffset: 0;
		animation: dash 1.5s ease-in-out infinite;
		stroke-linecap: round;
		stroke: #fff;
	}
	.appls .logo {
		font-family: sans-serif;
		font-size: 16px;
		color: #fff;
	}
	@keyframes rotate {
		100% {
			transform: rotate(360deg);
		}
	}
	@keyframes dash {
		0% {
			stroke-dasharray: 1, 200;
			stroke-dashoffset: 0;
		}
		50% {
			stroke-dasharray: 89, 200;
			stroke-dashoffset: -35px;
		}
		100% {
			stroke-dasharray: 89, 200;
			stroke-dashoffset: -124px;
		}
	}
.exc-auth-container {

}
.exc-auth-login-view {
	display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
}
.exc-auth-login-form {
	width: 390px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    padding: 20px 20px;
    box-shadow: 0 5px 10px 0 rgba(0, 0, 0, .1);
    -moz-box-shadow: 0 5px 10px 0 rgba(0, 0, 0, .1);
    -webkit-box-shadow: 0 5px 10px 0 rgba(0, 0, 0, .1);
    -o-box-shadow: 0 5px 10px 0 rgba(0, 0, 0, .1);
    -ms-box-shadow: 0 5px 10px 0 rgba(0, 0, 0, .1);
}
.exc-auth-login-brand {
	padding-bottom: 10px;
}
.exc-auth-login-brand-title {
	display: block;
	font-size: 30px;
	color: #333;
	line-height: 1.2;
	text-align: center;
}
.exc-auth-login-brand-logo {
	display: block;
	text-align: center;
}
.exc-auth-login-btn {
	width: 100%;
}
.exc-auth-login-legal {
	display: block;
	padding: 12px 12px;
	font-size: .75em;
	line-height: 1.2em;
	color: #8E9093;
}
.exc-auth-login-msg {
	display: block;
	padding: 10px 12px;
	font-size: 1em;
}
.exc-auth-login-error {
	display: block;
	padding: 10px 12px;
	font-size: 1em;

	color: #CB3739;
}
</style>
<link rel="stylesheet" href="/exc/vendor/line-awesome/css/line-awesome.min.css">
<link rel="stylesheet" href="/exc_core/exc/css/exc.css">
{{ccs_includes}}
</head>
<body class=''>
<div class="appls" style=""><div class="logo"></div><svg class="spinner" viewBox="25 25 50 50"><circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"></circle></svg><div class="msg">Loading...</div></div>
<div class="flex with-rows j1">
		<div class="flex with-rows j2">
			<section class='flex with-rows panel grey'>
				<section class="role-app-contents exc-auth-container">
					<div class="view exc-auth-login-view is-closed" name="loginView" data-controller="loginController">
						<form class="exc-auth-login-form">
						{{#view login_brand}}
						{{message_html}}
						<section class="form">
							<div data-cmp='{"type":"formRow"}'>
								<div data-cmp='{"type": "formField", "name":"loginUserField", "caption":"Username"}'>
									<div data-cmp='{"type": "textbox", "name":"loginUser", "default":"", "placeholder": "Username", "suffix":"<i class=\"la la-user\"></i>"}'></div>
								</div>
							</div>
							<div data-cmp='{"type":"formRow"}'>
								<div data-cmp='{"type": "formField", "name":"loginPasswordField", "caption":"Password"}'>
									<div data-cmp='{"type": "password", "name":"loginPassword", "default":"", "placeholder": "Password", "suffix":"<i class=\"la la-key\"></i>"}'></div>
								</div>
							</div>
							<div class="span">Hello jose</div>
							<div data-cmp='{"type":"formRow"}'>
								<div data-cmp='btn' m-click="[loginController.onTest]" name="doLogin" class="exc-auth-login-btn blue">{{btn_login_caption}}</div>
							</div>
						</section>
						{{#view login_footer}}
						{{legal_html}}
						</form>
					</div>
				</section><!-- role-app-contents !-->
			</section>
		</div>
</div>
<script>
	var exc = exc || {};
</script>

<script type='text/javascript' src='/exc_core/exc/js/exc.dom.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.core.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.defaults.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.types.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.crypto.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.io.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.app.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.device.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.helpers.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.space.js'></script>

<script type='text/javascript' src='../exc/js/exc.ds.js'></script>
<script type='text/javascript' src='../exc/js/exc.model.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.expansions.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.expansions.default.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.component.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.views.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.modal.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.datepicker.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.lookup.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.validation.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.component.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.form.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.btn.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.toggle.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.check.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.textbox.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.select.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.date.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.component.table.js'></script>


{{#script asset://js/controller.app.js}}

<script></script>
{{js_includes}}
{{body_end}}
{{exc_js_scripts}}
{{exc_js_backend}}
</body>
</html>