<?php

require_once('../boilerplate.php');

class GetOrders {
	//public $token; // do i need this token?
	public $expiration;
	public $ebayKeys;
	public $listingStatus;
	public $saleID;
	
	/**
	 * @param ebayKeys object $ebayKeys
	 * @param string $ebSession
	 */
	function __construct($ebayKeys, $token, $book) {
		$this->ebayKeys = $ebayKeys;
		$ebay_sess = new eBaySession($this->ebayKeys, 'GetOrders');
		$this->saleID = NULL;
		$sql = " SELECT last_getordersrequest_at 
				FROM users
				WHERE id = '" . mysql_real_escape_string($book['user_id']) . "'";
		$result = mysql_query($sql) or die('Unable to query');
		$row = mysql_fetch_assoc($result);
		date_default_timezone_set('UTC');
		$currentDate = date('Y-m-d') . 'T' . date('H:i:s') . '.000Z';
		if(!$row['last_getordersrequest_at']) {
			$date = date('Y-m-d', strtotime('-1 week')) . 'T' . date('H:i:s', strtotime('-1 week')) . '.000Z';
		}
		else {
			$date = date('Y-m-d', strtotime($row['last_getordersrequest_at'])) . 'T' . date('H:i:s', strtotime($row['last_getordersrequest_at'])) . '.000Z';
		}
		$request = '<?xml version="1.0" encoding="utf-8"?>';
		ob_start();
		?>
		<GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
				<eBayAuthToken><?=$token?></eBayAuthToken>
			</RequesterCredentials>
			<Pagination>
				<EntriesPerPage>100</EntriesPerPage>
			</Pagination>
			<CreateTimeFrom><?=$date?></CreateTimeFrom>
			<CreateTimeTo><?=$currentDate?></CreateTimeTo>
			<ListingType>Half</ListingType>
			<OrderRole>Seller</OrderRole>
			<OrderStatus>All</OrderStatus>
		</GetOrdersRequest>
		<?php
		$request .= ob_get_clean();
		print '<pre>Get Orders Request' . print_r($request, true) . '</pre>';
		$response = $ebay_sess->sendHttpRequest($request);
		$xml = simplexml_load_string($response);
		if($xml->Ack = 'Success') {
			print '<pre>GetOrders' . print_r($xml, true) . '</pre>';
			foreach($xml->OrderArray->Order as $order) {
			if(!$order) {
				continue;
			}
				if($book['item_id'] == $order->TransactionArray->Transaction->Item->ItemID) {
					$sql = "
						UPDATE books_lists
						SET sale_id = '" . mysql_real_escape_string($order->TransactionArray->Transaction->TransactionID) . "'
						WHERE item_id = '" . mysql_real_escape_string($book['item_id']) . "'";
					$result = mysql_query($sql) or die('Query failed');
					$this->saleID = $xml->OrderArray->Order->TransactionArray->Transaction->TransactionID;
					break;
				}
			}
		}
	}
}
