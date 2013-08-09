<?php

require_once('boilerplate.php');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
	header('Location: ' . 'index.php');
	exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		
		foreach (array ('email', 'password') as $key) {
			if (!strlen(trim($_POST[$key]))) {
				throw new Exception($key . 'was empty.');
			}
		}
		
		if(!validator::email($_POST['email'])) {
			throw new Exception('Invalid email address/password');
		}
		
		if (!validator::password($_POST['password'])) {
			throw new Exception('Invalid email address/password');
		}
		
		// Insert mysql query to getUserIdByEmail and see if valid
		$row = Finder::getUserByEmail($_POST['email']);
		if(!$row) {
			throw new Exception(Finder::$message);
		}
		$password = sha1(md5($row['salt'] . $_POST['password']));
		
		if($password != $row['pwd']) {
			throw new Exception('Invalid password');
		}
		$_SESSION['user_id'] = $row['id'];
		$_SESSION['logged_in'] = true;
		$_SESSION['name'] = $row['name'];
		$_SESSION['token_expiration'] = $row['token_expiration'];
		$_SESSION['token'] = $row['token'];
		
		header('Location: ' . 'index.php');
		exit();
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	//Redirect to index.php if valid userid
	header('Location: ' . basename($_SERVER['REQUEST_URI']));
	exit();
}

ob_start();
?>
<script type="text/javascript">
$(document).ready(function () {
	if (!$('#email').val()) {
		$('#email').focus();
	}
	else {
		$('#password').focus();
	}
});
</script>
<?php
$js = ob_get_clean();

ob_start();
?>
<div class="row">
	<div class="span8 offset2">
		<h1>Log In</h1>
		<hr>
		<form action="" method="post" class="form-horizontal">
			
			<label class="control-label" for="email">E-mail</label>
			<div class="controls">
				<input tabindex="1" type="text" name="email" id="email">
				<p class="help-block"><a href="register.php">Don't have an account?</a></p>
			</div>
			
			<label class="control-label" for="password">Password</label>
			<div class="controls">
				<input tabindex="2" type="password" name="password" id="password">
				<p class="help-block"><a href="password-reset.php">Forgot your password?</a></p>
			</div>
			
			<div class="form-actions">
				<button tabindex="3" type="submit" class="btn btn-inverse">Log In</button>
			</div>
		</form>
	</div>
</div>
<?php
$body = ob_get_clean();

print wrap('Login', $js, '', $body);
