<?php
namespace ay\xhprof;

if(!\ay\error_present())
{
	$data	= $xhprof_data_obj->getRequests($_GET['xhprof']['query']);

	if(empty($data))
	{
		\ay\message('No results matching your search were found.', 'notice');
	}
}

require __DIR__ . '/form.inc.tpl.php';

if(empty($data['discrete']))
{
	return;
}

require __DIR__ . '/summary.inc.tpl.php';

require __DIR__ . '/histogram.inc.tpl.php';
?>
<div class="table-wrapper">
	<table class="requests ay-sort">
		<thead class="ay-sticky">
			<tr>
				<th class="ay-sort ay-sort-desc request-id" rowspan="2">Request ID</th>
				<th class="ay-sort host" rowspan="2">Host</th>
				<th class="ay-sort" rowspan="2">URI</th>
				<th class="ay-sort request-method" rowspan="2">Request Method</th>
				<th class="heading" colspan="4">Metrics</th>
				<th class="ay-sort date-time" rowspan="2" data-ay-sort-index="8">Request Time</th>
			</tr>
			<tr>
				
				<th class="ay-sort" data-ay-sort-index="4">Wall Time</th>
				<th class="ay-sort" data-ay-sort-index="5">CPU</th>
				<th class="ay-sort" data-ay-sort-index="6">Memory Usage</th>
				<th class="ay-sort" data-ay-sort-index="7">Peak Memory Usage</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($data['discrete'] as $e):
				$e	= format_metrics($e);			
			?>
			<tr data-request-id="<?=$e['request_id']?>">
				<td><a href="<?=url('request', array('request_id' => $e['request_id']))?>"><?=$e['request_id']?></a></td>
				<td><a href="<?=url('uris', array('host_id' => $e['host_id']))?>"><?=htmlspecialchars($e['host'])?></a></td>
				<td><a href="<?=url('uris', array('host_id' => $e['host_id'], 'uri_id' => $e['uri_id']))?>"><?=htmlspecialchars($e['uri'])?></a></td>
				<td><?=$e['request_method']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['wt']['raw']?>"><?=$e['wt']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['cpu']['raw']?>"><?=$e['cpu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['mu']['raw']?>"><?=$e['mu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['pmu']['raw']?>"><?=$e['pmu']['formatted']?></td>
				<td data-ay-sort-weight="<?=$e['request_timestamp']?>"><?=date(\ay\FORMAT_DATETIME, $e['request_timestamp'])?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
</div>