<?php

require_once('boilerplate.php');

if(isset($_GET['id'])) {
	// Need to get an array of isbns for the current user_id and 'name'
	$isbns = array();
	$isbns = Finder::getISBNSByListID($_GET['id']);

	ob_start();

?>

<h1>List Results</h1>
<div class="btn-group">
	<a class="btn btn-inverse dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="icon-user icon-white"></i>&nbsp;Change Lists
		<span class="caret"></span>
	</a>
	<ul class="dropdown-menu dropdown-flip">
		<?php
			$names = Finder::getListNameByUser($_SESSION['user_id']);
			foreach ($names as $list) {
				?>
				<li><a href="view-lists.php?id=<?php echo $list['id']; ?>"><i class="icon-book"></i>&nbsp;<?php echo $list['name'] ?></a></li>
				<?php 
			}
		?>
	</ul>
</div>
<table class="table table-bordered"> 
	<thead>
		<tr>
			<th>ISBN</th>
			<th>Title</th>
			<th>Author</th>
			<th>Condition</th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach ($isbns as $isbn){
				$row = Finder::getBookResultsByISBN($isbn['isbn'], $isbn['id'] );
				?>
					<tr>
					<td><a href="book.php?b=<?php echo $row['isbn']; ?>&c=4"><?php echo $row['isbn']?></a></td>
					<td><?php echo $row['title']; ?></td>
					<td><?php echo $row['author']; ?></td>
					<td><?php echo getCondition($row['condition_id']); ?></td>
					</tr>
				<?php
			}
		?>
	</tbody>
</table>
<form action="listing/auth.php" method="post">
	<div class="control-group">
		<input type="hidden" name="list_id" value="<?=$_GET['id']?>" />
	</div>
	<div class="control-group">
		<button tabindex="1" type="submit">List these Books on half.com</button>
	</div>
</form>
<?php 
}
$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

function getCondition($condition) {
		$i = "Good";
		switch ($condition) {
			case 1:
				$i = "Brand New";
				break;
			case 2:
				$i = "Like New";
				break;
			case 3:
				$i = "Very Good";
				break;
			case 4:
				$i = "Good";
				break;
			case 5:
				$i = "Acceptable";
				break;
		}
		return $i;
	}

