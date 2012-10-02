<?php
if(!ay_error_present())
{
	$data	= $xhprof_data_obj->getHosts($_GET['xhprof']['query']);
}

require __DIR__ . '/form.inc.tpl.php';

if(empty($data['discrete']))
{
	ay_message('No results matching your search were found.', AY_MESSAGE_NOTICE);

	return;
}
	
$data['aggregated']	= xhprof_format_metrics($data['aggregated']);

require __DIR__ . '/summary.inc.tpl.php';
?>
<div class="table-wrapper">
	<table class="hosts ay-sort">
		<thead class="ay-sticky">
			<tr>
				<th class="ay-sort ay-sort-asc host" rowspan="2">Host</th>
				<th class="ay-sort request-count" rowspan="2">Request Count</th>
				<th class="heading" colspan="4">Average</th>
			</tr>
			<tr>
				<th class="ay-sort" data-ay-sort-index="2">Wall Time</th>
				<th class="ay-sort" data-ay-sort-index="3">CPU</th>
				<th class="ay-sort" data-ay-sort-index="4">Memory Usage</th>
				<th class="ay-sort" data-ay-sort-index="5">Peak Memory Usage</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($data['discrete'] as $e):
				$e	= xhprof_format_metrics($e);
			?>
			<tr>
				<td><a href="<?=xhprof_url('uris', array('host_id' => $e['host_id']))?>"><?=htmlspecialchars($e['host'])?></a></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['request_count']['raw']?>"><?=$e['request_count']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['wt']['raw']?>"><?=$e['wt']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['cpu']['raw']?>"><?=$e['cpu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['mu']['raw']?>"><?=$e['mu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['pmu']['raw']?>"><?=$e['pmu']['formatted']?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
		<tfoot>
			<tr>
				<td>
					<?php if(!empty($_GET['xhprof']['query'])):?>
						<a href="<?=xhprof_url('uris', $_GET['xhprof']['query'])?>">View URIs with the same filters</a>
						<a href="<?=xhprof_url('requests', $_GET['xhprof']['query'])?>">View requests with the same filters</a>
					<?php endif;?>
				</td>
				<td class="metrics"><?=$data['aggregated']['request_count']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['wt']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['cpu']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['mu']['formatted']?></td>
				<td class="metrics"><?=$data['aggregated']['pmu']['formatted']?></td>
			</tr>
		</tfoot>
	</table>
</div>