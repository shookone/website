<?php

require_once('../boilerplate.php');

class AddItems {
	public $expiration;
	public $ebayKeys;
	
	public $conditions = array(
	"1" => "Brand_New",
	"2" => "Like_New",
	"3" => "Very_Good",
	"4" => "Good",
	"5" => "Acceptable"
	);
	
	/**
	 * @var array[string]string
	 */
	public $itemsToAdd;
	
	/**
	 * @param string $condition
	 */
	public function conditionToID($condition) {
		$i = 4;
		switch ($condition) {
			case "Brand New":
				$i = 1;
				break;
			case "Like New":
				$i = 2;
				break;
			case "Very Good":
				$i = 3;
				break;
			case "Good":
				$i = 4;
				break;
			case "Acceptable":
				$i = 5;
				break;
		}
		return $i;
	}
	
	public function convertToConditionID($condition) {
		$i = 5000;
		switch ($condition) {
			case 1:
				$i = 1000;
				break;
			case 2:
				$i = 3000;
				break;
			case 3:
				$i = 4000;
				break;
			case 4:
				$i = 5000;
				break;
			case 5:
				$i = 6000;
				break;
		}
		return $i;
	}
	
	/**
	 * @param ebayKeys object $ebayKeys
	 * @param Token object $token
	 */
	function __construct($ebayKeys, $token, $itemsToAdd) {
		$this->itemsToAdd = $itemsToAdd;
		$this->ebayKeys = $ebayKeys;
		$ebay_sess = new eBaySession($this->ebayKeys, 'AddItem');
		$itemIDs = array();
		$i = 0;
		$prices = array();
		foreach ($this->itemsToAdd as $isbn) {
			$request = '<?xml version="1.0" encoding="utf-8"?>';
			ob_start();
			?>
			<AddItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<?php
			$request .= ob_get_clean();
			$data = preg_split("/\s+|,|;/", $isbn);
			$itemInformation = array();
			$id = Finder::getBookID($data[0]);
			$itemInformation['id'] = $id['id'];
			$itemInformation['condition'] = $data[1];
			$itemInformation['correlation'] = $i;
			$itemIDs[$i] = $itemInformation;
			
			$priceData = Finder::getPriceData($data[0], $this->conditionToID($data[1]));
			foreach($priceData as $calls) {
				if($calls['feedback'] > 500) {
					$price = $calls['price'];
					break;
				}
			}
			
			ob_start();
			?>
					<Item>
						<AttributeArray>
							<Attribute attributeLabel="Condition">
								<Value>
									<ValueLiteral><?=$this->conditions[$data[1]]?></ValueLiteral>
								</Value>
						  </Attribute>
						</AttributeArray>
						<Site>US</Site>
						<Quantity>1</Quantity>
						<Description>Ships fast! Ships out same day or next.</Description>
						<StartPrice currencyID="USD"><?=$price?></StartPrice>
						<ListingDuration>GTC</ListingDuration>
						<Country>US</Country>
						<Currency>USD</Currency>
						<Location>Austin</Location>
						<ListingType>Half</ListingType>
						<DispatchTimeMax>3</DispatchTimeMax>
						<ExternalProductID>
							<Value><?=$data[0]?></Value>
							<Type>ISBN</Type>
						</ExternalProductID>
					</Item>
			<?php
			$prices[] = $price;
			$request .= ob_get_clean();
		 ob_start();
		 ?>
			<RequesterCredentials>
				<eBayAuthToken><?=$token?></eBayAuthToken>
			</RequesterCredentials>
			<WarningLevel>High</WarningLevel>
		</AddItemRequest>
		<?php
		$request .= ob_get_clean();
		$response = $ebay_sess->sendHttpRequest($request);

		$xml = simplexml_load_string($response);
			if($xml->Ack = 'Success') {
					$sql = "
						UPDATE `halfhelper`.`books_lists` 
						SET `price` = '" . mysql_real_escape_string($price) . "',
						`item_id` = '" . mysql_real_escape_string($xml->ItemID) . "'
						WHERE `books_lists`.`item_id` IS NULL
						AND `books_lists`.book_id = '" . mysql_real_escape_string($itemIDs[$i]['id']) . "'
						AND `books_lists`.condition_id = '" . mysql_real_escape_string($itemIDs[$i]['condition']) . "'
						LIMIT 1	";
						
					$result = mysql_query($sql);
					if (!$result) {
						die('Invalid query: ' . mysql_error());
					}
					$i++;
			}
		}
		if($i > 0) {
			$sql = " UPDATE `halfhelper`.`lists` 
				SET `is_listed` = '1' 
				WHERE `lists`.`id` = '" . mysql_real_escape_string($_SESSION['list_id']) . "'";
			$result = mysql_query($sql);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}
		}
	}
}
