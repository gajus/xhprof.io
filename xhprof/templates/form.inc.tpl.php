<?php
namespace ay\xhprof;
?>
<form action="" method="post" id="filter">
	<div class="columns">
		<?php if($template['file'] != 'request'):?>
			<div class="column">
				<?=\ay\input('query[datetime_from]', 'Date-time from', NULL, array('comment' => '<a href="http://lt.php.net/manual/en/datetime.createfromformat.php" target="_blank">Date-time format</a> is <code>Y-m-d H:i:s</code> or timeless (<code>Y-m-d</code>).'))?>
			</div>
			<div class="column">
				<?=\ay\input('query[datetime_to]', 'Date-time to', NULL, array('comment' => '<a href="http://lt.php.net/manual/en/datetime.createfromformat.php" target="_blank">Date-time format</a> is <code>Y-m-d H:i:s</code> or timeless (<code>Y-m-d</code>).'))?>
			</div>
			<div class="column">
				<?=\ay\input('query[host]', 'Host', NULL, array('comment' => 'You can use <code>%</code> just like in the <a href="http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#operator_like" target="_blank">SQL LIKE</a> conditionals to match results.'))?>
			</div>
			<div class="column">
				<?=\ay\input('query[host_id]', 'Host #')?>
			</div>
			<?php if($template['file'] != 'hosts'):?>
				<div class="column">
					<?=\ay\input('query[uri]', 'URI', NULL, array('comment' => 'You can use <code>%</code> just like in the <a href="http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#operator_like" target="_blank">SQL LIKE</a> conditionals to match results.'))?>
				</div>
				<div class="column">
					<?=\ay\input('query[uri_id]', 'URI #')?>
				</div>
			<?php endif;?>
			<div class="column">
				<?=\ay\input('query[dataset_size]', 'Dataset Size', NULL, array('comment' => 'Integer number that defines the maximum number of the most recent requests to aggregate that match the query. Defaults to <mark>1,000</mark>.'))?>
			</div>
		<?php endif;?>
		<div class="column">
			<?=\ay\input('query[request_ids]', 'Request IDs', NULL, array('comment' => 'Request ID or a comma-separated list of request IDs to analyse. Entering two IDs will compare the requests.' /* Three or more will list them.*/))?>
		</div>
	</div>
	
	<div class="buttons">
		<input type="submit" value="Filter Data" />
		<?php if(!empty($_GET['xhprof']['query'])):?>
		<a href="<?=url($template['file'])?>">Reset Filters</a>
		<?php endif;?>
	</div>
</form>