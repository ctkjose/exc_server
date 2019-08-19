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
.exc-brand {
	position: absolute;
	left: 20px;
	bottom: 20px;

	padding: 8px 8px;
	border-radius: 4px 4px;
}
</style>
	<link rel="stylesheet" href="/exc/vendor/line-awesome/css/line-awesome.min.css">
	<link rel="stylesheet" href="/exc_core/exc/css/exc.css">
	

</head>
<body class='' style='height: 100vh;'>
<div class="appls" style="display: flex;">
<div class="logo" style="width: 128px; height: 128px;">
	<svg class="svg-bounce" viewBox="0 0 128 128">
		<path d="M116,0 L12,0 C5.4,0 0,5.4 0,12 L0,84 C0,90.6 5.4,96 12,96 L32,96 L32,128 L70.4,96 L116,96 C122.6,96 128,90.6 128,84 L128,12 C128,5.4 122.6,0 116,0 z" fill="#00CAD3"/>
    <path d="M71.3,70.2 C71.3,74.232 68.032,77.5 64,77.5 C59.968,77.5 56.7,74.232 56.7,70.2 C56.7,66.168 59.968,62.899 64,62.899 C68.032,62.899 71.3,66.168 71.3,70.2 z" fill="#FFFFFF" id="exclamation2"/>
    <path d="M64,16.968 L64,16.968 C60.051,16.968 56.928,20.312 57.196,24.251 L59.127,52.568 C59.302,55.13 61.432,57.12 64,57.12 L64,57.12 C66.569,57.12 68.698,55.131 68.873,52.568 L70.804,24.251 C71.072,20.312 67.949,16.968 64,16.968 z" fill="#FFFFFF" id="exclamation1"/>
	</svg>
</div>
<div class='msg-box'>
<div class="msg">{{msg}}</div>
</div>


