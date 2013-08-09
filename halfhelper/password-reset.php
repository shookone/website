<?php

require_once('boilerplate.php');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
	header('Location: ' . 'index.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		
		if (!validator::email($_POST['email'])) {
			throw new Exception('Invalid email address/password.');
		}
		
		$row = Finder::getUserByEmail($_POST['email']);
		if(!$row) {
			throw new Exception(Finder::$message);
		}
		
		$token = sha1(uniqid(mt_rand(), true));
		
		
		$sql = "UPDATE `halfhelper`.`users` SET `key` = '{$token}' WHERE `users`.`id` ='{$row['id']}'";
		// Update statement like in SimpleUser.php saveCol. Start there
		//$simple_user->saveCol('key', $token);
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		
		$url = BASE_URL . 'password-reset-form.php?k=' . $token;
		
		ob_start();
		?>
<p>You seem to have forgotten your password! No problem, follow the link below to reset your password:</p>
<p><a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
<p>If you didn't request to have your password reset, just ignore this e-mail and your password will remain the same.</p>
<p>Thanks!</p>
		<?php
		$message = ob_get_clean();
		
		$from = 'password-reset@' . 'localhost';
		$subject = 'Password Reset';
		
		$headers = "From: {$from}\r\n";
		$headers .= "Reply-To: {$from}\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		$headers .= "X-Mailer: PHP v" . phpversion() . "\r\n";
		
		mail($_POST['email'], $subject, $message, $headers);
		
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	header('Location: ' . basename($_SERVER['REQUEST_URI']) . '?d');
	exit();
}

ob_start();
?>
<script type="text/javascript">
$(document).ready(function () {
	$('#email').focus();
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
		if (isset($_GET['d'])) {
			?>
			<p>We've e-mailed you instructions for setting your password to the e-mail address you submitted. You should be receiving it shortly.</p>
			<?php
		}
		else {
			?>
			<p>Forgotten your password? Enter your e-mail address below, and we'll e-mail you instructions for setting a new one.</p>
			
			<form action="" method="post" class="form-horizontal">
				<input type="hidden" name="key" id="key" value="<?php $token ?>">

				
				<div class="control-group">
					<label class="control-label" for="email">E-mail</label>
					<div class="controls">
						<input tabindex="1" type="text" name="email" id="email" >
					</div>
				</div>
				
				<div class="form-actions">
					<button tabindex="2" type="submit" class="btn btn-inverse">Reset Password</button>
				</div>
			</form>
			<?php
		}
		?>
	</div>
</div>
<?php
$body = ob_get_clean();

print wrap('Password Reset', '', '', $body);