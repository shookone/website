<?php

require_once('boilerplate.php');

$_SESSION = array();
/* TODO ENABLE COOKIES
setcookie('SID');
setcookie('fingerprint');
*/

/* I don't know what this does
if (isset($_GET['r']) && sizeof($_GET['r'])) {
	header('Location: ' . get_proto() . BASE_URL_NO_PROTO . $_GET['r']);
	exit();
}
*/

ob_start();
?>
<div class="row">
	<div class="span8 offset2">
		<h1>Logged Out</h1>
		<hr>
		<p>You are now logged out. Thanks for visiting!</p>
		<p><a href="login.php">Log in</a></p>
	</div>
</div>
<?php
$body = ob_get_clean();

print wrap('Logout', '', '', $body);
