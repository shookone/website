<?php

error_reporting(E_ALL);

include 'includes/config.php';
include 'includes/connect.php';
include 'includes/template.php';
include 'includes/validator.php';
include 'includes/Finder.php';

$sql = " select * from users";
$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
while($row = mysql_fetch_assoc($result)) {
	print_r($row);
}