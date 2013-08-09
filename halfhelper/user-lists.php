<?php

require_once('boilerplate.php');



// Working on displaying all the lists for a given user.. 
// Creatign a new query

ob_start();
?>

<h1>User Lists</h1>
<div class="btn-group">
	<a class="btn btn-inverse dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="icon-user icon-white"></i>&nbsp;Delete List
		<span class="caret"></span>
	</a>
	<ul class="dropdown-menu dropdown-flip">
		<?php
			$names = Finder::getListNameByUser($_SESSION['user_id']);
			foreach ($names as $list) {
				?>
				<li><a href="delete-list.php?id=<?php echo $list['id']; ?>"><i class="icon-book"></i>&nbsp;<?php echo $list['name'] ?></a></li>
				<?php 
			}
		?>
	</ul>
</div>
<table class="table table-bordered"> 
	<thead>
		<tr>
			<th>List Name</th>
			<th>Date Created</th>
			<th># of Books</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$names = Finder::getListByUser($_SESSION['user_id']);
			foreach ($names as $key) {
				$count = Finder::getTotalBookCount($key['name']);
				?>
				<tr>
				<td><a href="view-lists.php?id=<?php echo $key['id']; ?>" > <?php echo $key['name']; ?></a></td>
				<td><?php echo $key['created_at']; ?></td>
				<td><?php echo $count; ?></td>
				</tr>
				<?php
			}
		?>
	</tbody>
</table>
<?php 

$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

