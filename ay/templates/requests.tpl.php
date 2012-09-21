<?php
if(!ay_error_present())
{
	$data	= $xhprof_data_obj->getRequests($_GET['xhprof']['query']);

	if(empty($data))
	{
		ay_message('No results matching your search were found.', AY_MESSAGE_NOTICE);
	}
}

require __DIR__ . '/form.inc.tpl.php';

if(empty($data['discrete']))
{
	return;
}

$data['aggregated']	= xhprof_format_metrics($data['aggregated']);


require __DIR__ . '/summary.inc.tpl.php';
?>
<div class="table-wrapper">
	<table class="requests ay-sort">
		<thead class="ay-sticky">
			<tr>
				<th class="ay-sort host" rowspan="2">Host</th>
				<th class="ay-sort" rowspan="2" data-ay-sort-index="1">URI</th>
				<th class="ay-sort request-method" rowspan="2">Request Method</th>
				<th class="heading" colspan="4">Metrics</th>
				<th class="ay-sort ay-sort-desc date-time" rowspan="2" data-ay-sort-index="6">Request Time</th>
			</tr>
			<tr>
				
				<th class="ay-sort" data-ay-sort-index="3">Wall Time</th>
				<th class="ay-sort" data-ay-sort-index="4">CPU</th>
				<th class="ay-sort" data-ay-sort-index="5">Memory Usage</th>
				<th class="ay-sort" data-ay-sort-index="6">Peak Memory Usage</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($data['discrete'] as $e):
				$e	= xhprof_format_metrics($e);			
			?>
			<tr>
				<td><a href="<?=xhprof_url('uris', array('host_id' => $e['host_id']))?>"><?=htmlspecialchars($e['host'])?></a></td>
				<td><a href="<?=xhprof_url('request', array('request_id' => $e['request_id']))?>"><?=htmlspecialchars($e['uri'])?></a></td>
				<td><?=$e['request_method']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['wt']['raw']?>"><?=$e['wt']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['cpu']['raw']?>"><?=$e['cpu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['mu']['raw']?>"><?=$e['mu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['pmu']['raw']?>"><?=$e['pmu']['formatted']?></td>
				<td data-ay-sort-weight="<?=$e['request_timestamp']?>"><?=date(AY_FORMAT_DATETIME, $e['request_timestamp'])?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3"></td>
				<td class="metrics"><?=$data['aggregated']['wt']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['cpu']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['mu']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['pmu']['formatted']?></td>
				<td></td>
			</tr>
		</tfoot>
	</table>
</div>