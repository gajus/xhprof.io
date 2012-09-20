<form action="" method="post">
	<div class="columns">
		<div class="column">
			<?=ay_input('query[datetime_from]', 'Date-time from', NULL, array('comment' => '<a href="http://lt.php.net/manual/en/datetime.createfromformat.php" target="_blank">Date-time format</a> is <code>Y-m-d H:i:s</code> or timeless (<code>Y-m-d</code>).'))?>
		</div>
		<div class="column">
			<?=ay_input('query[datetime_to]', 'Date-time to', NULL, array('comment' => '<a href="http://lt.php.net/manual/en/datetime.createfromformat.php" target="_blank">Date-time format</a> is <code>Y-m-d H:i:s</code> or timeless (<code>Y-m-d</code>).'))?>
		</div>
		<div class="column">
			<?=ay_input('query[host]', 'Host', NULL, array('comment' => 'You can use <code>%</code> just like in the <a href="http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#operator_like" target="_blank">SQL LIKE</a> conditionals to match results.'))?>
		</div>
		<div class="column">
			<?=ay_input('query[host_id]', 'Host #')?>
		</div>
		<?php if($template['file'] != 'hosts'):?>
		<div class="column">
			<?=ay_input('query[uri]', 'URI', NULL, array('comment' => 'You can use <code>%</code> just like in the <a href="http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#operator_like" target="_blank">SQL LIKE</a> conditionals to match results.'))?>
		</div>
		<div class="column">
			<?=ay_input('query[uri_id]', 'URI #')?>
		</div>
		<?php endif;?>
	</div>
	
	<div class="buttons">
		<input type="submit" value="Filter Data" />
		<?php if(!empty($template['filters'])):?>
		<a href="<?=xhprof_url('uris')?>">Reset Filters</a>
		<?php endif;?>
	</div>
</form>