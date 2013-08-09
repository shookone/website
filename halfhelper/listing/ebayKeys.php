<?php

class ebayKeys {
	public $findingVersion;
	public $compatabilityLevel;
	/** 
	 * SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
	 * @var int
	 */
	public $siteID;
	public $devID;
	public $appID;
	public $certID;
	public $serverUrl;
	public $shoppingUrl;
	public $findingUrl;
	public $loginUrl;
	public $feedbackUrl;
	public $appToken;

	public static $instance = null;
	
	public static function getInstance() {
		if(!isset(self::$instance)) {
			self::$instance = new ebayKeys();
		}
		return self::$instance;
	}
	
	private function __construct() {
		$this->findingVersion = '1.8.0';
		$this->compatabilityLevel = 681;
		$this->siteID = 0;
		$ini = parse_ini_file("ebayKeys.ini");
		$this->devID = $ini['devID'];
		$this->appID= $ini['appID'];
		$this->certID = $ini['certID'];
		$this->runame= $ini['runame'];
		$this->appToken = $ini['appToken'];
		$this->isSandbox= $ini['isSandbox'];
		if($this->isSandbox) {
			$this->serverUrl = 'https://api.sandbox.ebay.com/ws/api.dll';
			$this->shoppingUrl = 'http://open.api.sandbox.ebay.com/shopping';
			$this->findingUrl= 'http://svcs.sandbox.ebay.com/services/search/FindingService/v1';
			$this->loginUrl = 'https://signin.sandbox.ebay.com/ws/eBayISAPI.dll';
			$this->feedbackUrl = 'http://feedback.sandbox.ebay.com/ws/eBayISAPI.dll';
		}
		else {
			$this->serverUrl = 'https://api.ebay.com/ws/api.dll';
			$this->shoppingUrl = 'http://open.api.ebay.com/shopping';
			$this->findingUrl = 'http://svcs.ebay.com/services/search/FindingService/v1';
			$this->loginUrl = 'https://signin.ebay.com/ws/eBayISAPI.dll';
			$this->feedbackUrl = 'http://feedback.ebay.com/ws/eBayISAPI.dll';
		}
	}
}