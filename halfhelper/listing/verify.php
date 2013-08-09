<?php

require_once('../boilerplate.php');
require_once('ebayKeys.php');
require_once('eBaySession.php');
require_once('Token.php');
$ebayKeys = ebayKeys::getInstance();

$token = new Token($ebayKeys, $_SESSION['ebSession']);

if (!$token->isValid()) {
	ob_start();
	?>
	<h3>Oops! Something went wrong.</h3>
	<a href="auth.php">&larr; Retry authorization</a>
	<?php
}
else {
	header('Location: ' . 'listBooks.php');
	exit();
}
	$body = ob_get_clean();
	$html = wrap('Halfhelper', '', '', $body);
	print $html;
