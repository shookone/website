<?php

class eBaySession {
	/**
	 * @var ebayKeys object
	 */
	private $ebayKeys;
	/**
	 * @var string
	 */
	private $callName;

	/**
	 * @param ebayKeys object $ebayKeys
	 * @param string $callName
	 */
    public function __construct($ebayKeys, $callName) {
		$this->ebayKeys = $ebayKeys;
		$this->callName = $callName;
	}
	
	public function sendHttpRequest($request) {
		$connection = curl_init();
		curl_setopt($connection, CURLOPT_URL, $this->ebayKeys->serverUrl);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($connection, CURLOPT_HTTPHEADER, $this->buildEbayHeaders());
		curl_setopt($connection, CURLOPT_POST, 1);
		curl_setopt($connection, CURLOPT_POSTFIELDS, $request);
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($connection);
		curl_close($connection);
		return $response;
	}

	/**
	 * Generates an array of string to be used as the headers for the HTTP request to eBay
	 * @return array of strings that represent headers
	 */
	private function buildEbayHeaders() {
		return array (
			'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->ebayKeys->compatabilityLevel,
			'X-EBAY-API-DEV-NAME: ' . $this->ebayKeys->devID,
			'X-EBAY-API-APP-NAME: ' . $this->ebayKeys->appID,
			'X-EBAY-API-CERT-NAME: ' . $this->ebayKeys->certID,
			'X-EBAY-API-CALL-NAME: ' . $this->callName,
			'X-EBAY-API-SITEID: ' . $this->ebayKeys->siteID,
		);
	}
}
