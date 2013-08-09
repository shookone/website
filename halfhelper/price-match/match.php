<?php

/**
 * This is a script that will automatically match my price on half.com
 * Requires an input file of ISBN/Max Price key value pairs
 * Proper usage is in the command prompt on windows by running
 * php match.php csvFileName
 *
 */

require('CurlHelper.php');
$errorLog = "errorLog.txt";

if($argc != 2) {
	echo "Proper usage is: php match.php fileName \nWhere filename is the name of ";
	echo "a csv of ISBNs, max prices";
	exit();
}

// parse the input file and build the ISBNs array
$input = fopen($argv[1], "r");
while(($row = fgetcsv($input, ",")) !== FALSE) {
	$isbns[$row[0]] = $row[1];
}

// Get the sign in webpage via curl
$login_url = "https://signin.ebay.com/ws/eBayISAPI.dll?co_partnerid=502";
$data = CurlHelper::curl($login_url);
$domx = CurlHelper::getDOMXPath($data);

$params = CurlHelper::getFormInputs("SignInForm", $domx);
$params['userid'] = "tropurchases";
$params['pass'] = "trobuys4";

// Post our built form to the site via curl
$data = CurlHelper::curl($login_url, $login_url, $params);

// build an array of parameters for the query of an isbn
$params = array(
	'_trksid' => '',
	'sby' => '',
	'query' => '',
	'm' => '',
	'x' => '0',
	'y' => '0'
);

// set the parameters associated with matching a price
$wishlistParams = array(
	'MfcISAPICommand' => 'HalfManagePreOrder',
	'pr' => '', // get from url
	'catId' => '4',
	'mode' => 'add',
	'action' => 'submit',
	'mxp' => '', // maximum price
	'cd' => '3', // minimum condition "3" => good
	'rfb' => '50', // min seller feedback "50" => 50+
	'pfb' => '95', // min seller % "95" => 95%
	'expiration' => '12', // expiration "12" => 12 weeks
	'en' => '1', // send email updates?
	'ad_list_submit.x' => '1', // image input submit location
	'ad_list_submit.y' => '1'
);

// iterate through all of our isbns and attempt to match the price
foreach ($isbns as $isbn => $maximumPrice) {
	echo "Trying ISBN: {$isbn}\n";
	$params['query'] = $isbn;
	// Search for the current isbn
	$url = 'http://search.half.ebay.com/ws/web/HalfSearch?' . http_build_query($params);
	echo "Getting URL: {$url}..";
	$data = CurlHelper::curl($url);
	echo "OK\n";
	
	$domx = CurlHelper::getDOMXPath($data);

	// Find the link to the match my price page
	$arr = CurlHelper::getXPathNodes($domx->evaluate("//a[text()='Match my price']/@href"));
	if(!sizeof($arr)) {
		echo "Couldn't find link";
		CurlHelper::logError($isbn, $maximumPrice, "0", $errorLog);
		continue;
	}
	$wishlist_url = $arr[0];

	echo "Getting URL: {$wishlist_url}..";
	// Get the page for match my price
	$data = CurlHelper::curl($wishlist_url);
	echo "OK\n";

	$parts = parse_url($wishlist_url);
	parse_str($parts['query'], $query);
	// Add the pr from the query string and the maximum price
	$wishlistParams['mxp'] = $maximumPrice;
	$wishlistParams['pr'] = $query['pr'];
	
	$post_url = 'http://wishlist.half.ebay.com/ws/eBayISAPI.dll';
	echo "Getting URL: {$post_url}..";
	// Post the data to get to the confirmation web page
	$data = CurlHelper::curl($post_url, $wishlist_url, $wishlistParams);
	echo "OK\n";
	
	$domx = CurlHelper::getDOMXPath($data);
	
	// Confirm that we got to the confirmation page
	$formArray = CurlHelper::getXPathNodes($domx->evaluate("//form[@name='fplacepreorder']"));
	if(!sizeof($formArray)) {
		echo "Failed to find price match";
		CurlHelper::logError($isbn, $maximumPrice, "1", $errorLog);
		continue;
	}
	
	$confirmationParams = CurlHelper::getFormInputs("fplacepreorder", $domx);
	unset($confirmationParams['setspeedy']);
	
	$confirmation_url = "https://checkout.half.ebay.com/ws/eBayISAPI.dll";
	echo "Getting URL: {$confirmation_url}..";
	// Confirm the entire process and make the final post
	$data = CurlHelper::curl($confirmation_url, $post_url, $confirmationParams);
	file_put_contents("confirmation{$isbn}.html", $data);
	echo "OK\n";
	echo "Your requested match was submitted.\n";
	
}


