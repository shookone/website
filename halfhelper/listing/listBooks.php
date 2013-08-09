<?php

// Put in half.com user/pw. Link this to halfhelper account. Maybe not, auth & Auth
// List these items on half.com and update them daily
// We update the prices daily, we check for sales
// We notify upon sale completion
// How do we set price? One penny cheaper than 500 feedbacks lowest price.
// If none with 500 feedback, one penny cheaper than lowest overall
// User gives conditions of book when they upload a book. Assume "Good"
require_once('../boilerplate.php');
require_once('ebayKeys.php');
require_once('eBaySession.php');
require_once('AddItems.php');

// require the user to be logged in
// select the user row from $_SESSION['user_id']
// verify the token expiration
// ? maybe verify token is valid

if (!(isset($_SESSION['logged_in'])) || !($_SESSION['logged_in']) || !(isset($_SESSION['list_id']))) {
	header('Location: ' . '../login.php');
	exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		if(isset($_POST['upload'])) {
			// take the list_id from the session and use it to build the list
			$list = Finder::getISBNCondition($_SESSION['list_id']);
			foreach ($list as $book) {
				$data[] = $book['isbn'];
				$data[] = $book['condition_id'];
			}
			$ebayKeys = ebayKeys::getInstance();
			// Loop through the isbns/conditions and make a call every
			// 5 times, b/c half.com has limit of adding 5 items at a time
			for($i = 0; $i < count($data); $i+=2) {
				if($i % 10 == 0 && $i != 0) {
					$addItems = new AddItems($ebayKeys, $_SESSION['token'], $itemsToAdd);
					$itemsToAdd = array();
				}
				$itemsToAdd[] = $data[$i] . ',' . $data[$i + 1];
			}
			if(count($itemsToAdd) != 0) {
				$addItems = new AddItems($ebayKeys, $_SESSION['token'], $itemsToAdd);
			}
		}
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	ob_start();
	?>
	<p>Your books have been listed on half.com.	We will notify you by email whenever anything sells.
	You may also cancel a listing at any time.</p>
	<?php
	$body = ob_get_clean();
	$html = wrap('Halfhelper', '', '', $body);
	print $html;
}
else {
	ob_start();
	?>
	<form method="POST">
		<input type="hidden" name="upload"/>
		<button type="submit" class="btn">List your books on Half.com</button>
	</form>
	<?php
	$body = ob_get_clean();

	// check to see if paypal email address is necessary for half.com!!!
	$html = wrap('Halfhelper', '', '', $body);
	print $html;
}
