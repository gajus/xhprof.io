<?php
namespace xhprof;

if(!isset($_GET['xhprof']['query']['request_id'], $_GET['xhprof']['query']['callee_id']))
{
	throw new \Exception('Missing required arguments.');
}

$request			= $xhprof_data_obj->get($_GET['xhprof']['query']['request_id']);

$xhprof_obj			= new Model($request);

$aggregated_stack	= $xhprof_obj->getAggregatedStack($_GET['xhprof']['query']['callee_id']);

if(empty($aggregated_stack))
{
	throw new \Exception('This function is expected to always return data.');
}

$aggregated_stack	= array_map(function($e) use ($request)
{
	$e['metrics']				= format_metrics($e['metrics']);
	$e['metrics']['inclusive']	= format_metrics($e['metrics']['inclusive']);
	$e['metrics']['exclusive']	= format_metrics($e['metrics']['exclusive']);
	$e['metrics']['relative']	= array();
	
	foreach($e['metrics']['inclusive'] as $name => $data)
	{
		$e['metrics']['relative'][$name]	= format_number($request['total'][$name] == 0 ? 0 : $e['metrics']['inclusive'][$name]['raw']*100/$request['total'][$name]);
	}	
	
	return $e;
}, $aggregated_stack);

if($aggregated_stack[0]['callee_id'] == $_GET['xhprof']['query']['callee_id'])
{
	$callee	= $aggregated_stack[0];
}
else if($aggregated_stack[0]['caller_id'] === NULL)
{
	$callee	= $aggregated_stack[0];
}
else
{
	$caller	= $aggregated_stack[0];
	$callee	= $aggregated_stack[1];
}

$children		= array();
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
		<?php foreach($aggregated_stack as $e):?>
			<?php if(!empty($caller) && $caller == $e):?>
			<tbody class="ay-sort-no">
				<tr>
					<th colspan="15">Parent Function</th>
				</tr>
			<?php elseif($callee == $e):?>
			<tbody class="ay-sort-no">
				<tr>
					<th colspan="15">Current Function</th>
				</tr>
			<?php
			else:
				ob_start();
			endif;?>
			<tr<?php if($e['internal']):?> class="internal"<?php endif;?>>
				<td><a href="<?=url('function', array('request_id' => $request['id'], 'callee_id' => $e['callee_id']))?>"><?=$e['callee']?></a></td>
				
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['ct']['raw']?>"><?=$e['metrics']['ct']['raw']?></td>
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
			<?php if(!empty($caller) && $caller == $e || $callee == $e):?>
			</tbody>
			<?php else:
			$children[]	= ob_get_clean();
			endif;?>
		<?php endforeach;?>
		<?php if(!empty($children)):?>
		<tbody>
			<tr class="ay-sort-top">
				<th colspan="15">Children</th>
			</tr>
			<?=implode('', $children)?>
		</tbody>
		<?php endif;?>
	</table>
</div>