<?php
namespace ay\xhprof;

$aggregated_metrics	= $xhprof_data_obj->getMetricsSummary();

$labels		= array
(
	'wt'	=> array('label' => 'Wall Time', 'format' => 'ay\xhprof\format_microseconds'),
	'cpu'	=> array('label' => 'CPU Time', 'format' => 'ay\xhprof\format_microseconds'),
	'mu'	=> array('label' => 'Memory Usage', 'format' => 'ay\xhprof\format_bytes'),
	'pmu'	=> array('label' => 'Peak Memory Usage', 'format' => 'ay\xhprof\format_bytes'),
);
?>

<div class="table-wrapper" id="metrics-summary">
	<table class="metrics-summary">
		<thead>
			<tr>
				<th class="name">Parameter</th>
				<th>Min.</th>
				<th>Max.</th>
				<th>Avg.</th>
				<th>approx. 95th percentile</th>
				<th>Mode</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach(array_intersect_key($aggregated_metrics, $labels) as $k => $v):?>
			<tr>
				<th><?=$labels[$k]['label']?></th>
				<td><?=call_user_func($labels[$k]['format'], $v['min'])?></td>
				<td><?=call_user_func($labels[$k]['format'], $v['max'])?></td>
				<td><?=call_user_func($labels[$k]['format'], $v['avg'])?></td>
				<td><?=call_user_func($labels[$k]['format'], $v['95th'])?></td>
				<td><?=call_user_func($labels[$k]['format'], $v['mode'])?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
		<tfoot>
			<tr>
				<th>Dataset Size</th>
				<td colspan="5"><?=number_format($aggregated_metrics['request_count'])?></td>
			</tr>
		</tfoot>
	</table>
</div>