<?php
namespace ay\xhprof;

if (empty($_GET['xhprof']['query']['request_id'])) {
	throw new \Exception('Request data can be accessed only through the ID.');
}

$request = $xhprof_data_obj->get($_GET['xhprof']['query']['request_id']);

if (!$request) {
	\ay\redirect(\ay\REDIRECT_REFERRER, 'Request data not found.');
}

$xhprof_obj = new Model($request);

if (!empty($_GET['xhprof']['callgraph'])) {
	$xhprof_callgraph	= new Callgraph;
	
	$callstack	= $xhprof_obj->assignUID();
	
	$dot_script			= $xhprof_callgraph->dot($callstack);
	
	$xhprof_callgraph->graph($dot_script); // Further script execution is terminated.
}

$aggregated_stack = $xhprof_obj->getAggregatedStack();

if (isset($_GET['xhprof']['query']['second_request_id'])) {
	$second_request = $xhprof_data_obj->get($_GET['xhprof']['query']['second_request_id']);
	
	if (!$second_request) {
		\ay\redirect(\ay\REDIRECT_REFERRER, 'Second request data not found.');
	} else if(array_map(function($e){ return $e['callee_id']; }, $request['callstack']) !== array_map(function($e){ return $e['callee_id']; }, $second_request['callstack'])) {
		\ay\redirect(\ay\REDIRECT_REFERRER, 'Cannot compare the two requests. The callstack does not match.');
	} else if($request == $second_request) {
		\ay\redirect(\ay\REDIRECT_REFERRER, 'Cannot compare the request to itself.');
	}
	
	$second_xhprof_obj			= new Model($second_request);
	
	$second_aggregated_stack	= $second_xhprof_obj->getAggregatedStack();
}

require __DIR__ . '/form.inc.tpl.php';

require __DIR__ . '/pie.inc.tpl.php';

/**
 * @param string $name Metrics name.
 * @param string $group Inclusive|Exclusive.
 * @param array $a Present request metrics.
 * @param array $b Request to compare to.
 */
$fn_metrics_column	= function ($name, $group, $a, $b = null) {
	$a = format_metrics($a['metrics'][$group][$name], $name);
	
	$weight = $a['raw'];
	$metrics = '<div class="metrics-parameter">' . $a['formatted'] . '</div>';
	
	if ($b) {
		$b = format_metrics($b['metrics'][$group][$name], $name);
		
		$c = format_metrics($b['raw'] - $a['raw'], $name);
		
		$weight = $c['raw'];
		
		if ($c['raw'] !== 0) {
			$prefix = '';
			$class = 'change-decrease';
			
			if ($c['raw'] > 0) {
				$prefix = '+';
				$class = 'change-increase';
			}
			
			$change_in_percentage	= $a['raw'] && $b['raw'] ? $prefix . sprintf('%.2f', $b['raw']/$a['raw']) . '%' : 'N/A';
		
			$alternate	= array($b['formatted'], $change_in_percentage, $prefix . $c['formatted']);
			
			$metrics	.= '<div class="metrics-parameter ' . $class . '" data-ay-alternate="' . htmlspecialchars(json_encode($alternate), ENT_QUOTES, 'UTF-8') . '">' . $prefix . $c['formatted'] . '</div>';
		}
	}
	
	return '<td class="metrics dual" data-ay-sort-weight="' . $weight . '">' . $metrics . '</td>';
};
?>
<div class="table-wrapper">
	<table class="aggregated-callstack ay-sort">
		<thead class="ay-sticky">
			<tr>
				<th rowspan="2" class="ay-sort">Function</th>
				<th class="ay-sort call-count" rowspan="2">Call Count</th>
				<th class="heading no-sorting" colspan="4">Inclusive</th>
				<th class="heading no-sorting" colspan="4">Exclusive</th>
			</tr>
			<tr>
				<th class="ay-sort regular" data-ay-sort-index="2">Wall Time</th>
				<th class="ay-sort regular" data-ay-sort-index="3">CPU</th>
				<th class="ay-sort regular" data-ay-sort-index="4">Memory Usage</th>
				<th class="ay-sort regular" data-ay-sort-index="5">Peak Memory Usage</th>
				<th class="ay-sort regular" data-ay-sort-index="6">Wall Time</th>
				<th class="ay-sort regular" data-ay-sort-index="7">CPU</th>
				<th class="ay-sort regular" data-ay-sort-index="8">Memory Usage</th>
				<th class="ay-sort regular" data-ay-sort-index="9">Peak Memory Usage</th>
			</tr>
		</thead>
		<tfoot>
			<?php
			foreach($aggregated_stack as $i => $a):				
				
				$b = null;
				
				if (isset($second_aggregated_stack)) {
					// Both aggregated callstacks have exactly the same scheme and order of the execution.
					$b	= $second_aggregated_stack[$i];
				}
				
				$a['metrics']['ct'] = format_metrics($a['metrics']['ct'], 'ct');
				
				?>
				<tr>
					<td><a href="<?=url('function', array('request_id' => $request['id'], 'callee_id' => $a['callee_id']))?>"><?=$a['callee']?></a><?php if($a['group']):?><span class="group g-<?=$a['group']['index']?>"><?=$a['group']['name']?></span><?php endif;?></td>
					<td class="metrics" data-ay-sort-weight="<?=$a['metrics']['ct']['raw']?>"><?=$a['metrics']['ct']['formatted']?></td>
					
					<?=$fn_metrics_column('wt', 'inclusive', $a, $b)?>
					<?=$fn_metrics_column('cpu', 'inclusive', $a, $b)?>
					<?=$fn_metrics_column('mu', 'inclusive', $a, $b)?>
					<?=$fn_metrics_column('pmu', 'inclusive', $a, $b)?>
					
					<?=$fn_metrics_column('wt', 'exclusive', $a, $b)?>
					<?=$fn_metrics_column('cpu', 'exclusive', $a, $b)?>
					<?=$fn_metrics_column('mu', 'exclusive', $a, $b)?>
					<?=$fn_metrics_column('pmu', 'exclusive', $a, $b)?>
				</tr>
				<?php if($i === 0):?>
		</tfoot>
		<tbody>
				<?php endif;?>
			<?php endforeach;?>
		</tbody>
	</table>
</div>