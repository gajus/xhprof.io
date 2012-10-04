/**
 * Pie Chart v0.0.2
 * https://github.com/anuary/ay-pie-chart
 *
 * Licensed under the BSD.
 * https://github.com/anuary/ay-pie-chart/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 */
var ay_pie_chart	= function(name, data, options, debug)
{
	'use strict';
	
	var svg	= d3
		.select('svg.' + name);
		
	// The svg element is expected to be a square.
	var size			= svg[0][0].getBoundingClientRect().width;
	
	var radius_outer	= size/3;
	var radius_inner	= 50;
	
	if(typeof options != 'undefined')
	{
		if(typeof options.radius_outer != 'undefined')
		{
			radius_outer	= options.radius_outer;
		}
		
		if(typeof options.radius_inner != 'undefined')
		{
			radius_inner	= options.radius_inner;
		}
	
		if(typeof options.label != 'undefined')
		{
			var label	= svg
				.append('g')
					.attr('class', 'label')
					.attr('transform', 'translate(' + ((size/2)-radius_inner) + ', ' + ((size/2)-radius_inner) + ')');
			
			var label_margin	= 5;
			
			var labels			= [];
			var label_height	= 0;
			
			label
				.selectAll('text')
				.data(options.label)
				.enter()
				.append('text')
					.attr('class', function(e, i){
						return 'label i-' + i;
					})
					.style('text-anchor', 'middle')
					.text(function(e){ return e; })
						.attr('dx', function(){
							label_height	+= this.getBBox().height;
						
							labels.push( this.getBBox().height );
						
							return radius_inner;
						});
			
			
			
			var label_dy 		= [];
			
			var	label_height	= label_height+label_margin*labels.length;
			
			var label_top		= (radius_inner*2-label_height)/2;
			
			
			label
				.selectAll('text')
					.attr('dy', function(e, i){ label_top += labels[i]; return label_top + i*label_margin; });
		}
	}
	
	var label_radius	= radius_outer+20;
	
	var donut	= svg
		.append('g')
		.attr('transform', 'translate(' + (size/2) + ', ' + (size/2) +  ')');
	
	var arc		= d3.svg.arc()
		.innerRadius(radius_inner)
		.outerRadius(radius_outer);
	
	var pie		= d3.layout.pie().value(function(e){ return e.value; })(data);
	
	var slices	= donut
		.selectAll('g.slice')
		.data(pie)
		.enter()
		.append('g')
			.attr('class', 'slice');
			
	slices
		.append('path')
		.attr('class', function(d){
			return 'g-' + d.data.index;
		})
		.attr('d', arc);
	
	var total	= 100;
	
	var text	= slices
	    .append('text')
		    .text(function(e){
				return e.data.name;
		    })
		    .each(function(d) {
		        // Get the center of the slice and then move the label out
		        var center = arc.centroid(d), // gives you the center point of the slice
		            x = center[0],
		            y = center[1],
		            h = Math.sqrt(x*x + y*y),
		            lx = x/h * label_radius,
		            ly = y/h * label_radius;
		        
		        //var bb	= this.getBBox();
		        
		        d3.select(this)
		            .attr('y', ly)
		            .attr('x', lx)
		            .style('text-anchor', ((d.endAngle - d.startAngle)*0.5 + d.startAngle > Math.PI) ? 'end' : 'start');
		    });
	
	if(typeof debug != 'undefined')
	{
		text.style('fill', function(d, index){
			var bb	= this.getBBox();
			
			var p	=
			[
				{x: bb.x, y: bb.y},
				{x: bb.x+bb.width, y: bb.y},
				{x: bb.x+bb.width, y: bb.y+bb.height},
				{x: bb.x, y: bb.y + bb.height}
			];
			
			// Determine any of the corners are outside the slice angles
			var is_outside = false;
			
			for(var i=0; i<4; ++i)
			{
				// The angle from the center to the corner
				var a = (Math.atan2(p[i].y, p[i].x) + Math.PI*2.5) % (Math.PI*2);
				
				// Debugging display
				var line = this.ownerDocument.createElementNS("http://www.w3.org/2000/svg", "line");
				line.setAttributeNS(null, 'x1', 0);
				line.setAttributeNS(null, 'y1', 0);
				line.setAttributeNS(null, 'x2', 0);
				line.setAttributeNS(null, 'y2', 0-size);
				line.setAttributeNS(null, 'transform', 'rotate('+(a*180/Math.PI)+')')
				$(this).parent().append(line);
				
				var dot = this.ownerDocument.createElementNS("http://www.w3.org/2000/svg", "circle");
				dot.setAttributeNS(null, 'cx', p[i].x);
				dot.setAttributeNS(null, 'cy', p[i].y);
				dot.setAttributeNS(null, 'r', 2);
				dot.setAttributeNS(null, 'fill', 'red');
				$(this).parent().append(dot);
				
				if(a < d.startAngle || a > d.endAngle)
				{
					is_outside = true;
				}
			}
			
			return is_outside ? '#111' : '#000';
		});
	}	
};