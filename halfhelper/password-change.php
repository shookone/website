<?php

require_once('boilerplate.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		if (isset($_POST['cancel'])) {
			header('Location: ' . get_proto() . BASE_URL_NO_PROTO . 'account/index.php');
			exit();
		}
		
		foreach (array ('old-password', 'password', 'password-retype') as $key) {
			if (!strlen(trim($_POST[$key]))) {
				throw new Exception($key . 'was empty.');
			}
		}
		
		$simple_user = new SimpleUser(Security::getUserId($db), $db);
		
		$old_password = Security::encryptPassword($_POST['old-password'], $simple_user->salt);
		
		if ($old_password != $simple_user->password) {
			FormHelper::addProblem('old-password', 'That password does not match your old password. Plesae try again.');
			throw new Exception();
		}
		
		if (!Validator::password($_POST['password'])) {
			FormHelper::addProblem('password', Validator::reason());
			throw new Exception();
		}
		
		if ($_POST['password'] != $_POST['password-retype']) {
			FormHelper::addProblem('password', 'The passwords you entered do not match. Please try again.');
			throw new Exception();
		}
		
		$simple_user->salt = Security::token();
		$simple_user->password = Security::encryptPassword($_POST['password'], $simple_user->salt);
		$simple_user->save();
		
		Alerts::add('Your password has been changed.', 'info', 'Success!');
		header('Location: ' . get_proto() . BASE_URL_NO_PROTO . 'account/index.php');
		exit();
	}
	catch (Exception $e) {
		if ($e->getMessage()) {
			Alerts::add($e->getMessage(), 'error', '', 'form');
		}
	}
	FormHelper::postRedirect();
}

ob_start();
?>
<script type="text/javascript">
$(document).ready(function () {
	$('#old-password').focus();
});
</script>
<?php
$js = ob_get_clean();

ob_start();
?>
<h2>Change Password</h2>
<p class="space-top10">Please enter your old password, for security, and then enter your new password twice so we can verify you typed it in correctly.</p>

<form action="" method="post" class="form-horizontal">
	<input type="hidden" name="token" id="token" value="<?php echo FormHelper::buildToken(); ?>">
	
	<?php echo Template::buildAlerts('form'); ?>
	
	<div class="control-group <?php FormHelper::echoIfProblem('old-password', 'error'); ?>">
		<label class="control-label" for="old-password">Old password</label>
		<div class="controls">
			<input tabindex="1" type="password" name="old-password" id="old-password" value="">
			<span class="help-inline"><?php echo FormHelper::getProblem('old-password'); ?></span>
		</div>
	</div>
	
	<div class="control-group <?php FormHelper::echoIfProblem('password', 'error'); ?>">
		<label class="control-label" for="password">New password</label>
		<div class="controls">
			<input tabindex="2" type="password" name="password" id="password" value="">
			<span class="help-inline"><?php echo FormHelper::getProblem('password'); ?></span>
			<span class="help-block">Must be at least 5 characters long</span>
		</div>
	</div>
	
	<div class="control-group <?php FormHelper::echoIfProblem('password-retype', 'error'); ?>">
		<label class="control-label" for="password-retype">Retype password</label>
		<div class="controls">
			<input tabindex="3" type="password" name="password-retype" id="password-retype" value="">
			<span class="help-inline"><?php echo FormHelper::getProblem('password-retype'); ?></span>
		</div>
	</div>
	
	<div class="form-actions">
		<button tabindex="4" type="submit" class="btn btn-primary" name="change">Change Password</button>
		<button tabindex="5" type="submit" class="btn" name="cancel">Cancel</button>
	</div>
</form>
<?php
$content = ob_get_clean();

print Template::makeAccount($db, array(
	'title' => 'Change Password',
	'content' => $content,
	'js' => $js,
	'active' => 'account/password-change.php'
));
