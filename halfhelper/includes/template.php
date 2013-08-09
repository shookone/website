<?php

/**
	 * @param string $title
	 * @param string $js
	 * @param string $css
	 * @param string $body
	 * @return string
	 */
	function wrap($title, $js, $css, $body) {
		$_SESSION['header'] = true;
		ob_start();
		?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">
	
	<!-- CSS Styles -->
	<link href="<?=BASE_URL?>bootstrap/css/bootstrap.css" rel="stylesheet">
	<link href="<?=BASE_URL?>bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<?php echo $css; ?>
	
	<!-- HTML5 shim for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	
	
	<!-- Javascript
	================================================== -->
	<script type="text/javascript" src="<?=BASE_URL?>js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="<?=BASE_URL?>bootstrap/js/bootstrap.js"></script>
	<script type="text/javascript" src="http://konami-js.googlecode.com/svn/trunk/konami.js"></script>
	<script type="text/javascript">
		konami = new Konami()
		konami.load("http://www.youtube.com/watch?v=oHg5SJYRHA0");
	</script>
	
	<?php echo $js; ?>

</head>
<body>
	<a name="top"></a>
	<?php 
	if(isset($_SESSION['header'])) {
	?>

<!-- 
**********************Navbar*************************
-->	
<div class="navbar wcb-navbar">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?=BASE_URL?>index.php">
				<img class="screen-only" src="<?=BASE_URL?>img/hh-logo-small.png" alt="Half Helper">
				<!--<h1 class="print-only">Half Helper</h1> -->
			</a>
			<div id="user-button" class="pull-right" style="margin-top: -1px;">
				<?php
				if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
					$user_id = $_SESSION['name'];
					?>
					<div class="btn-group">
						<a class="btn btn-inverse dropdown-toggle" data-toggle="dropdown" href="#">
							<i class="icon-user icon-white"></i>&nbsp;<?php echo htmlentities($user_id); ?>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu dropdown-flip">
							<li><a href="<?=BASE_URL?>password-change.php"><i class="icon-book"></i>&nbsp;Change your password</a></li>
							<!-- What goes here?
							<li><a href="account/deliveries-scheduled.php"><i class="icon-time"></i>&nbsp;Scheduled Orders</a></li>
							<li><a href="account/index.php"><i class="icon-user"></i>&nbsp;Profile</a></li>
							-->
							<li class="divider"></li>
							<li><a href="<?=BASE_URL?>logout.php"><i class="icon-off"></i>&nbsp;Log Out</a></li>
						</ul>
					</div>
					<?php
				}
				else {
					?>
					<a class="btn btn-inverse" href="<?=BASE_URL?>login.php">Log In</a>
					<?php
				}
				?>
			</div>
			
			<a class="btn btn-navbar pull-left" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			
			<div class="nav-collapse">
				<ul class="nav">
					<li><a href="<?=BASE_URL?>search-isbn.php">Search</a></li>
					<li><a href="<?=BASE_URL?>books.php">Books</a></li>
					<li><a href="<?=BASE_URL?>upload-list.php">Create ISBN List</a></li>
					<li><a href="<?=BASE_URL?>user-lists.php">User Lists</a></li>
					<li class="divider-vertical"></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<!-- 
**********************Footer*************************
	
<footer class="footer">
	<div class="container">
		
		<div class="row space-top10">
			<div class="span2">
				<h4>Follow Us</h4>
				<ul class="unstyled">
					<li><a href="http://facebook.com/westcampusbooks">Facebook</a></li>
					<li><a href="http://twitter.com/westcampusbooks">Twitter</a></li>
				</ul>
			</div>
			<div class="span3">
			</div>
			
			<div class="span7">
			</div>
		</div>
		
		<p><small>&copy; <?php echo date("Y"); ?> <a href="<?php echo ROOT_PATH; ?>">HalfHelper</a></small></p>
	</div>
</footer>
-->

<?php
	}
	
	
	if (isset($_SESSION['error'])) {
		print ' <div class="alert" >' . $_SESSION['error'] . ' </div> ';
		unset($_SESSION['error']);
	}
	?>
	<div class="container">
		<?php echo $body; ?>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}