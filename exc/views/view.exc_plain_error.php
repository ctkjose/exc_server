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
	
	.appls .logo {
		font-family: sans-serif;
		font-size: 16px;
		color: #fff;
	}

.svg-bounce {
	-webkit-animation: svg-bounce 2s;
	        animation: svg-bounce 2s;
	-webkit-animation-iteration-count: infinite;
	        animation-iteration-count: infinite;
}

@-webkit-keyframes svg-bounce {
	0%,
	25%,
	50%,
	75%,
	100% {
		-webkit-transform: translateY(0);
		        transform: translateY(0);
	}
	40% {
		-webkit-transform: translateY(-20px);
		        transform: translateY(-20px);
	}
	60% {
		-webkit-transform: translateY(-12px);
		        transform: translateY(-12px);
	}
}

@keyframes svg-bounce {
	0%,
	25%,
	50%,
	75%,
	100% {
		-webkit-transform: translateY(0);
		        transform: translateY(0);
	}
	40% {
		-webkit-transform: translateY(-20px);
		        transform: translateY(-20px);
	}
	60% {
		-webkit-transform: translateY(-12px);
		        transform: translateY(-12px);
	}
}

.msg-box {
	min-width: 160px;
	margin-top: 20px;
	padding: 20px 20px;
	background-color: #ffffff;
	border-radius: 6px;
	box-shadow: 5px 15px 30px 0px rgba(0,0,0,1);
	border: 1px solid #f9f9f9;
}

.msg-box .msg {
		text-align: center;
		color: #222222;
		font-family: sans-serif;
		font-size: 16px;
		font-weight: 400;
}
</style>
	<link rel="stylesheet" href="/exc/vendor/line-awesome/css/line-awesome.min.css">
	<link rel="stylesheet" href="/exc_core/exc/css/exc.css">
	

</head>
<body class=''>
<div class="appls" style="display: flex;">
<div class="logo" style="width: 128px; height: 128px;">
	<svg class="svg-bounce" viewBox="0 0 128 128">
		<defs>
			<linearGradient id="Gradient_1" gradientUnits="userSpaceOnUse" x1="-0" y1="64" x2="128" y2="64">
				<stop offset="0" stop-color="#F14D4C"/>
				<stop offset="1" stop-color="#C63536"/>
			</linearGradient>
		</defs>
		<path d="M1.066,108.664 L57.184,11.465 C60.213,6.218 67.787,6.218 70.816,11.465 L126.934,108.664 C129.963,113.911 126.176,120.47 120.118,120.47 L7.882,120.47 C1.824,120.47 -1.963,113.911 1.066,108.664 z" fill="url(#Gradient_1)"/>
		<path d="M71.3,91.366 C71.3,95.398 68.032,98.667 64,98.667 C59.968,98.667 56.7,95.398 56.7,91.366 C56.7,87.334 59.968,84.066 64,84.066 C68.032,84.066 71.3,87.334 71.3,91.366 z" fill="#FFFFFF"/>
		<path d="M64,38.135 L64,38.135 C60.051,38.135 56.928,41.478 57.196,45.418 L59.127,73.735 C59.302,76.297 61.432,78.287 64,78.287 L64,78.287 C66.569,78.287 68.698,76.297 68.873,73.735 L70.804,45.418 C71.072,41.479 67.949,38.135 64,38.135 z" fill="#FFFFFF"/>
	</svg>
</div>
<div class='msg-box'>
<div class="msg">{{msg}}</div>
</div>
</div>