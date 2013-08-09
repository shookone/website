#!/usr/bin/php
<?php 

include dirname(__FILE__) . '/../includes/config.php';
include dirname(__FILE__) . '/../includes/connect.php';
include dirname(__FILE__) . '/../includes/Finder.php';

$conditions = array(
	"1" => "BrandNew",
	"2" => "LikeNew",
	"3" => "VeryGood",
	"4" => "Good",
	"5" => "Acceptable"
);

$pids = array();

class HalfScrape{
	public $isbn;
	public $condition;
	public $condition_id;
	public $errorLog = "halfErrorLog.txt";
	public $link;
	
	public function __construct() {
		
		$this->link = mysql_connect(
			DB_HOST,
			DB_USER,
			DB_PWD,
			true
		);
		if (!$this->link) {
			die('Could not connect: ' . mysql_error($this->link) . "\n");
		}
		if (!mysql_select_db(DB_DATABASE, $this->link)) {
			die ('Can\'t use foo : ' . mysql_error($this->link) . " This is the errorNUmber: " . mysql_errno($this->link) . "\n");
		}
	}
	
	public function update($isbn, $condition, $condition_id) {
		$this->isbn = $isbn;
		$this->condition = $condition;
		$this->condition_id = $condition_id;
	}
	
	public function attemptQuery($sql, $newLink) {
		$result = mysql_query($sql, $newLink);

		if(!$result) {
			print "Invalid query: " . mysql_error($newLink) . "\n";
			if(!is_null($newLink)) {
				@mysql_close($newLink);
				$newLink = null;
			}
			print "***** Attempting reconnect *****\n";
			// Attempt to reconnect ten times for now
			for ($i = 0; $i < 10; $i++) {
				try {
					$this->link = mysql_connect(
						DB_HOST,
						DB_USER,
						DB_PWD,
						true
					);
					if(!mysql_ping($this->link)) {
						throw new Exception("Ping was unsuccessful");
					}
					if(!($this->link)) {
						throw new Exception("Could not connect to database");
					}
					else {
						print "This is the new link: " . $this->link . "\n";
						$resul = mysql_query($sql, $this->link);
						if(!$resul) {
							throw new Exception("Reconnect was successul, subsequent query was not\n");
						}
						return $resul;
					}
				}
				catch (Exception $e) {
					print "Failed attempt to reconnect. Sleeping for 60...\n";
					sleep(30);
					continue;
				}
			}
		}
		else {
			return $result;
		}
		
	}
	
	public function run() {
		$pid = pcntl_fork();
		if ($pid == -1) {
			die('Could not fork');
		}
		elseif ($pid) {
			// Parent
			return $pid; // return the child pid
		}
		else {
			try {
				// We're a child, lets call the half.com api
				
				$xmlURL = "https://svcs.ebay.com/services/half/HalfFindingService/v1?OPERATION-NAME=findHalfItems&X-EBAY-SOA-SERVICE-NAME=HalfFindingService&SERVICE-VERSION=1.0.0&GLOBAL-ID=EBAY-US&X-EBAY-SOA-SECURITY-APPNAME=WillDris-4d94-4dd3-bd74-9239d49c70a6&RESPONSE-DATA-FORMAT=XML&REST-PAYLOAD&productID={$this->isbn}&productID.@type=ISBN&itemFilter.name=Condition&itemFilter.value={$this->condition}&sortBy.sortOn=FixedPrice&sortBy.sortOrder=INCREASING";
				
				print "PID: " . posix_getpid() . " ISBN: {$this->isbn} Condition: {$this->condition}  Link: {$this->link} \n";
				
				$data = new SimpleXMLElement($xmlURL, null, true);
				//Fatal error occurred, write to errorLog 
				if($data->ack == "Failure") {
					logError($this->isbn, $this->errorLog, $data->errorMessage->error, $this->condition);
					throw New Exception("Failure on call to half.com api");
				}
				
				// Now we know the data exists on half.com. It may or may not exist in our
				// database. we must check for this
				
				$arr = array();
				$sql = "
					SELECT `isbn`, `title`, `author`, `edition`, `id`
					FROM `books` 
					WHERE `isbn` LIKE '" . mysql_real_escape_string($this->isbn, $this->link) . "'";
				$result = halfScrape::attemptQuery($sql, $this->link);
				if (!$result) {
					throw new Exception('03 Invalid query: ' . mysql_error($this->link));
				}
			
				if (mysql_num_rows($result) == 0) {
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
						'" . mysql_real_escape_string($this->isbn, $this->link) ."', 
						'" . mysql_real_escape_string($data->product->title, $this->link) ."',
						'" . mysql_real_escape_string($data->product->itemSpecifics->nameValueList->value, $this->link) ."',
						''
						)";
					$result =halfScrape::attemptQuery($sql, $this->link);
					if (!$result) {
						throw new Exception('00 Invalid query: ' . mysql_error($this->link));
					}
					$currentBookID = mysql_insert_id();
				}
				if (mysql_num_rows($result) >= 1) {
					$currentBook = mysql_fetch_assoc($result);
					$currentBookID = $currentBook['id'];
					$sql = "
						UPDATE `halfhelper`.`books` SET
							`title` = '" . mysql_real_escape_string($data->product->title, $this->link) ."',
							`author` = '" . mysql_real_escape_string($data->product->itemSpecifics->nameValueList->value, $this->link) ."'
						WHERE `isbn` = '" . mysql_real_escape_string($this->isbn, $this->link) . "'";
						
					$result = halfScrape::attemptQuery($sql, $this->link);
					if (!$result) {
						throw new Exception('Invalid query: ' . mysql_error($this->link));
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
						'" . mysql_real_escape_string($data->timestamp, $this->link) . "',
						'" . mysql_real_escape_string($currentBookID, $this->link) . "',
						'" . mysql_real_escape_string($this->condition_id, $this->link) . "'
						);";
				$result = halfScrape::attemptQuery($sql, $this->link);
				if (!$result) {
					throw new Exception('Invalid query: ' . mysql_error($this->link));
				}
				
				$id = mysql_insert_id($this->link);
				foreach ($data->product->item as $book) {
					// Now we need to add all the relevant information to our price table
					$sql = "
						INSERT INTO `halfhelper`.`price` (
						`id` ,
						`price` ,
						`feedback` ,
						`api_call_id`
						)
						VALUES (
						NULL ,
						'" . mysql_real_escape_string($book->price, $this->link) . "',
						'" . mysql_real_escape_string($book->seller->feedbackScore, $this->link) ."',
						'" . mysql_real_escape_string($id, $this->link) . "'
						);";
					$result = halfScrape::attemptQuery($sql, $this->link);
					if (!$result) {
						throw new Exception('02 Invalid query: ' . mysql_error($this->link));
					}
				}
			
			}
			catch(Exception $e) {
				print $e->getMessage() . "\n";
				// Error occurred, skip processing and let the thread die
			}
			// Child/Orphan process
			print posix_getpid() . "-" . "Done\n";
			
			// Wait for signal SIGTERM
			/*for (;;) {
				sleep(10);
			}*/
			
			// Eventually kill self
			posix_kill(posix_getpid(), SIGTERM);
		}
	}
}
//one thing to be wary about is if the max run per day is 40k
// and you have 120001 books, the fourth day will run only for 1 isbn.. 
$totalISBNs = Finder::getAllISBNs();
$days = ceil(count($totalISBNs)/MAX_CALLS);
$startAt = (date('U') / 3600) % $days + 1;
//$isbns = Finder::getSelectedISBNs($startAt);
$isbns = Finder::getAllISBNs();

