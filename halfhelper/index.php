<?php

require('boilerplate.php');

if (!isset($_SESSION['logged_in'])) {
	header('Location: ' . 'login.php');
	exit();
}

ob_start();

?>


Hello <?php echo $_SESSION['name'] . "\n"; ?>
<?php 
$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;
