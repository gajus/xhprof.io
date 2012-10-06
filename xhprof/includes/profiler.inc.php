<?php
namespace ay\xhprof;

$config['pdo']->exec("SET `profiling` = 1;");
// keep track of as many as possible queries (the maximum value is 100)
// http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_profiling_history_size
$config['pdo']->exec("SET `profiling_history_size` = 100;");

register_shutdown_function(function() use ($config) {
	
	$queries	= $config['pdo']->query("SHOW PROFILES;")->fetchAll(\PDO::FETCH_ASSOC);
	
	$total_duration	= 0;
	
	$queries	= array_map(function($e) use(&$total_duration) { $e['Duration'] = 1000000*$e['Duration']; $total_duration += $e['Duration']; $e['Query'] = preg_replace('/\s+/', ' ', $e['Query']); return $e; }, $queries);
	
	?>
	<style>
		.mysql-debug-table { margin: 20px; }
		.mysql-debug-table table { width: 100%; }
		.mysql-debug-table th.id,
		.mysql-debug-table th.duration { width: 100px; }
	</style>
	<?php
	echo '
	<div class="mysql-debug-table">
		<table>
			<thead>
				<tr>
					<th class="id">Query ID</th>
					<th>Query</th>
					<th class="duration">Duration</th>
				</tr>
			</thead>
			<tbody>';
	foreach($queries as $q):
		echo '<tr><td>' . $q['Query_ID'] . '</td><td>' . $q['Query'] . '</td><td>' . format_microseconds($q['Duration']) . '</td></tr>';
	endforeach;
	echo '
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2">Total</td>
					<td>' . format_microseconds($total_duration) . '</td>
				</tr>
			</tfoot>
		</table>
	</div>';
});