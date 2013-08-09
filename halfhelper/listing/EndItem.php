<?php

require_once('../boilerplate.php');

class EndItem {
	public $expiration;
	public $ebayKeys;
	public $success;
	/**
	 * @param ebayKeys object $ebayKeys
	 * @param string $ebSession
	 */
	function __construct($ebayKeys, $token, $itemID) {
		$this->success = 0;
		$this->ebayKeys = $ebayKeys;
		$ebay_sess = new eBaySession($this->ebayKeys, 'EndItem');
		
		$request = '<?xml version="1.0" encoding="utf-8"?>';
		ob_start();
		?>
		<EndItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
			<eBayAuthToken><?=$token?></eBayAuthToken>
			</RequesterCredentials>
			<ItemID><?=$itemID?></ItemID>
			<EndingReason>NotAvailable</EndingReason>
		</EndItemRequest>
		<?php
		$request .= ob_get_clean();
		print '<pre>request' . print_r($request, true) . '</pre>';
		$response = $ebay_sess->sendHttpRequest($request);
		
		$xml = simplexml_load_string($response);
		if($xml->Ack = 'Success') {
		$this->success = 1;
			$sql = " DELETE 
					FROM books_lists
					WHERE item_id = '" . mysql_real_escape_string($itemID) . "'	";
			$result = mysql_query($sql) or die('Invalid query: ' . mysql_error()) ;
		}
	}
}