mysql_close($link);

$max_threads = 18;
$hs = array();
for($i = 0; $i < $max_threads; $i++) {
	$hs[] = new halfScrape();
}
//$hsCount = 0;
//$step = floor($max_threads / sizeof($conditions));

//for ($i = 0; $i < MAX_CALLS; $i += 18) 
$callsMade = 0;
$i = 0; // index of isbns
$c = 1; // index of conditions
$h = 0; // index of halfscrape objects
global $pids; // keeps track of current processes
global $conditions; // available conditions for books
//Need to change back to MAX_CALLS
while($callsMade < MAX_CALLS * 5) {
	if($c == 6) {
		$c = 1;
	}
	if(isset($isbns[$i])) {
		$hs[$h]->update($isbns[$i], $conditions[$c], $c);
		$hs[$h]->run();
	}
	//print "ISBN: $i C C[C] $c $conditions[$c] HS: $h callsMade: $callsMade \n";
	$c++;
	$h++;
	$callsMade++;
	if($callsMade % 5 == 0) {
		$i++;
	}
	if($h == $max_threads) {
		sleep(5);
		$h = 0;
		print $callsMade . " " . $i ."========\n";
	}
}
sleep(5);
print "Finished processing, check the error log for information regarding failures\n";
 
/*$max_threads = 15;
$hs = array();
for($i = 0; $i < $max_threads; $i++) {
	$hs[] = new halfScrape();
}
$hsCount = 0;
$step = floor($max_threads / sizeof($conditions));

print "========\n";

for ($i = 0; $i < count($isbns); $i += $step) {
	for ($j = 0; $j < $step; $j++) {
		$k = $i + $j;
		if (isset($isbns[$k])) {
			process($isbns[$k], $hs, $hsCount); // fills $pids
			$hsCount+=5;
			if($hsCount >= $max_threads) {
				print_r($pids);
				$hsCount = 0;
			}
		}
	}
	sleep(5);
	print "-----\n";
}
print "Finished processing, check the error log for information regarding failures\n";

function process($isbn, $hs, $i) {
	global $pids;
	global $conditions;

	foreach($conditions as $key => $condition) {
		$hs[$i]->update($isbn, $condition, $key);
		//$pid = $hs[$i]->run();
		$hs[$i]->run();
		$i++;
		//$pids[] = $pid; // took this out, not sure what it was doing..
	}
}*/

function logError($isbn, $errorLog, $error, $condition) {
	$message = "There was a";
	if($error->severity == "Error") {
		$message .= "n error processing ISBN {$isbn} with a condition: {$condition}.\r\n";
	}
	else {
		$message .= " warning processing ISBN {$isbn} with a condition: {$condition}.\r\n";
	}
	$message .= "The errorId was {$error->errorId}.\r\n";
	$message .= $error->message . "\r\n";
	$fh = fopen($errorLog, 'ab') or die("Can't open log file");
	fwrite($fh, $message);
	fclose($fh);
}
