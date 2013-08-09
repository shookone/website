<?php

require_once('boilerplate.php');

if(!isset($_GET['b'])) { throw new Exception("No book found"); }

$isbn = trim($_GET['b']);
if(!Validator::isbns($isbn)) {
	throw new Exception("ISBN was invalid");
}

if(!($book = Finder::getBookByISBN($isbn))) {
	throw new Exception("Book was not found");
}
$pricesByCondition = array();
$sql = " 
		SELECT `id`
		FROM `api_calls`
		WHERE book_id = {$book['id']}
		ORDER BY id DESC
		LIMIT 0 , 5 ";
$result = mysql_query($sql);
if (!$result) {
	die('Invalid query: ' . mysql_error());
}		
$ids = array();
while($row = mysql_fetch_assoc($result)) {
	$ids[] = $row;
}
$sql = " Select a.*, b.isbn, c.name, c.abbr, p.price, c.id
		from api_calls a
		LEFT JOIN books b
		ON a.book_id = b.id
		LEFT JOIN conditions c
		ON a.condition_id = c.id
		LEFT JOIN price p
		ON a.id = p.api_call_id
		WHERE b.isbn = $isbn
		AND a.id = {$ids[0]['id']}
		OR a.id = {$ids[1]['id']}
		OR a.id = {$ids[2]['id']}
		OR a.id = {$ids[3]['id']}
		OR a.id = {$ids[4]['id']}
		ORDER BY c.id, p.price ASC ";
$result = mysql_query($sql);
if (!$result) {
	die('Invalid query: ' . mysql_error());
}
while($row = mysql_fetch_assoc($result)) {
	if(!isset($pricesByCondition[$row['name']])) {
		$pricesByCondition[$row['name']] = array();
	}
	$pricesByCondition[$row['name']][] = $row;
}

ob_start();

?>
<script type="text/javascript">
$(document).ready(function() {
	$('.add-book-link').click(function (event) {
		$.post(
			"ajax/add-book-to-list.php",
			{
				book_id: <?php echo $book['id']; ?>,
				list_id: $(this).attr('data-list_id')
			},
			function (data) {
				//window.location.reload(true);
			}
		);
		event.preventDefault();
	});
});
</script>
<style type="text/css">
.asdf { background-color: red!important; }
</style>

<h3><?php echo $book['title'] . ' by ' . $book['author']; ?></h3>
<dl>
	<dt>ISBN: </dt>
		<dd><?php echo $book['isbn']; ?></dd>
	<dt>Edition: </dt>
		<dd><?php echo $book['edition']; ?></dd>
</dl>
<h3>Price History for <?php print $book['title']; ?> </h3>
<p style="color:grey">Click and drag to zoom. Double click to reset. </p>
<div class="btn-group">
	<a class="btn btn-inverse dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="icon-book icon-white"></i>&nbsp;<?php print "Book Condition"; ?>
		<span class="caret"></span>
	</a>
	<ul class="dropdown-menu dropdown-flip">
		<li><a href="book.php?b=<?php print $book['isbn']; ?>&c=1"><i class="icon-heart"></i>&nbsp;Brand New</a></li>
		<li><a href="book.php?b=<?php print $book['isbn']; ?>&c=2"><i class="icon-heart"></i>&nbsp;Like New</a></li>
		<li><a href="book.php?b=<?php print $book['isbn']; ?>&c=3"><i class="icon-heart"></i>&nbsp;Very Good</a></li>
		<li><a href="book.php?b=<?php print $book['isbn']; ?>&c=4"><i class="icon-heart"></i>&nbsp;Good</a></li>
		<li><a href="book.php?b=<?php print $book['isbn']; ?>&c=5"><i class="icon-heart"></i>&nbsp;Acceptable</a></li>
	</ul>
</div>
<div id="dygraphs" style="width:600px; height:300px;">
</div>
<?php $graphData = Finder::getGraphData($book['id'], $_GET['c']);
?>

<?php foreach ($pricesByCondition as $key => $arr) { ?>
	<h4><?php echo $key; ?></h4>
	<table class="table table-bordered"> 
		<thead>
			<tr>
				<th>Price</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($arr as $price) { ?>
				<tr>
					<td><?php echo $price['price']; ?> </td>
				</tr>
			<?php } ?>
		</tbody>
</table>

<?php 
}
?>

<div class="btn-group">
	<a class="btn btn-inverse dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="icon-user icon-white"></i>&nbsp;Add to list
		<span class="caret"></span>
	</a>
	<ul class="dropdown-menu dropdown-flip">
		<?php
			$names = Finder::getListNameByUser($_SESSION['user_id']);
			foreach ($names as $list) {
				?>
				<li><a class="add-book-link" data-list_id="<?php echo $list['id']; ?>" href="#"><i class="icon-book"></i>&nbsp;<?php echo $list['name'] ?></a></li>
				<?php 
			}
		?>
	</ul>
</div>

<h4>Lists</h4>
<table class="table table-bordered"> 
	<thead>
		<tr>
			<th>List Names</th>
			<th>Number of copies on List</th>
		</tr>
	</thead>
	<tbody>
		<?php 
		$names = Finder::getListNameByUser($_SESSION['user_id']);
		foreach ($names as $list) {
			$count = Finder::getIndividualBookCount($book['id'], $list['id']);
			?>
			<tr>
			<td><?php echo $list['name']; ?></td>
			<td><?php echo $count; ?></td>
			</tr>
			<?php 
		}
		?>
	</tbody>
</table>
<?php
$body = ob_get_clean();
ob_start();
?>
<script type="text/javascript" src="js/dygraphs/dygraph-combined.js"></script>
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
      google.load('visualization', '1', {packages: ['linechart']});
      function createDataTable(dateType) {
        data = new google.visualization.DataTable();
        data.addColumn(dateType, 'Date');
        data.addColumn('number', 'Price');
		<?php
			$graphResults = array();
			$apiID = -1;
			$price = 0;
			foreach ($graphData as $calls) {
				if($apiID == $calls['id'] && $price == -1) {
					continue;
				}
				if($apiID != $calls['id']) {
					$apiID = $calls['id'];
					$price = 0;
					$point = array();
				}
				if($calls['feedback'] > 499) {
					$point['date'] = $calls['pulled_at'];
					$point['price'] = $calls['price'];
					$graphResults[] = $point;
					$price = -1;
				}
			}
			print "data.addRows(" . count($graphResults) . ");";
			$i = 0;
			foreach ($graphResults as $point) {
				$unixDate = strtotime($point['date']);
				print "data.setCell(" . $i . ", 0, new Date(\"" . date("F j Y h:i:s", $unixDate) . "\"));";
				print "data.setCell(" . $i . ", 1, " . $point['price'] . ");";
				$i++;
			}
		?>
        return data;
      }

      function drawVisualization() {
      data = createDataTable('date');
        var chart1 = new Dygraph.GVizChart(
            document.getElementById('dygraphs')).draw(data, { stepPlot: true
            });

        data = createDataTable('datetime');
        var chart2 = new Dygraph.GVizChart(
            document.getElementById('dygraphs_datetime')).draw(data, {
            });
      }
      google.setOnLoadCallback(drawVisualization);
    </script>
<?php 
$js = ob_get_clean();
//print_r($graphResults);
$html = wrap('Halfhelper', $js, '', $body);
print $html;

