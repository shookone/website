<?php

require_once('../boilerplate.php');

print_r($_POST);

if(isset($_POST['list_id']) && isset($_POST['book_id'])) {
	$sql = " INSERT INTO `halfhelper`.`books_lists` (
			`book_id` ,
			`list_id`
			)
			VALUES (
			'" . mysql_real_escape_string($_POST['book_id']) . "', 
			'" . mysql_real_escape_string($_POST['list_id']) . "'
			);";
	$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
	// parameters wrong, do nothing?

}