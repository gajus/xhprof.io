<?php
namespace ay\xhprof;

$grouped_stack	= $xhprof_obj->getGroupedStack();

$sort_stack_by	= function($metric_name) use ($grouped_stack)
{
	usort($grouped_stack, function($a, $b) use ($metric_name){
		return $b['metrics'][$metric_name] - $a['metrics'][$metric_name];
	});
	
	return $grouped_stack;
};

$get_values		= function($metric_name, $limit = 5) use ($sort_stack_by)
{
	$data			= $sort_stack_by($metric_name);
	
	$total_value	= 0;
	
	foreach($data as &$e)
	{
		$e['value']			= $e['metrics'][$metric_name];
		
		$total_value		+= $e['value'];
		
		unset($e['metrics'], $e);
	}
	
	return $data;
};
?>
<div class="pie-layout">
	<div class="chart">
		<svg class="pie-ct"></svg>
	</div>
	<div class="chart">
		<svg class="pie-wt"></svg>
	</div>
	<div class="chart">
		<svg class="pie-cpu"></svg>
	</div>
	<div class="chart">
		<svg class="pie-mu"></svg>
	</div>
	<div class="chart">
		<svg class="pie-pmu"></svg>
	</div>
</div>
<script type="text/javascript">
$(function(){
	var options = {radius_inner: 0, radius_outer: 50, group_data: 5, radius_label: 80};

	ay.pie_chart('pie-ct', <?=json_encode($get_values('ct'))?>, options);
	ay.pie_chart('pie-wt', <?=json_encode($get_values('wt'))?>, options);
	ay.pie_chart('pie-cpu', <?=json_encode($get_values('cpu'))?>, options);
	ay.pie_chart('pie-mu', <?=json_encode($get_values('mu'))?>, options);
	ay.pie_chart('pie-pmu', <?=json_encode($get_values('pmu'))?>, options);
});
</script>