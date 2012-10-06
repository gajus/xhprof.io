<?php
namespace ay\xhprof;

if(empty($_GET['xhprof']['query']['request_id']))
{
	throw new \Exception('Request data can be accessed only through the ID.');
}

$request			= $xhprof_data_obj->get($_GET['xhprof']['query']['request_id']);

if(!$request)
{
	\ay\redirect(AY_REDIRECT_REFERRER, 'Request data not found.');
}

$xhprof_obj			= new Model($request);

if(!empty($_GET['xhprof']['callgraph']))
{
	$xhprof_callgraph	= new Callgraph;
	
	$callstack	= $xhprof_obj->assignUID();
	
	$dot_script			= $xhprof_callgraph->dot($callstack);
	
	$xhprof_callgraph->graph($dot_script);
}

$aggregated_stack	= $xhprof_obj->getAggregatedStack();

if(isset($_GET['xhprof']['query']['second_request_id']))
{
	$second_request				= $xhprof_data_obj->get($_GET['xhprof']['query']['second_request_id']);

	if(!$second_request)
	{
		\ay\redirect(\AY\REDIRECT_REFERRER, 'Second request data not found.');
	}
	else if(array_map(function($e){ return $e['callee_id']; }, $request['callstack']) !== array_map(function($e){ return $e['callee_id']; }, $second_request['callstack']))
	{
		\ay\redirect(\AY\REDIRECT_REFERRER, 'Cannot compare the two requests. The callstack does not match.');
	}
	else if($request == $second_request)
	{
		\ay\redirect(\AY\REDIRECT_REFERRER, 'Cannot compare the request to itself.');
	}
	
	$second_xhprof_obj			= new Model($second_request);
	
	$second_aggregated_stack	= $second_xhprof_obj->getAggregatedStack();
}

require __DIR__ . '/form.inc.tpl.php';

require __DIR__ . '/pie.inc.tpl.php';

$fn_metrics_column	= function($parameter, $group)
{
	// The following globals refer to the variables within the foreach loop.
	// The $a is the present request. [$b the secont request. $c the difference ($b-$a).]
	global $a, $b, $c;
	
	$weight		=  $a['metrics'][$group][$parameter]['raw'];
	$metrics	= '<div class="metrics-parameter">' . $a['metrics'][$group][$parameter]['formatted'] . '</div>';
	
	if(isset($b))
	{
		$weight		= $c['metrics'][$group][$parameter]['raw'];
		
		if($c['metrics'][$group][$parameter]['raw'] !== 0)
		{
			$prefix	= '';
			$class 	= 'change-decrease';
			
			if($c['metrics'][$group][$parameter]['raw'] > 0)
			{
				$prefix	= '+';
				$class 	= 'change-increase';
			}
			
			$change_in_percentage	= $a['metrics'][$group][$parameter]['raw'] && $b['metrics'][$group][$parameter]['raw'] ? $prefix . sprintf('%.2f', $b['metrics'][$group][$parameter]['raw']/$a['metrics'][$group][$parameter]['raw']) . '%' : 'N/A';
		
			$alternate	= array($b['metrics'][$group][$parameter]['formatted'], $change_in_percentage, $prefix . $c['metrics'][$group][$parameter]['formatted']);
			
			$metrics	.= '<div class="metrics-parameter ' . $class . '" data-ay-alternate="' . htmlspecialchars(json_encode($alternate), ENT_QUOTES, 'UTF-8') . '">' . $prefix . $c['metrics'][$group][$parameter]['formatted'] . '</div>';
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
				
				if(isset($second_aggregated_stack))
				{
					// Both aggregated callstacks have exactly the same scheme and order of the execution.
					$b	= $second_aggregated_stack[$i];
					
					// calculate the relative change from A to B.
					$c	= array
					(
						'metrics'	=> array
						(
							'ct'		=> $b['metrics']['ct']-$a['metrics']['ct']['raw'],
							'inclusive'	=> array
							(
								'wt'	=> $b['metrics']['inclusive']['wt']-$a['metrics']['inclusive']['wt'],
								'cpu'	=> $b['metrics']['inclusive']['cpu']-$a['metrics']['inclusive']['cpu'],
								'mu'	=> $b['metrics']['inclusive']['mu']-$a['metrics']['inclusive']['mu'],
								'pmu'	=> $b['metrics']['inclusive']['pmu']-$a['metrics']['inclusive']['pmu']
							),
							'exclusive'	=> array
							(
								'wt'	=> $b['metrics']['exclusive']['wt']-$a['metrics']['exclusive']['wt'],
								'cpu'	=> $b['metrics']['exclusive']['cpu']-$a['metrics']['exclusive']['cpu'],
								'mu'	=> $b['metrics']['exclusive']['mu']-$a['metrics']['exclusive']['mu'],
								'pmu'	=> $b['metrics']['exclusive']['pmu']-$a['metrics']['exclusive']['pmu']
							)
						)
					);
					
					$b['metrics']				= format_metrics($b['metrics']);
					$b['metrics']['inclusive']	= format_metrics($b['metrics']['inclusive']);
					$b['metrics']['exclusive']	= format_metrics($b['metrics']['exclusive']);
					
					$c['metrics']				= format_metrics($c['metrics']);
					$c['metrics']['inclusive']	= format_metrics($c['metrics']['inclusive']);
					$c['metrics']['exclusive']	= format_metrics($c['metrics']['exclusive']);
				}
				
				$a['metrics']				= format_metrics($a['metrics']);
				$a['metrics']['inclusive']	= format_metrics($a['metrics']['inclusive']);
				$a['metrics']['exclusive']	= format_metrics($a['metrics']['exclusive']);
				
				?>
				<tr>
					<td><a href="<?=url('function', array('request_id' => $request['id'], 'callee_id' => $a['callee_id']))?>"><?=$a['callee']?></a><?php if($a['group']):?><span class="group g-<?=$a['group']['index']?>"><?=$a['group']['name']?></span><?php endif;?></td>
					<td class="metrics" data-ay-sort-weight="<?=$a['metrics']['ct']['raw']?>"><?=$a['metrics']['ct']['formatted']?></td>
					
					<?=$fn_metrics_column('wt', 'inclusive')?>
					<?=$fn_metrics_column('cpu', 'inclusive')?>
					<?=$fn_metrics_column('mu', 'inclusive')?>
					<?=$fn_metrics_column('pmu', 'inclusive')?>
					
					<?=$fn_metrics_column('wt', 'exclusive')?>
					<?=$fn_metrics_column('cpu', 'exclusive')?>
					<?=$fn_metrics_column('mu', 'exclusive')?>
					<?=$fn_metrics_column('pmu', 'exclusive')?>
				</tr>
				<?php if($i === 0):?>
		</tfoot>
		<tbody>
				<?php endif;?>
			<?php endforeach;?>
		</tbody>
	</table>
</div>