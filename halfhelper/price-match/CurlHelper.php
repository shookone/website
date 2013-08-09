<?php

/**
 * TroTools
 *
 * @author Rusty Fausak <rustyfausak@gmail.com>
 * @copyright Copyright (c) 2010-2011, Textbook Rental Operations, LLC
 * @package trotools
 *
 * Methods for scraping and parsing via curl and XPath
 */

class CurlHelper {
	private function __construct() {}
	
	/**
	 * Uses the curl library to get a webpage. Saves cookie information locally
	 *
	 * @param string $url the url to get
	 * @param string $reffer a url to put as the refferer in the header
	 * @param array $post an array of key / value pairs representing POSTs
	 * @return array Array('error' => string, 'httpCode' => int, 'html' => string)
	 */
	public static function curl($url, $reffer = false, $post = false) {
		static $header = array( 
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Accept-Language: en-us,en;q=0.5",
			"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
			"Keep-Alive: 300",
			"Connection: keep-alive"
		);
		static $agent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2";
		static $cookiefile = "_cookiefile.txt";
		static $timeout = 10;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip"); // Does encoding and deflate

		if ($reffer) {
			curl_setopt($ch, CURLOPT_REFERER, $reffer);
		}

		if ($post) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}

		$html = curl_exec($ch);
		$html = str_replace('&nbsp;', ' ', $html);
		$html = html_entity_decode($html, ENT_QUOTES, 'ISO-8859-1');
		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = false;
		
		if (curl_errno($ch)) {
			$error = curl_error($ch);
		}
		
		curl_close($ch);
		
		return array(
			'error' => $error,
			'httpCode' => $httpcode,
			'html' => $html
		);
	}
	
	/**
	 * Given a DOMNodeList Object, loops through the results and puts them into an
	 * array and returns them
	 *
	 * @param DOMNodeList Object $entries
	 * @return array
	 */
	public static function getXPathNodes($entries) {
		$arr = array();
		foreach ($entries as $entry) {
			$arr[] = trim((string)$entry->nodeValue);
		}
		return $arr;
	}
	
	/**
	 * Create a DOMX object
	 * @param array of data with 'html' inside we can use
	 */
	public static function getDOMXPath($data) {
		$dom = new DOMDocument();
		@$dom->loadHTML($data['html']);
		return new DOMXPath($dom);
	}
	
	/**
	 * Gets all the elements of the form called $name
	 *
	 */
	 public static function getFormInputs($formName, $domx) {
		$arr = CurlHelper::getXPathNodes($domx->evaluate("//form[@name='{$formName}']//input/@name"));
		$params = array();
		// build an array of name, value pairs for the sign in form input
		foreach ($arr as $key) {
			$valArray = CurlHelper::getXPathNodes($domx->evaluate("//form[@name='{$formName}']//input[@name='{$key}']/@value"));
			$params[$key] = $valArray[0];
		}
	 return $params;
	 }
	  
	/**
	 * Appends to the error log file 
	 *
	 */
	 public static function logError($isbn, $price, $error, $errorLog) {
		$message = "ISBN: {$isbn} Max Price: {$price}\r\n";
		if($error == "0") {
			$message .= "Could not find Match My Price Link\r\n";
		}
		else {
			$message .= "Failed to complete price match for the given ISBN\r\n";
		}
		$fh = fopen($errorLog, 'ab') or die("Can't open log file");
		fwrite($fh, $message);
		fclose($fh);
	 }
}
