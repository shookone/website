<?php

require_once('boilerplate.php');

//Making sure you're logged in to use this feature..
if (!(isset($_SESSION['logged_in']))) {
	header('Location: ' . 'index.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		if(isset($_POST['textarea'])) {
			if (!strlen(trim($_POST['name']))) {
				throw new Exception('Please enter a name for your list');
			}	
		
			$sql = "INSERT INTO `halfhelper`.`lists` (
				`id` ,
				`user_id` ,
				`name` ,
				`created_at` ,
				`is_listed`
				)
				VALUES (
				NULL ,
				'" . mysql_real_escape_string($_SESSION['user_id']) . "',
				'" . mysql_real_escape_string($_POST['name']) . "',
				CURRENT_TIMESTAMP,
				FALSE
				);";
				
			$result = mysql_query($sql);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}

			$id = mysql_insert_id();
			
			$isbns = preg_split("/\s+|,|;/", $_POST['isbn']);
			$isbnsConditions = array();
			for($i = 0; $i < count($isbns); $i+=2) {
				$isbnsConditions[$isbns[$i]] = $isbns[$i + 1];
			}
		
			foreach($isbnsConditions as $isbn => $conditions) {
				
				if(!validator::isbns($isbn)) {
					// One of the isbns was invalid, proceed but don't add to list
					continue;
				}
				
				$bookID = Finder::getBookByISBN($isbn);
				if(!$bookID) {
					continue;
				}
				
				// Need to add the information to a row in my
				$sql = " INSERT INTO `halfhelper`.`books_lists` (
						`book_id` ,
						`list_id` ,
						`condition_id` ,
						`sale_id`
						)
						VALUES (
						'" . mysql_real_escape_string($bookID['id']) . "',
						'" . mysql_real_escape_string($id) . "',
						'" . mysql_real_escape_string($condition) . "',
						NULL
						)";
				$result = mysql_query($sql);
				if (!$result) {
					die('Invalid query: ' . mysql_error());
				}
				
				header('Location: ' . 'view-lists.php?' . 'id=' . $id);
			}
		}
		else if(isset($_POST['upload'])) {
		
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
			if(Finder::nameExists($path['filename'], $_SESSION['user_id'])) {
				throw new Exception("List name already exists.");
			}
			if(!(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $targetPath))) {
				echo "There was an error uploading the file";
			}
				
			$txt = file_get_contents($targetPath);
			$isbns = preg_split("/\s+|,|;/", $txt);
			$isbnsConditions = array();
			for($i = 0; $i < count($isbns); $i+=2) {
				print $isbns[$i];
				$isbnsConditions[$isbns[$i]] = $isbns[$i + 1];
			}
			
			$sql = "INSERT INTO `halfhelper`.`lists` (
				`id` ,
				`user_id` ,
				`name` ,
				`created_at` ,
				`is_listed`
				)
				VALUES (
				NULL ,
				'" . mysql_real_escape_string($_SESSION['user_id']) . "',
				'" . mysql_real_escape_string($path['filename']) . "',
				CURRENT_TIMESTAMP,
				FALSE
				);";
				
			$result = mysql_query($sql);
			if (!$result) {
				die('Invalid query: ' . mysql_error());
			}
			$id = mysql_insert_id();
			
			foreach($isbnsConditions as $isbn => $condition) {
		
				if(!validator::isbns($isbn)) {
					// One of the isbns was invalid, proceed but don't add to list
					continue;
				}
				
				$bookID = Finder::getBookByISBN($isbn);
				if(!$bookID) {
					continue;
				}
				
				// Need to add the information to a row in my
				$sql = " INSERT INTO `halfhelper`.`books_lists` (
						`book_id` ,
						`list_id` ,
						`condition_id` ,
						`sale_id`
						)
						VALUES (
						'" . mysql_real_escape_string($bookID['id']) . "',
						'" . mysql_real_escape_string($id) . "',
						'" . mysql_real_escape_string($condition) . "', 
						NULL
						)";
				$result = mysql_query($sql);
				if (!$result) {
					die('Invalid query: ' . mysql_error());
				}
			}
			header('Location: ' . 'view-lists.php?' . 'id=' . $id);
			exit();
		}
	}
	catch (Exception $e) {
		$_SESSION['error'] = $e->getMessage();
		header('Location: ' . 'upload-list.php');
		exit();
	}
}
	
ob_start();

?>
<form action="" method="post" class="well form-search">
	<h3>Create a list of ISBNs for us to save</h3>
	<textarea type="text" class="input-medium search-query" name="isbn" id="isbn"></textarea>
		<p class="help-block">Enter one or more ISBNs to create a list</p>
	<input type="text" class="input-medium search-query" name="name" id="name">
		<p class="help-block">Enter a name for your list</p>
	<button type="submit" name="textarea" class="btn">Submit</button>
</form>

<form class="well form-search" enctype="multipart/form-data" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
<p>Choose a file to upload</p>
<input name="uploadedfile" type="file" /><br />
<span class="help-block">The upload must be a plaintext file under 1MB.</span>
<button type="submit" class="btn" name="upload">Upload</button>
</form>
<?php 

$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

