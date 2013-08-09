<?php

require_once('boilerplate.php');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
	header('Location: ' . 'index.php');
	exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		
		
		$_POST['first_name'] = str_replace(array('<', '>'), '', $_POST['first_name']);
		$_POST['last_name'] = str_replace(array('<', '>'), '', $_POST['last_name']);
		
		foreach (array ('first_name', 'last_name', 'email', 'password', 'password-retype') as $key) {
			if (!strlen(trim($_POST[$key]))) {
				throw new Exception($key . 'was empty.');
			}
		}
		
		if(!validator::email($_POST['email'])) {
			throw new Exception('Invalid e-mail address.');
		}
		
		if(!validator::password($_POST['password'])) {
			throw new Exception(validator::$reason);
		}
		
		if ($_POST['password'] != $_POST['password-retype']) {
			throw new Exception('Passwords did not match.');
		}
		
		$salt = sha1(uniqid(mt_rand(), true));
		$password = sha1(md5($salt . $_POST['password']));
		
		$sql = "
			INSERT INTO `halfhelper`.`users` (
				`id` ,
				`email` ,
				`name` ,
				`pwd` ,
				`salt` ,
				`key`
			)
			VALUES (
				NULL ,
				'" . mysql_real_escape_string($_POST['email']) . "',
				'" . mysql_real_escape_string($_POST['first_name'] . ' ' . $_POST['last_name']) . "',
				'" . mysql_real_escape_string($password) . "',
				'" . mysql_real_escape_string($salt) . "',
				NULL
			);";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	header('Location: ' . 'index.php');
	exit();
}

ob_start();
?>
<!-- Puts cursor into first_name id -->
<script type="text/javascript">
$(document).ready(function () {
	$('#first_name').focus();
});
</script>
<?php
$js = ob_get_clean();

ob_start();
?>
<div class="row">
	<div class="span8 offset2">
		<h1>Register</h1>
		<hr>
		<form action="" method="post" class="form-horizontal">
			
			
			<div class="control-group">
				<label class="control-label" for="first_name">Name</label>
				<div class="controls">
					<input tabindex="1" class="input-small" type="text" name="first_name" id="first_name" placeholder="First">
					<input tabindex="2" class="input-small" type="text" name="last_name" id="last_name" placeholder="Last">
				</div>
			</div>
						
			<div class="control-group" >
				<label class="control-label" for="email">E-mail</label>
				<div class="controls">
					<input tabindex="3" type="text" name="email" id="email">
				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label" for="password">Password</label>
				<div class="controls">
					<input tabindex="4" type="password" name="password" id="password" value="">
					<p class="help-block"><a href="login.php">Already have an account?</a></p>

				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label" for="password-retype">Retype password</label>
				<div class="controls">
					<input tabindex="5" type="password" name="password-retype" id="password-retype" value="">
				</div>
			</div>
						
			<div class="form-actions">
				<button tabindex="6" type="submit" class="btn btn-inverse">Register</button>
			</div>
		</form>
	</div>
</div>
<?php
$body = ob_get_clean();

print wrap('Register', $js, '', $body);
