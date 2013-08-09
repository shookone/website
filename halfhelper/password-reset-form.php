<?php

require_once('boilerplate.php');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
	header('Location: ' . 'index.php');
	exit();
}

if (!isset($_GET['k'])) {
	header('Location: ' . 'password-reset.php');
	exit();
}

$user_row = Finder::getUserByResetKey(trim($_GET['k']));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		
		$post_user_row = Finder::getUserByResetKey($_POST['key']);

		if (!$post_user_row['id'] || $post_user_row['id'] != $user_row['id']) {
			throw new Exception('Unable to complete password reset. Please try again.');
		}
		
		foreach (array ('password', 'password-retype') as $key) {
			if (!strlen(trim($_POST[$key]))) {
				throw new Exception($key . 'was empty.');
			}
		}
		
		if (!validator::password($_POST['password'])) {
			throw new Exception('Invalid email password');
		}
		
		if ($_POST['password'] != $_POST['password-retype']) {
			throw new Exception('Passwords did not match.');
		}
		
		$salt = sha1(uniqid(mt_rand(), true));
		$password = sha1(md5($salt . $_POST['password']));
		
		//Save the new pw, salt, and set key to NULL
		$sql = 
			"UPDATE `halfhelper`.`users` SET `pwd` = '{$password}',
			`salt` = '{$salt}',
			`key` = NULL WHERE `users`.`id` ='{$post_user_row['id']}'";
		
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		
		// Need to add this as an alert to be displayed..
		$_SESSION['success'] = 'Your password has been changed. You can log in with it now.';
		header('Location: ' . 'login.php');
		exit();
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	header('Location: ' . basename($_SERVER['REQUEST_URI']));
	exit();
}

ob_start();
?>
<script type="text/javascript">
$(document).ready(function () {
	$('#password').focus();
});
</script>
<?php
$js = ob_get_clean();

ob_start();
?>
<div class="row">
	<div class="span8 offset2">
		<h1>Password Reset</h1>
		<hr>
		<?php
		if (!$user_row) {
			?>
			<p>The password reset link was invalid, possibly because it has already been used. Please <a href="password-reset.php">request a new password reset</a>.</p>
			<?php
		}
		else {
		$user_email = $user_row['email'];
			?>
			<p>Please enter your new password twice so we can verify you typed it in correctly.</p>
			
			<form action="" method="post" class="form-horizontal">
				<input type="hidden" name="key" id="key" value="<?php echo $_GET['k']; ?>">
				
				<div class="control-group">
					<label class="control-label" for="email">E-mail</label>
					<div class="controls">
						<span class="uneditable-input"><?php echo $user_email; ?></span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="password">New password</label>
					<div class="controls">
						<input tabindex="1" type="password" name="password" id="password" value="">
						<span class="help-block">Must be at least 5 characters long</span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="password-retype">Retype password</label>
					<div class="controls">
						<input tabindex="2" type="password" name="password-retype" id="password-retype" value="">
					</div>
				</div>
				
				<div class="form-actions">
					<button tabindex="3" type="submit" class="btn btn-inverse">Update Password</button>
				</div>
			</form>
			<?php
		}
		?>
	</div>
</div>
<?php
$body = ob_get_clean();

print wrap('Password Reset' , '', '', $body);