/**
 * Pie Chart v0.1.0
 * https://github.com/gajus/pie-chart
 *
 * Licensed under the BSD.
 * https://github.com/gajus/pie-chart/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 */
var ay = ay || {};

ay.pie_chart	= function (name, data, options) {
	'use strict';
	if (window.d3 === undefined) {
		throw 'Pie Chart requires presence of the d3.js library.';
	}
	var svg = d3.select('svg.' + name),
		chart_size = svg[0][0].clientWidth || svg[0][0].parentNode.clientWidth,
		settings = {
			radius_inner: 0,
			radius_outer: chart_size / 3,
			radius_label: chart_size / 3 + 20,
			percentage: true,
			label_margin: 10,
			group_data: 0
		},
		donut,
		arc,
		slices,
		labels_group,
		grouped_labels = {left: [], right: []},
		labels,
		label_boxes,
		label_texts,
		parameter,
		reposition_colliding_labels = function (group) {
			group
				.sort(function (a, b) {
					return (a.y + a.height) - (b.y + b.height);
				})
				.forEach(function (e, i) {
					if (group[i + 1]) {
						if (group[i + 1].y - (e.y + e.height) < settings.label_margin) {
							group[i + 1].y = (e.y + e.height) + settings.label_margin;
						}
					}
					if (e.x < settings.label_margin) {
						e.x	= settings.label_margin;
					} else if (e.x + e.width > chart_size - settings.label_margin) {
						e.x = chart_size - e.width - settings.label_margin;
					}
					d3.select(labels[0][e.index])
						.attr('transform', 'translate(' + e.x + ', ' + e.y + ')');
					d3.select(label_boxes[0][e.index])
						.attr('x', 0)
						.attr('y', -e.height + 2)
						.attr('width', e.width + 4)
						.attr('height', e.height + 4);
					e.textNode
						.attr('x', 2)
						.attr('y', 2);
				});
		},
		group_data = function (data) {
			var data_size = 0,
				removed_data_size = 0,
				i;
			data.forEach(function (e) {
				data_size += e.value;
			});
			// Check if it is worth grouping the data.
			for (i = data.length-1; i >= 0; i--) {
				if ((data[i].value / data_size) * 100 < settings.group_data) {
					removed_data_size++;
				}
			}
			if(removed_data_size > 1) {
				removed_data_size = 0;
				for (i = data.length-1; i >= 0; i--) {
					if ((data[i].value / data_size) * 100 < settings.group_data) {
						removed_data_size += data.splice(i, 1)[0].value;
					}
				}
			}
			data.push({index: 0, name: 'Other', value: removed_data_size});
			return data;
		};
	if (data.map(function (d) { return d.index; }).indexOf(0) !== -1) {
		throw '0 index is reserved for grouped data.'
	}
	if (options !== undefined) {
		for (parameter in options) {
			if (options.hasOwnProperty(parameter) && settings[parameter] !== undefined) {
				settings[parameter]		= options[parameter];
			}
		}
	}
	if (settings.group_data) {
		data = group_data(data);
	}
	donut = svg
		.append('g')
		.attr('class', 'donut')
		.attr('transform', 'translate(' + (chart_size / 2) + ', ' + (chart_size / 2) +  ')');
	arc = d3.svg.arc()
		.innerRadius(settings.radius_inner)
		.outerRadius(settings.radius_outer);
	data = d3.layout.pie()
		.value(function (e) {
			return e.value;
		})
		.sort(function (a, b) {
			return b.index - a.index;
		})(data);
	slices = donut
		.selectAll('path')
		.data(data)
		.enter()
		.append('path')
		.attr('class', function (d) {
			return 'g-' + d.data.index;
		})
		.attr('d', arc)
		.on('mouseover', function (d, i) {
			d3.select(labels[0][i])
				.classed('active', true);
		})
		.on('mouseout', function (d, i) {
			d3.select(labels[0][i])
				.classed('active', false);
		});
	
	labels_group = svg
		.append('g')
		.attr('class', 'labels');
	labels = labels_group
		.selectAll('g.label')
		.data(data)
		.enter()
		.append('g')
		.filter(function (e) {
			if (settings.percentage) {
				return true;
			}
			return e.data.name !== undefined;
		})
		.attr('class', 'label');
	label_boxes = labels
		.append('rect');
	label_texts = labels
		.append('text').text(function (e) {
			var percentage = (((e.endAngle - e.startAngle) / (2 * Math.PI)) * 100).toFixed(2),
				label = [];
			if (e.data.name !== undefined) {
				label.push(e.data.name);
			}
			if (settings.percentage) {
				label.push(percentage + '%');
			}			
			return label.join(' ');
		})
		.each(function (d, i) {
			var center = arc.centroid(d),
				x = center[0],
				y = center[1],
				h = Math.sqrt(x * x + y * y),
				lx = x / h * settings.radius_label + chart_size / 2,
				ly = y / h * settings.radius_label + chart_size / 2,
				left_aligned = (d.endAngle - d.startAngle) * 0.5 + d.startAngle > Math.PI,
				text = d3.select(this),
				bb = this.getBBox();
			grouped_labels[left_aligned ? 'left' : 'right'].push({
				index: i,
				width: bb.width,
				height: bb.height,
				x: left_aligned ? lx - bb.width : lx,
				y: ly,
				textNode: text
			});
		});
	reposition_colliding_labels(grouped_labels.left);
	reposition_colliding_labels(grouped_labels.right);
};