<div class='exc-brand'>
<svg viewBox="0, 0, 128, 128" style="width: 56px; height: 56px;" title="EXPRESSCODE LOGO">
	<defs>
	<linearGradient id="Gradient_1" gradientUnits="userSpaceOnUse" x1="-140.894" y1="18.385" x2="-76.894" y2="18.385" gradientTransform="matrix(-0.707, -0.707, 0.707, -0.707, 0, 0)">
	<stop offset="0.005" stop-color="#06DBC4"/>
	<stop offset="0.662" stop-color="#4846BD"/>
	</linearGradient>
	<linearGradient id="Gradient_2" gradientUnits="userSpaceOnUse" x1="-106.285" y1="49.899" x2="-97.588" y2="-13.129" gradientTransform="matrix(-0.707, -0.707, 0.707, -0.707, 0, 0)">
	<stop offset="0.187" stop-color="#0DFD96"/>
	<stop offset="0.823" stop-color="#1EA2D7"/>
	</linearGradient>
	<linearGradient id="Gradient_3" gradientUnits="userSpaceOnUse" x1="-47.387" y1="-85.649" x2="10.617" y2="-58.601" gradientTransform="matrix(0.707, -0.707, -0.707, -0.707, 0, 0)">
	<stop offset="0.274" stop-color="#FEC137"/>
	<stop offset="1" stop-color="#E52962"/>
	</linearGradient>
	<linearGradient id="Gradient_4" gradientUnits="userSpaceOnUse" x1="-29.135" y1="-94.752" x2="6.28" y2="-49.498" gradientTransform="matrix(0.707, -0.707, -0.707, -0.707, 0, 0)">
	<stop offset="0.23" stop-color="#FAC163"/>
	<stop offset="0.443" stop-color="#F7A35B"/>
	<stop offset="1" stop-color="#EF5445"/>
	</linearGradient>
	</defs>
	<g id="icon">
      <path d="M90,64 L101.314,75.314 L102.412,76.529 C107.457,83.29 106.935,91.722 101.314,97.941 C95.065,104.19 84.935,104.19 78.686,97.941 L56.059,75.314 C49.81,69.065 49.81,58.935 56.059,52.686 L56.06,52.685 L78.685,30.06 L78.686,30.059 C84.935,23.81 95.065,23.81 101.314,30.059 C107.562,36.307 107.562,46.438 101.314,52.686 L90,64 z" fill="url(#Gradient_1)"/>
      <g>
        <path d="M78.686,97.941 L78.685,97.94 L73.025,92.28 C57.883,76.308 58.004,51.613 73.027,35.719 L44.745,64 L44.745,64 L78.686,30.059 C84.935,23.81 95.065,23.81 101.314,30.059 C107.562,36.307 107.562,46.438 101.314,52.686 C98.666,55.557 95.743,58.416 92.828,61.172 C85.043,69.44 74.954,83.176 78.454,95.42 C79.036,96.677 79.618,97.935 80.2,99.192 C80.228,99.226 80.256,99.259 80.283,99.292 C79.446,98.738 80.015,99.142 78.686,97.941 L78.686,97.941 z" fill="url(#Gradient_2)"/>
        <path d="M91.534,25.446 C95.348,25.82 98.515,27.529 101.314,30.059 C107.562,36.307 107.562,46.438 101.314,52.686 C98.666,55.557 95.743,58.416 92.828,61.172 C85.043,69.44 74.954,83.176 78.454,95.42 C79.036,96.677 79.618,97.935 80.2,99.192 C80.228,99.226 80.256,99.259 80.283,99.292 C79.446,98.738 80.015,99.142 78.686,97.941 L78.686,97.941 L78.685,97.94 L73.025,92.28 C57.883,76.308 58.004,51.613 73.027,35.719 L44.745,64 L44.745,64 L78.686,30.059 C81.344,27.466 84.753,25.803 88.466,25.446 C89.484,25.348 90.511,25.446 91.534,25.446 z M89.73,26.396 C85.71,26.528 82.418,28.253 79.393,30.766 L73.751,36.409 C58.968,52.447 59.201,75.484 73.732,91.573 L77.385,95.227 C74.314,81.846 83.675,69.707 92.141,60.445 C95.524,57.247 98.213,54.573 100.607,51.979 C106.464,46.121 106.464,36.624 100.607,30.766 C97.756,27.937 95.243,27.179 91.438,26.441 L89.73,26.396 z" fill="#76DCDD"/>
      </g>
      <path d="M38,64 L26.686,75.314 L25.588,76.529 C20.543,83.29 21.065,91.722 26.686,97.941 C32.935,104.19 43.065,104.19 49.314,97.941 L71.941,75.314 C78.19,69.065 78.19,58.935 71.941,52.686 L71.94,52.685 L49.315,30.06 L49.314,30.059 C43.065,23.81 32.935,23.81 26.686,30.059 C20.438,36.307 20.438,46.438 26.686,52.686 L38,64 z" fill="url(#Gradient_3)"/>
      <g>
        <path d="M49.314,97.941 L49.315,97.94 L54.975,92.28 C70.117,76.308 69.996,51.613 54.973,35.719 L83.255,64 L83.255,64 L49.314,30.059 C43.065,23.81 32.935,23.81 26.686,30.059 C20.438,36.307 20.438,46.438 26.686,52.686 C29.334,55.557 32.257,58.416 35.172,61.172 C42.957,69.44 53.046,83.176 49.546,95.42 C48.964,96.677 48.382,97.935 47.8,99.192 C47.772,99.226 47.744,99.259 47.717,99.292 C48.554,98.738 47.985,99.142 49.314,97.941 L49.314,97.941 z" fill="url(#Gradient_4)"/>
        <path d="M39.534,25.446 C43.348,25.821 46.515,27.529 49.314,30.059 L83.255,64 L83.255,64 L54.973,35.719 C69.996,51.613 70.117,76.308 54.975,92.28 L49.315,97.94 L49.314,97.941 L49.314,97.941 C47.985,99.142 48.554,98.738 47.716,99.292 C47.744,99.259 47.772,99.226 47.8,99.192 C48.382,97.935 48.964,96.677 49.546,95.42 C53.046,83.176 42.957,69.44 35.172,61.172 C32.257,58.416 29.334,55.557 26.686,52.686 C20.438,46.438 20.438,36.307 26.686,30.059 C29.344,27.466 32.753,25.803 36.466,25.446 C37.484,25.348 38.511,25.446 39.534,25.446 z M37.73,26.396 C33.71,26.528 30.418,28.253 27.393,30.766 C21.536,36.624 21.536,46.121 27.421,52.008 C29.787,54.573 32.476,57.247 35.9,60.486 C44.358,69.727 53.683,81.873 50.616,95.225 L54.249,91.592 C69.077,75.554 68.753,52.513 54.249,36.409 L48.607,30.766 C45.756,27.937 43.244,27.179 39.438,26.441 L37.73,26.396 z" fill="#FDD082"/>
      </g>
    </g>
</svg>
</div>
</div>
</body>
</html>