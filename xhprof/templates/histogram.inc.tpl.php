<?php
namespace ay\xhprof;
?>
<div class="histogram-layout">
	<div class="left">
		<div class="column">
			<div class="label">Number of Requests by Date</div>
			<svg class="histogram-date"></svg>
		</div>
	</div>
	<div class="right">
		<div class="column">
			<div class="label">CPU</div>
			<svg class="histogram-cpu"></svg>
		</div>
		<div class="column">
			<div class="label">Wall Time</div>
			<svg class="histogram-wt"></svg>
		</div>
	</div>
	<div class="center">
		<div class="column">
			<div class="label">Memory Usage</div>
			<svg class="histogram-mu"></svg>
		</div>
		<div class="column">
			<div class="label">Peak Memory Usage</div>
			<svg class="histogram-pmu"></svg>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	var requests	= <?php
	echo json_encode(array_map(function($e){
		return array($e['request_id'], $e['request_timestamp'], $e['wt'], $e['cpu'], $e['mu'], $e['pmu']);
	}, $data['discrete']));
	?>;
	
	var format	= 
	{
		bytes: function(number)
		{
			var precision	= 2;
			
		    var base		= Math.log(Math.abs(number)) / Math.log(1024);
		    var suffixes	= ['b', 'k', 'M', 'G', 'T'];   
		
		   return (Math.pow(1024, base - Math.floor(base))).toFixed(precision) + ' ' + suffixes[Math.floor(base)];
		},
		microseconds: function(number)
		{
			var pad		= false;
			var suffix	= 'µs';
		
			if (number >= 1000)
			{
				number	= number / 1000;
				suffix	= 'ms';
				
				if (number >= 1000)
				{
					pad		= true;
					
					number	= number / 1000;
					suffix	= 's';
					
					if (number >= 60)
					{
						number	= number / 60;
						suffix	= 'm';
					}
				}
			}
			
			return pad ? number.toFixed(2) + ' ' + suffix : number + ' ' + suffix;
		}
	};
	
	var filter	= crossfilter(requests);
	
	var data	=
	{
		id:
		{
			dimension: filter.dimension(function(d){ return d[0]; })
		},
		date:
		{
			dimension: filter.dimension(function(d){ return new Date(d[1]*1000); })
		},
		wt:
		{
			dimension: filter.dimension(function(d){ return d[2]; })
		},
		cpu:
		{
			dimension: filter.dimension(function(d){ return d[3]; })
		},
		mu:
		{
			dimension: filter.dimension(function(d){ return d[4]; })
		},
		pmu:
		{
			dimension: filter.dimension(function(d){ return d[5]; })
		}
	};
	
	var date_scale	= date_scale	= {name: 'day', interval: 1000*3600*24};
	var days		= (requests[0][1]-requests[requests.length-1][1])/(3600*24);
	
	if(days < 1)
	{
		date_scale	= {name: 'minute', interval: 1000*60};
	}
	else if(days < 10)
	{
		date_scale	= {name: 'hour', interval: 1000*3600};
	}
	
	data.id.group	= data.id.dimension.group();
	data.date.group	= data.date.dimension.group(function(d){ return d3.time[date_scale.name](d); });
	data.wt.group	= data.wt.dimension.group(function(d){ return Math.floor(d / 1000)*1000; });
	data.cpu.group	= data.cpu.dimension.group(function(d){ return Math.floor(d / 100)*100; });
	data.mu.group	= data.mu.dimension.group(function(d){ return Math.floor(d / 1000)*1000; });
	data.pmu.group	= data.pmu.dimension.group(function(d){ return Math.floor(d / 1000)*1000; });
	
	var date	= ay_histogram('histogram-date', data.date, {margin: [10, 10], bin_width: date_scale.interval});
	var wt		= ay_histogram('histogram-wt', data.wt, {margin: [10, 10], bin_width: 1000, x_axis_format: format.microseconds, tick_width: 100});
	var cpu		= ay_histogram('histogram-cpu', data.cpu, {margin: [10, 10], bin_width: 100, x_axis_format: format.microseconds, tick_width: 100});
	var mu		= ay_histogram('histogram-mu', data.mu, {margin: [10, 10], bin_width: 1000, x_axis_format: format.bytes, tick_width: 100});
	var pmu		= ay_histogram('histogram-pmu', data.pmu, {margin: [10, 10], bin_width: 1000, x_axis_format: format.bytes, tick_width: 100});
	
	var all			= data.id.group.all();
	
	var table_rows	= $('table.requests tbody tr');
	
	var table		= {render: function(stage){
		
		if(stage != 1)
		{
			return;
		}
		
		table_rows.hide();
		
		var matches	= [];
		
		for(var i = 0, j = all.length; i < j; i++)
		{
			if(all[i].value)
			{
				matches.push(all[i].key);
			}
		}
		
		table_rows.filter(function(){
			return matches.indexOf($(this).data('request-id')) != -1;
		}).show();
	}};
	
	date.setRelations([wt,cpu,mu,pmu,table]);
	wt.setRelations([date,cpu,mu,pmu,table]);
	cpu.setRelations([date,wt,mu,pmu,table]);
	mu.setRelations([date,wt,cpu,pmu,table]);
	pmu.setRelations([date,wt,cpu,mu,table]);
});
</script>