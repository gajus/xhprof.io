<?php
namespace ay\xhprof;

if (!\ay\error_present()) {
	$data	= $xhprof_data_obj->getUris($_GET['xhprof']['query']);
	
	if (empty($data)) {
		\ay\message('No results matching your search were found.', 'notice');
	}
}

require __DIR__ . '/form.inc.tpl.php';

if (empty($data['discrete'])) {
	return;
}

require __DIR__ . '/summary.inc.tpl.php';
?>
<div class="table-wrapper">
	<table class="uris ay-sort">
		<thead class="ay-sticky">
			<tr>
				<?php if(empty($_GET['ay']['query']['host_id'])):?>
				<th class="ay-sort ay-sort-asc host" rowspan="2">Host</th>
				<?php endif;?>
				<th class="ay-sort uri" rowspan="2">URI</th>
				<th class="ay-sort regular" rowspan="2">Request Count</th>
				<th class="heading" colspan="4">Average</th>
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
				$e	= format_metrics($e);			
			?>
			<tr>
				<?php if(empty($_GET['ay']['query']['host_id'])):?>
				<td><a href="<?=url('uris', array('host_id' => $e['host_id']))?>"><?=htmlspecialchars($e['host'])?></a></td>
				<?php endif;?>
				<td><a href="<?=url('requests', array('host_id' => $e['host_id'], 'uri_id' => $e['uri_id']))?>"><?=htmlspecialchars($e['uri'])?></a></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['request_count']['raw']?>"><?=$e['request_count']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['wt']['raw']?>"><?=$e['wt']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['cpu']['raw']?>"><?=$e['cpu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['mu']['raw']?>"><?=$e['mu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['pmu']['raw']?>"><?=$e['pmu']['formatted']?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
</div>