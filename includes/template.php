<?php
error_reporting(E_ALL);

require_once('config.example.php');
require_once('Security.php');
/**
 * Wraps custom body in header and footer
 *
 * @param string $title
 * @param string $js
 * @param string $css
 * @param string $body
 * @return string 
 */
function wrap($title, $js, $css, $body) {
	ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">
	
	<!-- CSS Styles -->
	<link href="<?=BASE_URL?>css/bootstrap.css" rel="stylesheet">
	<link href="<?=BASE_URL?>css/bootstrap-responsive.css" rel="stylesheet">
	<?php echo $css; ?>
	
	<!-- Javascript -->
	<script type="text/javascript" src="<?=BASE_URL?>js/jquery-1.9.1.min.js"></script>
	<script type="text/javascript" src="<?=BASE_URL?>js/bootstrap.js"></script>
	<?php echo $js; ?>

</head>
<body>
	<a name="top"></a>

<!-- Navbar -->	
<div class="navbar wcb-navbar">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?=BASE_URL?>index.php">
				<img class="screen-only" src=<?=BASE_URL?>'img/dali.jpg alt="Dali">
			</a>
			<div id="user-button" class="pull-right" style="margin-top: -1px;">
			</div>
			
			<a class="btn btn-navbar pull-left" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			
			<div class="nav-collapse">
				<ul class="nav">
					<li><a href="<?=BASE_URL?>ultimate.php">Ultimate</a></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<div class="container">
	<?php echo $body; ?>
</div>

<!-- Footer -->
	
<footer class="footer">
	<div class="container">
		<p><small>&copy; <?php echo date("%Y-%m-%d"); ?>365</a></small></p>
	</div>
</footer>

</body>
</html>
	<?php
		return ob_get_clean();
}