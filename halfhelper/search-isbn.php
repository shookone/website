<?php

require_once('boilerplate.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		$arr = explode(" ", $_POST['isbn']);
		$header = '';
		
		foreach($arr as $key) {
			if (!strlen(trim($key))) {
				throw new Exception($key . 'was empty.');
			}
			
			if(!validator::isbns($key)) {
				// must change validation because its not just isbns
				//throw new Exception("Please enter one or more 13 digit ISBN's separated by spaces");
			}
			$header .= 'isbn[]=' . $key .'&';
		}
		
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
	}
	
	header('Location: ' . 'books.php?' . $header);
	exit();
}
	
ob_start();

?>
<form action="" method="post" class="well form-search">
	<h3>Enter one or more ISBN's separated by spaces</h3>
	<input type="text" class="input-medium search-query" name="isbn" id="isbn">
	<button type="submit" class="btn">Search</button>
</form>
<?php 

$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

