<?php

require_once('../boilerplate.php');
require_once('ebayKeys.php');
require_once('eBaySession.php');
require_once('EndItem.php');

if(!isset($_GET['i']) || !($_GET['i'])) {
	header('Location: ' . '../index.php');
	exit();
}
try{
	ob_start();
	$ebayKeys = ebayKeys::getInstance();
	$itemID = Finder::getItemID($_GET['i']);
	$token = Finder::getUserToken($itemID['item_id']);
	print '<pre>token' . print_r($token, true) . '</pre>';
	
	if(isExpired($token['token_expiration'])) {
		throw new Exception('Token is expired.');
	}
	if(!(strlen($token['token']) > 500)) {
		throw new Exception('Token is not valid.');
	}
	
	$_SESSION['token'] = $token['token'];
	$_SESSION['token_expiration'] = $token['token_expiration'];
	
	$endItem = new EndItem($ebayKeys, $_SESSION['token'], $itemID['item_id']);
	if($endItem->success == 1) {
		?>
		<h4>Your book was successfully unlisted from half.com.</h4>
		<p>Back to <a href="../user-lists.php">User Lists</a>.</p<>
		<?php
	}
	else{
		?>
		<h4>There was a problem unlisting your item. Please check <a href="http://www.half.com">Half.com</a> for more details.</h4>
		<?php
	}
}
catch(Exception $e) {
	$_SESSION['error'] = $e->getMessage();
}

$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

	/**
	 * @param string $token_expiration
	 */
	function isExpired($token_expiration) {
		$today = date('Y-m-d');
		$todayStr = strtotime($today);
		$tokenTime = strtotime($token_expiration);
		print $todayStr . 'today ' . ' token : ' . $tokenTime;
		if($todayStr < $tokenTime) {
			return false;
		}
		else {
			return true;
		}
	}