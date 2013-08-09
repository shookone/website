<?php

// Loads in all files I need. Security, config, etc. This is the way a page should look
require_once('includes/template.php');

ob_start();
//Put html here that makes up the body of my website. This is basically the only part that changes....

$body = ob_get_clean();

print wrap('Login', $js, '', $body);