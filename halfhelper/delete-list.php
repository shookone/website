<?php

require('boilerplate.php');

if (!isset($_SESSION['logged_in']) || !isset($_GET['id'])) {
	header('Location: ' . 'login.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		$sql = " DELETE FROM `lists` WHERE `id` = '" . $_GET['id'] . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		$sql = " DELETE FROM `books_lists` WHERE `list_id` = '" . $_GET['id'] . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	header('Location: ' . 'user-lists.php?');
	exit();
}

ob_start();

$listName = Finder::getListName($_GET['id']);

?>


<form action="" method="post" class="well form-search">
	<p>Are you sure you want to delete <?php echo $listName['name']; ?>?</p>
	<input type="hidden" name="delete">
	<button type="submit" class="btn">Confirm</button>
	<a href="user-lists.php">Cancel</a>
</form>

<?php 
$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;
