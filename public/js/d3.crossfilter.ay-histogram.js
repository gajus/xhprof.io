/**
 * Histogram v0.0.2
 * https://github.com/gajus/interdependent-interactive-histograms
 *
 * This function utilises d3.js (http://d3js.org/) and Crossfilter (http://square.github.com/crossfilter/)
 * to create interdependent, interactive histograms. The beauty of the code comes from the flexibility of
 * the input data. The crossfilter can have an arbitrary number of groups, of any size.
 *
 * Licensed under the BSD.
 * https://github.com/gajus/interdependent-interactive-histograms/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 * 
 * param	string	name	Unique name of the SVG.
 * param	object	data	{group: [crossfilter group], dimension: [crossfilter dimension]}
 * param	object	options	{
 *								margin: [(int) vertical, (int) horizontal], // required
 *								bin_width: (int), // required for d3.linear.scale; defauls to day-length otherwise.
 *							  	x_axis_format: (function) // optional. The horizontal axis tick format
 *							  	tick_width: (int) // optional. The distance between each label on the horizontal axis.
 *							}
 */
var ay_histogram	= function(name, data, options)
{
	'use strict';

	var svg		= d3
		.select('svg.' + name);
	
	var dimensions	=
	{
		brush:
		{
			bar: { width: 10 }
		},
		scrollbar: { height: 10 },
		axis:
		{
			x: { height: 20 }
		}
	};
	
	dimensions.graph	=
	{
		width: parseInt((svg[0][0].clientWidth || svg[0][0].parentNode.clientWidth)-options.margin[0]*2),
		height: parseInt((svg[0][0].clientHeight || svg[0][0].parentNode.clientHeight)-options.margin[1]*2-dimensions.axis.x.height)-1
	};
	
	var all		= data.group.all();
	
	var brush	=
	{
		events:
		{
			brush: function()
			{
				var extent 	= brush.d3.extent();
				
				// round the brush movement to the bin width
				var scale	= extent.map(x.scale).map(function(d){ return Math.ceil(d/dimensions.brush.bar.width)*dimensions.brush.bar.width; });
				
				// for whatever reason the x.scale.invert sometimes returns number such as [400, 700.0000000000001], thus the .map(Math.floor)
				var extent	= scale.map(x.scale.invert).map(Math.floor);
				
				if(scale[0] == scale[1])
				{
					data.dimension.filterAll();
				}
				else
				{
					data.dimension.filterRange(extent);
				}
				
				// limit the brush movement to the bin width
				brush.g.call(brush.d3.extent(extent));
				
				// adjust the clipping mask to the brush tool
				clippath_brush
					.attr('x', scale[0])
					.attr('width', scale[1]-scale[0]);
				
				// update any histograms that share the same crossfilter
				if(relations)
				{
					for(var i = 0, j = relations.length; i < j; i++)
					{
						relations[i].render(0);
					}
				}
			},
			brushend: function()
			{
				// update any histograms that share the same crossfilter
				if(relations)
				{
					for(var i = 0, j = relations.length; i < j; i++)
					{
						relations[i].render(1);
					}
				}
			}
		},
		// A helper method to generate the handle-bars for the brush tool.
		// This is a slightly modified version taken out of the Mike Bostock (http://square.github.com/crossfilter/) code.
		resize_path: function(d)
		{
			var e = +(d == "e"),
				x = e ? -1 : 1,
				y = dimensions.graph.height / 3;
			
			var b = e ? 0 : 1;
			
			return "M" + (.5 * x) + "," + (y)
				+ "A5,5 0 0 " + b + " " + (6.5 * x) + "," + (y + 6)
				+ "V" + (2 * y - 6)
				+ "A5,5 0 0 " + b + " " + (.5 * x) + "," + (2 * y)
				+ "Z"
				+ "M" + (2.5 * x) + "," + (y + 8)
				+ "V" + (2 * y - 8)
				+ "M" + (4.5 * x) + "," + (y + 8)
				+ "V" + (2 * y - 8);
		}
	};
	
	var x			= 
	{
		extent: d3.extent(all, function(d){ return d.key; })
	};
	
	// This is used to automatically differentiate between time scale and the quantitative scale.
	if(typeof all[0].key === 'object')
	{
		if(!options.bin_width)
		{
			options.bin_width	= 1000*3600*24;
		}
	
		// cannot use all.length because crossfilter data length does not reflect date-gaps
		var bins			= Math.round((x.extent[1].getTime()-x.extent[0].getTime())/options.bin_width);
		
		var upper_boundry	= new Date(x.extent[1].getTime() + options.bin_width);
		var optimized_graph_width		= (bins+1) * dimensions.brush.bar.width;
	
		x.scale				= d3.time.scale().domain([x.extent[0], upper_boundry]).rangeRound([0, optimized_graph_width]);	
	}
	else
	{
		if(!options.bin_width)
		{
			throw 'bin_width is a required option for non-timescale histogram.';
		}
		
		var upper_boundry			= x.extent[1]+options.bin_width;
		var optimized_graph_width	= ((upper_boundry-x.extent[0])/options.bin_width)*dimensions.brush.bar.width;
		
		x.scale				= d3.scale.linear().domain([x.extent[0], upper_boundry]).rangeRound([0, optimized_graph_width]);
	}
	
	// place a tick roughly every # px
	if(!options.tick_width)
	{
		options.tick_width	= 50;
	}
	
	x.axis	= d3.svg.axis()
		.tickPadding(5)
		.tickSize(5)
		.ticks(Math.floor(optimized_graph_width/options.tick_width))
		.scale(x.scale);
	
	if(typeof options != 'undefined' && options.x_axis_format)
	{
		x.axis.tickFormat(options.x_axis_format);
	}
	
	// graph
	var graph			= svg
		.append('g')
			.attr('class', 'gragh')
			.attr('clip-path', 'url("#ay-clippath-graph-' + name + '")')
			.attr('transform', 'translate(' + options.margin.join(',') + ')');
	
	var foreground	= graph
		.append('g')
			.attr('class', 'foreground')
	
	// Create a gray graph that is displayed when there is no
	// active brush selection.
	foreground
		.selectAll('rect')
			.data(all)
			.enter()
			.append('rect')
				.attr('width', function(d){ return dimensions.brush.bar.width-1; })
				.attr('x', function(d){ return x.scale(d.key); });
	
	// This is the graph that is revealed using the brush tool.
	var background	= graph
		.append('g')
			.attr('class', 'background')
			.attr('clip-path', 'url("#ay-clippath-brush-' + name + '")');
			
	background
		.selectAll('rect')
			.data(all)
			.enter()
			.append('rect')
				.attr('width', function(){ return dimensions.brush.bar.width-1; })
				.attr('x', function(d){ return x.scale(d.key); });
	
	
	
	// scrollbar
	if(dimensions.graph.width/optimized_graph_width < 1)
	{
		dimensions.graph.height	-= dimensions.scrollbar.height;
	
		var drag	=
		{
			event: d3.behavior.drag().on('drag', function(){
				drag.move(parseInt(scrollbar.attr('x'))+d3.event.dx);
			}),
			// param	int	move_x The present scrollbar handle X value.
			move: function(move_x)
			{
				var max_offset	= optimized_graph_width-dimensions.graph.width;
				var move_width	= dimensions.graph.width-scrollbar_width;
				
				// if overscrolled to the left, stick to left-most position
				if(move_x < 0)
				{
					move_x	= 0;
				}
				// if overscrolled to the right, stick to right-most position
				else if(move_x > move_width)
				{
					move_x	= move_width;
				}
				
				var x		= (move_x/move_width)*max_offset;
				
				scrollbar.attr('x', move_x);
				
				graph.attr('transform', 'translate(' + (-1*x+options.margin[0]) + ',' + options.margin[1] + ')');
				
				clippath_graph.attr('transform', 'translate(' + (x-(allow_overflow/2)) + ',0)');				
			}
		};		
		
		var scrollbar_width		= Math.floor((dimensions.graph.width/optimized_graph_width)*dimensions.graph.width);
		
		if(scrollbar_width < 50)
		{
			scrollbar_width		= 50;
		}
		
		// the number of pixels to allow overflow on both sides (2*options.margin[0] is max)
		var allow_overflow		= 2*options.margin[0];
		
		var clippath_graph		= svg.append('clipPath')
			.attr('id', 'ay-clippath-graph-' + name)
				.append('rect')
					.attr('width', dimensions.graph.width+allow_overflow)
					.attr('height', dimensions.graph.height+dimensions.axis.x.height);
		
		
		var scrollbar			= svg.append('rect')
			.attr('class', 'scrollbar')
			.attr('width', scrollbar_width)
			.attr('height', dimensions.scrollbar.height)
			.attr('transform', 'translate(' + options.margin[0] + ',' + (dimensions.graph.height+options.margin[1]+dimensions.scrollbar.height+dimensions.axis.x.height) + ')')
			.attr('x', 0)
			.attr('y', 0)
			.call(drag.event);
		
		drag.move(dimensions.graph.width);
	}	
	
	graph
		.append('g')
			.attr('class', 'axis x')
			.attr('transform', 'translate(0,' + dimensions.graph.height + ')')
			.call(x.axis);
	
	
	var y_axis	= svg
		.append('g')
			.attr('class', 'axis y')
			.attr('transform', 'translate(' + options.margin.join(',') + ')');
	
	// brush
	var clippath_brush	= svg.append('clipPath')
		.attr('id', 'ay-clippath-brush-' + name)
			.append('rect')
			.attr('height', dimensions.graph.height);
	
	brush.d3	= d3.svg.brush()
		.x(x.scale)
			.on('brush', brush.events.brush)
			.on('brushend', brush.events.brushend)
	
	brush.g	= graph
		.append('g')
			.attr('class', 'brush')
			.call(brush.d3);
			
	brush.g
		.select('rect.background')
			.attr('width', optimized_graph_width);
			
	brush.g
		.selectAll('rect')
			.attr('height', dimensions.graph.height);
		
	brush.g
		.selectAll('.resize')
			.append('path')
				.attr('d', brush.resize_path);
	
	var render	= function(stage)
	{
		var y		=
		{
			max: d3.max(all, function(e){ return e.value; })
		};
		
		y.scale			= d3.scale.linear().range([0, dimensions.graph.height]).domain([0, y.max]);
		y.scale_invert	= d3.scale.linear().range([0, dimensions.graph.height]).domain([y.max, 0]);
	
		y.axis	= d3.svg.axis()
					.tickPadding(5)
					.tickSize(5)
					.scale(y.scale_invert)
					.tickFormat(d3.format('d'))
					.orient('right');
					
		y_axis.call(y.axis);
		
		foreground
			.selectAll('rect')
				.data(all)
					.attr('y', function(d){ return dimensions.graph.height-y.scale(d.value); })
					.attr('height', function(d){ return y.scale(d.value); });
		
		background
			.selectAll('rect')
				.data(all)
					.attr('y', function(d){ return dimensions.graph.height-y.scale(d.value); })
					.attr('height', function(d){ return y.scale(d.value); });
	};
	
	// stores reference to the histograms that use the same crossfilter
	var relations;
	
	var set_relations	= function(histogram_objects_array)
	{
		relations	= histogram_objects_array;
	}
	
	render(1);
	
	return {
		setRelations: set_relations,
		render: render
	};
};