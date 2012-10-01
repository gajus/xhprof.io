<?php
if(empty($_GET['xhprof']['query']['request_id']))
{
	throw new XHProfException('Request data can be accessed only through the ID.');
}

$request			= $xhprof_data_obj->get($_GET['xhprof']['query']['request_id']);

if(!$request)
{
	ay_redirect(AY_REDIRECT_REFERRER, 'Request data not found.');
}

$xhprof_obj			= new XHProf($request);

if(!empty($_GET['xhprof']['callgraph']))
{
	$xhprof_callgraph	= new XHProfCallgraph;


	$callstack			= $xhprof_obj->assignUID($request['callstack']);
	
	
	$dot_script			= $xhprof_callgraph->dot($callstack);
	
	$xhprof_callgraph->graph($dot_script);
}

$aggregated_stack	= $xhprof_obj->getAggregatedStack();

?>
<div class="table-wrapper">
	<table class="aggregated-callstack ay-sort">
		<thead>
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
		<tbody>
			<?php foreach($aggregated_stack as $e):
			
				$e['metrics']				= xhprof_format_metrics($e['metrics']);
				$e['metrics']['inclusive']	= xhprof_format_metrics($e['metrics']['inclusive']);
				$e['metrics']['exclusive']	= xhprof_format_metrics($e['metrics']['exclusive']);
			?>
			<tr>
				<td><a href="<?=xhprof_url('function',array('request_id' => $request['id'], 'callee_id' => $e['callee_id']))?>"><?=$e['callee']?></a><?php if($e['group']):?><span class="group g-<?=$e['group']['index']?>"><?=$e['group']['name']?></span><?php endif;?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['ct']['raw']?>"><?=$e['metrics']['ct']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['wt']['raw']?>"><?=$e['metrics']['inclusive']['wt']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['cpu']['raw']?>"><?=$e['metrics']['inclusive']['cpu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['mu']['raw']?>"><?=$e['metrics']['inclusive']['mu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['inclusive']['pmu']['raw']?>"><?=$e['metrics']['inclusive']['pmu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['wt']['raw']?>"><?=$e['metrics']['exclusive']['wt']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['cpu']['raw']?>"><?=$e['metrics']['exclusive']['cpu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['mu']['raw']?>"><?=$e['metrics']['exclusive']['mu']['formatted']?></td>
				<td class="metrics" data-ay-sort-weight="<?=$e['metrics']['exclusive']['pmu']['raw']?>"><?=$e['metrics']['exclusive']['pmu']['formatted']?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
</div>