<?php
ay_message('Pie charts are underway. If you have ideas how to make pie charts use less space, yet keeping them informative, kindly express your ideas to <a href="mailto:g.kuizinas@anuary.com">g.kuizinas@anuary.com</a> or contribute to <a href="https://github.com/anuary/ay-pie-chart">pie-chart</a> Git.', AY_MESSAGE_NOTICE);

$grouped_stack	= $xhprof_obj->getGroupedStack();

$sort_by		= function($name) use ($grouped_stack)
{
	usort($grouped_stack, function($a, $b) use ($name){
		if($a['metrics'][$name] == $b['metrics'][$name])
		{
			return 0;
		}
		
		return $a['metrics'][$name] > $b['metrics'][$name] ? -1 : 1;
	});
	
	return $grouped_stack;
};

$get_values		= function($name, $limit = 5) use ($sort_by)
{
	$data		= array_map(function($e) use ($name) { $e['value'] = $e['metrics'][$name]; unset($e['metrics']); return $e; }, $sort_by($name));
	
	$displayed	= array_slice($data, 0, $limit);
	
	$other		= array
	(
		'index'	=> $limit+1,
		'name'	=> 'other',
		'value'	=> array_sum(array_map(function($e){ return $e['value']; }, array_slice($data, $limit)))
	);
	
	array_push($displayed, $other);
	
	$displayed	= array_filter($displayed, function($e){
		return $e['value'] > 0;
	});
	
	return $displayed;
};
?>
<div class="pie-layout">
	<svg class="pie-ct"></svg>
	<svg class="pie-wt"></svg>
	<svg class="pie-cpu"></svg>
	<svg class="pie-mu"></svg>
	<svg class="pie-pmu"></svg>
</div>
<script type="text/javascript">
$(function(){
	var radius_inner	= 0;
	var radius_outer	= 50;

	ay_pie_chart('pie-ct', <?=json_encode($get_values('ct'))?>, {radius_inner: radius_inner, radius_outer: radius_outer});
	ay_pie_chart('pie-wt', <?=json_encode($get_values('wt'))?>, {radius_inner: radius_inner, radius_outer: radius_outer});
	ay_pie_chart('pie-cpu', <?=json_encode($get_values('cpu'))?>, {radius_inner: radius_inner, radius_outer: radius_outer});
	ay_pie_chart('pie-mu', <?=json_encode($get_values('mu'))?>, {radius_inner: radius_inner, radius_outer: radius_outer});
	ay_pie_chart('pie-pmu', <?=json_encode($get_values('pmu'))?>, {radius_inner: radius_inner, radius_outer: radius_outer});
});
</script>