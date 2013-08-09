<?php
// This is a simple way to parse XML into an array in PHP

require_once('boilerplate.php');
require('price-match/CurlHelper.php');
$errorLog = "halfErrorLog.txt";
$stime = microtime(true);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		$whitelist = array("txt", "text");
		$path = pathinfo($_FILES['uploadedfile']['name']);
		
		if(!in_array($path['extension'], $whitelist)) {
			throw new Exception("Invalid file extension.");
		}
		
		if((!($_FILES['uploadedfile']['type'] == "text/plain"))
		|| !($_FILES['uploadedfile']['size'] < 1000000)) {
			throw new Exception("Invalid file upload, please check restrictions.");
		}
	
		$uploadDirectory = 'uploads/';
		$targetPath = $uploadDirectory . basename($_FILES['uploadedfile']['name']);
		$path = pathinfo($targetPath);
		
		if(!(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $targetPath))) {
			echo "There was an error uploading the file";
		}
			
		$txt = file_get_contents($targetPath);
		$isbns = preg_split("/\s+|,|;/", $txt);

		$conditions = array(
			"1" => "BrandNew",
			"2" => "LikeNew",
			"3" => "VeryGood",
			"4" => "Good",
			"5" => "Acceptable"
		);
		// iterate through all isbns given in the input file
		foreach ($isbns as $isbn) {
			foreach ($conditions as $key => $condition) {
				$xmlURL = "https://svcs.ebay.com/services/half/HalfFindingService/v1?OPERATION-NAME=findHalfItems&X-EBAY-SOA-SERVICE-NAME=HalfFindingService&SERVICE-VERSION=1.0.0&GLOBAL-ID=EBAY-US&X-EBAY-SOA-SECURITY-APPNAME=WillDris-4d94-4dd3-bd74-9239d49c70a6&RESPONSE-DATA-FORMAT=XML&REST-PAYLOAD&productID={$isbn}&productID.@type=ISBN&itemFilter.name=Condition&itemFilter.value={$condition}&sortBy.sortOn=FixedPrice&sortBy.sortOrder=INCREASING";
				print(microtime(true) - $stime) . "\n";
				$data = new SimpleXMLElement($xmlURL, null, true);
				if($data->ack == "Failure") {
					//Fatal error occurred, write to errorLog, continue to next iteration 
					logError($isbn, $errorLog, $data->errorMessage->error);
					continue;
				}
				// Now we know the data exists on half.com. It may or may not exist in our
				// database. we must check for this
				$currentBook = Finder::getBookByISBN($isbn);
				if(!$currentBook) {
					// The isbn has no entry in our books table, we must add it
					$sql = "
						INSERT INTO `halfhelper`.`books` (
						`id` ,
						`isbn` ,
						`title` ,
						`author` ,
						`edition`
						)
						VALUES (
						NULL , 
						'" . mysql_real_escape_string($isbn) ."', 
						'" . mysql_real_escape_string($data->product->title) ."',
						'" . mysql_real_escape_string($data->product->itemSpecifics->nameValueList->value) ."',
						''
						);";
					$result = mysql_query($sql);
					if (!$result) {
						die('Invalid query: ' . mysql_error());
					}
				}
				
				$sql = " 
							INSERT INTO `halfhelper`.`api_calls` (
							`id` ,
							`pulled_at` ,
							`book_id` ,
							`condition_id`
							)
							VALUES (
							NULL ,
							'" . mysql_real_escape_string($data->timestamp) . "',
							'" . mysql_real_escape_string($currentBook['id']) . "',
							'" . mysql_real_escape_string($key) . "'
							);";
				$result = mysql_query($sql);
				if (!$result) {
					die('Invalid query: ' . mysql_error());
				}
				
				$id = mysql_insert_id();
				foreach ($data->product->item as $book) {
					//We should add everything to the api_calls table
					
					// Now we need to add all the relevant information to our database..
					$sql = "
						INSERT INTO `halfhelper`.`price` (
						`id` ,
						`price` ,
						`feedback` ,
						`api_call_id`
						)
						VALUES (
						NULL ,
						'" . mysql_real_escape_string($book->price) . "',
						'" . mysql_real_escape_string($book->seller->feedbackScore) ."',
						'" . mysql_real_escape_string($id) . "'
						);";
					$result = mysql_query($sql);
					if (!$result) {
						die('Invalid query: ' . mysql_error());
					}
				}
				//print_r($data);
			}
		}
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
		header('Location: ' . 'parseHalfXML.php');
		exit();
	}
}
ob_start();

?>

<form class="well form-search" enctype="multipart/form-data" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
<p>Choose a text file of ISBN's delimited by whitespace or commas</p>
<p>This will query the half.com database</p>
<input name="uploadedfile" type="file" /><br />
<span class="help-block">The upload must be a plaintext file under 1MB.</span>
<button type="submit" class="btn" name="upload">Upload</button>
</form>

<!--
// Given a list of ISBNs
// If it doesn't exist in our database, check to see if it exists on half.com. 
// If it does, then add to the books table
// create entries into pricing table with all of the given prices
// lowest 3 with 500+ feedback
-->

<?php 

function logError($isbn, $errorLog, $error) {
	$message = "There was a";
	if($error->severity == "Error") {
		$message .= "n error processing ISBN {$isbn}.\r\n";
	}
	else {
		$message .= " warning processing ISBN {$isbn}.\r\n";
	}
	$message .= "The errorId was {$error->errorId}.\r\n";
	$message .= $error->message . "\r\n";
	$fh = fopen($errorLog, 'ab') or die("Can't open log file");
	fwrite($fh, $message);
	fclose($fh);
}

$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

