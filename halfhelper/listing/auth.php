<?php

require_once('../boilerplate.php');
require_once('ebayKeys.php');
require_once('eBaySession.php');

$ebayKeys = ebayKeys::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		if(isset($_POST['remove'])) {
			$sql = "
				UPDATE `halfhelper`.`users` SET `token` = '',
				`token_expiration` = '' WHERE `users`.`id` ='" . mysql_real_escape_string($_SESSION['user_id']) . "';
			";
			$result = mysql_query($sql);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}
			$_SESSION['token'] = '';
			$_SESSION['token_expiration'] = '';
		}
		if(isset($_POST['list_id'])) {
			$_SESSION['list_id'] = $_POST['list_id'];
			header('Location: ' . '../view-lists.php?id=' . $_POST['list_id'] . '&auth=1');
			exit();
		}
	}
	catch(Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
}

if (!(isset($_SESSION['logged_in'])) || !($_SESSION['logged_in']) || !(isset($_SESSION['list_id']))) {
	header('Location: ' . '../login.php');
	exit();
}

if(isset($_SESSION['token']) && !isExpired($_SESSION['token_expiration'])){
	
	ob_start();
	?>
	<p>You already have a session saved with us.</p>
	<p>You may choose to continue or to remove your session from our records.</p>
	<form action="" method="post">
		<div class="control-group">
			<input type="hidden" name="remove" />
		</div>
		<div class="form-ations">
			<button tabindex="1" type="submit" class="btn">Remove Session</button>
		</div>
	</form>
	<form action="listBooks.php" method="POST">
		<input type="hidden" name="upload"/>
		<button type="submit" class="btn">List your books on Half.com</button>
	</form>
	<?php
	$body = ob_get_clean();
	$html = wrap('Halfhelper', '', '', $body);
	print $html;
}
else if (isset($_GET['continue'])) {
	ob_start();
	?>
	<a href="verify.php">Continue to verification &rarr;</a>
	<?php
	$body = ob_get_clean();
	$html = wrap('Halfhelper', '', '', $body);
	print $html;
}
else {
	$callName = 'GetSessionID';
	$ebay_sess = new eBaySession($ebayKeys, $callName);

	$request = '<?xml version="1.0" encoding="utf-8" ?>';
	ob_start();
	?>
	<GetSessionIDRequest xmlns="urn:ebay:apis:eBLBaseComponents">
		<Version><?=$ebayKeys->compatabilityLevel?></Version>
		<RuName><?=$ebayKeys->runame?></RuName>
	</GetSessionIDRequest>
	<?php
	$request .= ob_get_clean();

	$response = $ebay_sess->sendHttpRequest($request);

	if(stristr($response, 'HTTP 404') || $response == '') {
		die('Error sending request. Response: ' . $response);
	}

	$xml = simplexml_load_string($response);

	$_SESSION['ebSession'] = (string) $xml->SessionID;
	
	ob_start();
	?>
	<p>If you would like to list books for us to sell on half.com, we will need to authorize your ebay account information. The authorization is done through ebay and we will not store any of your account information.</p>
	<form action="" method="get">
		<input type="hidden" name="continue">
		<button type="submit" onclick="window.open('<?=$ebayKeys->loginUrl?>?SignIn&runame=<?=$ebayKeys->runame?>&SessID=<?=$_SESSION['ebSession']?>');">Launch Authorization</button>
	</form>
	<?php
	$body = ob_get_clean();
	$html = wrap('Halfhelper', '', '', $body);
	print $html;
}
	
	/**
	 * @param string $token_expiration
	 */
	function isExpired($token_expiration) {
		$today = date('Y-m-d');
		$todayStr = strtotime($today);
		$tokenTime = strtotime($token_expiration);
		if($todayStr < $tokenTime) {
			return false;
		}
		else {
			return true;
		}
	}
