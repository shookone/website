<?php

require_once('../boilerplate.php');

class ReviseItem {
	//public $token; // do i need this token?
	public $expiration;
	public $ebayKeys;
	
	/**
	 * @param ebayKeys object $ebayKeys
	 * @param string $ebSession
	 */
	function __construct($ebayKeys, $token, $book) {
		$price = 0;
		$priceData = Finder::getPriceData($book['isbn'], $book['condition_id']);
		foreach($priceData as $calls) {
			if($calls['feedback'] > 500) {
				$price = $calls['price'];
				break;
			}
		}
		if(!$price) {
			$price = 1000000;
		}
		$this->ebayKeys = $ebayKeys;
		$ebay_sess = new eBaySession($this->ebayKeys, 'ReviseItem');
		
		$request = '<?xml version="1.0" encoding="utf-8"?>';
		ob_start();
		?>
		<ReviseItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<ErrorLanguage>en_US</ErrorLanguage>
			<WarningLevel>High</WarningLevel>
			<Item>
				<ItemID><?=$book['item_id']?></ItemID>
				<StartPrice><?=$price?></StartPrice>
			</Item>
			<RequesterCredentials>
			<eBayAuthToken><?=$token?></eBayAuthToken>
			</RequesterCredentials>
		</ReviseItemRequest>
		<?php
		$request .= ob_get_clean();
		print '<pre>request' . print_r($request, true) . '</pre>';
		//die('Revise Item was made');
		$response = $ebay_sess->sendHttpRequest($request);
		
		$xml = simplexml_load_string($response);
		if($xml->Ack = 'Success') {
			$sql = "
				UPDATE `halfhelper`.`books_lists` 
				SET `price` = '" . mysql_real_escape_string($price) . "'
				WHERE `books_lists`.`item_id` = '" . mysql_real_escape_string($xml->ItemID) . "'";
			$result = mysql_query($sql);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}
			print '<pre>response' . print_r($xml, true) . '</pre>';
		}
	}
}
