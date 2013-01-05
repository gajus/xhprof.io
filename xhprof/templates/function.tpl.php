<?php
namespace ay\xhprof;

if(!isset($_GET['xhprof']['query']['request_id'], $_GET['xhprof']['query']['callee_id']))
{
	throw new \Exception('Missing required parameters.');
}

$request			= $xhprof_data_obj->get($_GET['xhprof']['query']['request_id']);

if(!$request)
{
	\ay\redirect(\ay\REDIRECT_REFERRER, 'Request data not found.');
}

$xhprof_obj			= new Model($request);

$aggregated_family	= $xhprof_obj->getFamily($_GET['xhprof']['query']['callee_id']);

if(!$aggregated_family)
{
	throw new \Exception('Function is not in the callstack.');
}

$table_row			= function($e) use ($request)
{
	$e['metrics']				= format_metrics($e['metrics']);
	$e['metrics']['inclusive']	= format_metrics($e['metrics']['inclusive']);
	$e['metrics']['exclusive']	= format_metrics($e['metrics']['exclusive']);
	$e['metrics']['relative']	= array();
	
	foreach($e['metrics']['inclusive'] as $name => $data)
	{
		$e['metrics']['relative'][$name]	= format_number($request['total'][$name] == 0 ? 0 : $e['metrics']['inclusive'][$name]['raw']*100/$request['total'][$name]);
	}	
	
	?>
	<tr<?php if($e['internal']):?> class="internal"<?php endif;?>>
		<td><a href="<?=url('function', array('request_id' => $request['id'], 'callee_id' => $e['callee_id']))?>"><?=$e['callee']?></a></td>
		
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['ct']['raw']?>"><?=$e['metrics']['ct']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['ct']['raw']*100/$request['total']['ct']?>"><?=format_number($e['metrics']['ct']['raw']*100/$request['total']['ct'])?>%</td>
		
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['wt']['raw']?>"><?=$e['metrics']['exclusive']['wt']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['wt']['raw']?>"><?=$e['metrics']['inclusive']['wt']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['relative']['wt']?>"><?=$e['metrics']['relative']['wt']?>%</td>
		
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['cpu']['raw']?>"><?=$e['metrics']['exclusive']['cpu']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['cpu']['raw']?>"><?=$e['metrics']['inclusive']['cpu']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['relative']['cpu']?>"><?=$e['metrics']['relative']['cpu']?>%</td>
		
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['mu']['raw']?>"><?=$e['metrics']['exclusive']['mu']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['mu']['raw']?>"><?=$e['metrics']['inclusive']['mu']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['relative']['mu']?>"><?=$e['metrics']['relative']['mu']?>%</td>
		
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['pmu']['raw']?>"><?=$e['metrics']['exclusive']['pmu']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['pmu']['raw']?>"><?=$e['metrics']['inclusive']['pmu']['formatted']?></td>
		<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['relative']['pmu']?>"><?=$e['metrics']['relative']['pmu']?>%</td>
	</tr>
	<?php
};

?>
<div class="table-wrapper">
	<table class="function-breakdown ay-sort">
		<thead>
			<tr>
				<th class="ay-sort" rowspan="3">Function</th>
				<th class="heading call-count" colspan="2">Call Count</th>
				<th class="heading" colspan="3">Wall Time</th>
				<th class="heading" colspan="3">CPU</th>
				<th class="heading" colspan="3">Memory Usage</th>
				<th class="heading" colspan="3">Peak Memory Usage</th>
			</tr>
			<tr>
				<th class="regular ay-sort" data-ay-sort-index="1" rowspan="2">Qty.</th>
				<th class="regular ay-sort" data-ay-sort-index="2" rowspan="2">%</th>
				
				<th class="regular" colspan="2">Time</th>
				<th class="regular ay-sort" data-ay-sort-index="5" rowspan="2">%</th>
				
				<th class="regular" colspan="2">Time</th>
				<th class="regular ay-sort" data-ay-sort-index="8" rowspan="2">%</th>
				
				<th class="regular" colspan="2">Memory</th>
				<th class="regular ay-sort" data-ay-sort-index="11" rowspan="2">%</th>
				
				<th class="regular" colspan="2">Memory</th>
				<th class="regular ay-sort" data-ay-sort-index="14" rowspan="2">%</th>
			</tr>
			<tr>
				<th class="ay-sort" data-ay-sort-index="3">Excl.</th>
				<th class="ay-sort" data-ay-sort-index="4">Incl.</th>
				
				<th class="ay-sort" data-ay-sort-index="6">Excl.</th>
				<th class="ay-sort" data-ay-sort-index="7">Incl.</th>
				
				<th class="ay-sort" data-ay-sort-index="9">Excl.</th>
				<th class="ay-sort" data-ay-sort-index="10">Incl.</th>
				
				<th class="ay-sort" data-ay-sort-index="12">Excl.</th>
				<th class="ay-sort" data-ay-sort-index="13">Incl.</th>
			</tr>
		</thead>
		<?php if(!empty($aggregated_family['callers'])):?>
		<tbody class="ay-sort-no">
			<tr>
				<th colspan="15">Parent Function</th>
			</tr>
			<?php foreach($aggregated_family['callers'] as $caller):?>
			<?=$table_row($caller)?>
			<?php endforeach;?>
		</tbody>
		<?php endif;?>
		
		<tbody class="ay-sort-no">
			<tr>
				<th colspan="15">Current Function</th>
			</tr>
			<?=$table_row($aggregated_family['callee'])?>
		</tbody>
		
		<?php if(!empty($aggregated_family['children'])):?>
		<tbody class="ay-sort-no">
			<tr>
				<th colspan="15">Children</th>
			</tr>
			<?php foreach($aggregated_family['children'] as $child):?>
			<?=$table_row($child)?>
			<?php endforeach;?>
		</tbody>
		<?php endif;?>
	</table>
</div>