<?php

class Token {
    public $token;
    public $expiration;
	public $ebayKeys;
	
	/**
	 * @param ebayKeys object $ebayKeys
	 * @param string $ebSession
	 */
	function __construct($ebayKeys, $ebSession) {
		$this->ebayKeys = $ebayKeys;
		$ebay_sess = new eBaySession($this->ebayKeys, 'FetchToken');

		$request = '<?xml version="1.0" encoding="utf-8" ?>';
		ob_start();
		?>
		<FetchTokenRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<SessionID><?=$ebSession?></SessionID>
        </FetchTokenRequest>
		<?php
		$request .= ob_get_clean();

        $response = $ebay_sess->sendHttpRequest($request);

        if(stristr($response, 'HTTP 404') || $response == '') {
			die('Error sending request. Response: ' . $response);
		}

        $xml = simplexml_load_string($response);
		
        $this->token = (string) $xml->eBayAuthToken;
        $this->expiration = (string) $xml->HardExpirationTime;
		
		$_SESSION['token'] = (string) $xml->eBayAuthToken;
		$_SESSION['token_expiration'] = (string) $xml->HardExpirationTime;
		$sql = "
		UPDATE `halfhelper`.`users` SET `token` = '" . mysql_real_escape_string($this->token) . "', `token_expiration` = '" . mysql_real_escape_string($this->expiration) . "' WHERE `users`.`id` = '" . mysql_real_escape_string($_SESSION['user_id']) . "';
		";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
    }
	
	/**
	 * @return bool
	 */
	public function isValid() {
		return strlen($this->token) > 500;
	}
}
