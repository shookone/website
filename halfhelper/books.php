<?php

require_once('boilerplate.php');

if(isset($_GET['isbn'])) {
	$arr = Finder::getBookResults($_GET['isbn']);
ob_start();

?>

<h1>Book Results</h1>
<table class="table table-bordered"> 
	<thead>
		<tr>
			<th>ISBN</th>
			<th>Title</th>
			<th>Author</th>
			<th>Edition</th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach ($arr as $row) {
				echo "<tr>";
				echo "<td>". '<a href="book.php?b=' . $row['isbn'].'">'. $row['isbn'].'</a>' ."</td>";
				echo "<td>".$row['title']."</td>";
				echo "<td>".$row['author']."</td>";
				echo "<td>".$row['edition']."</td>";
				echo "</tr>";
			}
		?>
	</tbody>
</table>
<?php 
}
$body = ob_get_clean();
$html = wrap('Halfhelper', '', '', $body);
print $html;

