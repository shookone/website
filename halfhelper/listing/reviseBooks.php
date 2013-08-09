<?php

require_once('../boilerplate.php');
require_once('ebayKeys.php');
require_once('eBaySession.php');
require_once('ReviseItem.php');
require_once('GetOrders.php');
//require_once('GetOrderTransactions.php');
//require_once('GetItemTransactions.php');

$booksToRevise = Finder::getAllListedBooks();
foreach($booksToRevise as $book) {
	try{
		$currentUser['user_id'] = $book['user_id'];
		if(!isset($lastUser['user_id']) || $lastUser['user_id'] != $currentUser['user_id']) {
			$ebayKeys = ebayKeys::getInstance();
			$token = Finder::getUserToken($book['item_id']);
			
			if(isExpired($token['token_expiration'])) {
				throw new Exception('Token is expired.');
			}
			if(!(strlen($token['token']) > 500)) {
				throw new Exception('Token is not valid.');
			}
		}
		$getOrders = new GetOrders($ebayKeys, $token['token'], $book);
		//$ids = array();
		//$ids = $getOrders->ids;
		//$getItemTransactions = new GetItemTransactions($ebayKeys, $_SESSION['token'], $ids);
		//$getOrderTransactions = new GetOrderTransactions($ebayKeys, $_SESSION['token'], $ids);
		if(!$book['sale_id'] || !$getOrders->saleID) {
			$reviseItem = new ReviseItem($ebayKeys, $token['token'], $book);
		}
		$lastUser = $book['item_id'];
	}
	catch(Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
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