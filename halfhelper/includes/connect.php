<?php 

$link = mysql_connect(
	DB_HOST,
	DB_USER,
	DB_PWD
);
if (!$link) {
	die('Could not connect: ' . mysql_error());
}
$db_selected = mysql_select_db(DB_DATABASE);
if (!$db_selected) {
	die ('Can\'t use foo : ' . mysql_error());
}