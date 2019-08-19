<div class="view" name="loginView" data-controller="loginController">
<section class="form">
	<h1 class="form-title">Welcome, please login</h1>
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
		<div data-cmp='{"type":"formRow"}'>
			<div data-cmp='{"type": "btn", "name":"doFindRecord", "caption":"Find...", "color":"blue" }'></div>
		</div>
</section>
</div>