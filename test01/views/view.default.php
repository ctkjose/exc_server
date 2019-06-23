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
</style>
<link rel="stylesheet" href="/exc/vendor/line-awesome/css/line-awesome.min.css">
<link rel="stylesheet" href="/exc_core/exc/css/exc.css">
{{ccs_includes}}
</head>
<body class=''>
<div class="appls" style=""><div class="logo"></div><svg class="spinner" viewBox="25 25 50 50"><circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"></circle></svg><div class="msg">Loading...</div></div>
<div class="flex with-rows j1">
		<div class="flex with-columns j2">
			<section class="role-app-sidebar panel dark with-shadow">
				<div class="menu dark is-vertical with-background" name="appMenu">
					<div class="menu-header">A Header</div>
					<a class="menu-item is-active" href="#">Vert 1</a>
					<a class="menu-item" href="#">Vert 2</a>
					<div class="menu-divider"></div>
					<a class="menu-item" href="#">Vert 3</a>
					<div class="menu-group-caption is-expanded" href="#">Group Example</div>
					<div class="menu-group is-expanded" name="mnuGroup1">
						<a class="menu-item" href="#"><i class='la la-pencil'></i> Example 1</a>
						<a class="menu-item" href="#">Example 2 <span class="pill green">New</span></a>
						<a class="menu-item" href="#">Example 3</a>
					</div>
					<a class="menu-item" href="#">More stuff...</a>
					<div class="menu-header">Account</div>
					<a class="menu-item" href="#"><i class='la la-cog'></i> Settings</a>
					<div class="menu-spacer"></div>
					<a class="menu-item is-special" href="#"><i class='la la-bomb'></i>About</a>
				</div>
			</section>
			<section class='flex with-rows panel grey'>
				<header class="role-app-bar mute-divider-bottom flex with-columns p-4 p-sm-1 p-md-2" role="navigation">
					<a class="brand" href="#">EXC</a>
					<div class='spacer'></div>
					<a href="#" class="btn is-inset">
						Options
					</a>
					<a href="#" class="btn green is-outline with-pill-badge">
						<i class="la la-bell"></i>
						<span class="pill red">25</span>
					</a>
					<a href="#" class="btn is-outline role-app-sidebar-hamburger"><i class="la la-navicon la-2x"></i></a>
				</header>
				<section class="role-app-contents">
					
							
				</section>
			
			</section>
		</div>
</div>

{{#view login}}
<script>
	var exc = exc || {};
</script>

<script type='text/javascript' src='/exc_core/exc/js/exc.dom.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.core.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.defaults.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.types.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.io.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.app.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.device.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.helpers.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.space.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.ds.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.expansions.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.expansions.default.js'></script>

<script type='text/javascript' src='/exc_core/exc/js/exc.component.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.views.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.modal.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.datepicker.js'></script>
<script type='text/javascript' src='/exc_core/exc/js/exc.lookup.js'></script>

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

</body>
</html>