/**
 * jQuery table-sort v0.1.1
 * https://github.com/gajus/table-sort
 *
 * Licensed under the BSD.
 * https://github.com/gajus/table-sort/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 */
(function ($) {
	$.ay = $.ay || {};
	$.ay.tableSort	= function (options) {
		var settings	= $.extend({
			'debug': false
		}, options);
		
		// @param	object	columns	NodeList table colums.
		// @param	integer	row_width	defines the number of columns per row.
		var table_to_array	= function (columns, row_width) {
			if (settings.debug) {
				console.time('table to array');
			}
		
			var columns	= Array.prototype.slice.call(columns, 0);
			
			var rows		= [];
			var row_index	= 0;
			
			for (var i = 0, j = columns.length; i < j; i += row_width) {
				var row	= [];
				
				for (var k = 0, l = row_width; k < l; k++) {
					var e			= columns[i+k];
					
					var data		= e.dataset.aySortWeight;
					
					if (data === undefined) {
						var data	= e.textContent || e.innerText;
					}
					
					var number	= parseFloat(data);
					
					var data	= isNaN(number) ? data : number;
					
					row.push(data);
				}
				
				rows.push({index: row_index++, data: row});
			}
			
			if (settings.debug) {
				console.timeEnd('table to array');
			}
			
			return rows;
		};
		
		if (!settings.target || !settings.target instanceof $) {
			throw 'Target is not defined or it is not instance of jQuery.';
		}
		
		settings.target.each(function () {
			var table		= $(this);
			
			table.find('thead th.ay-sort').on('click', function () {
				// Cannot use .siblings() because th might be not under the same <tr>
				$(this).parents('thead').find('th').not($(this)).removeClass('ay-sort-asc ay-sort-desc');
				
				var desc;
				
				if ($(this).hasClass('ay-sort-asc')) {
					$(this).removeClass('ay-sort-asc').addClass('ay-sort-desc');
					
					desc	= 1;
				} else {
					$(this).removeClass('ay-sort-desc').addClass('ay-sort-asc');
					
					desc	= 0;
				}				
				
				var index	= $(this).data('ay-sort-index') === undefined ? $(this).index() : $(this).data('ay-sort-index');
				
				table.find('tbody:not(.ay-sort-no)').each(function () {
					var tbody	= $(this);
					
					var rows		= this.rows;
					
					var anomalies	= $(rows).has('[colspan]').detach();
					
					var columns		= this.getElementsByTagName('td');
					
					if (this.data_matrix === undefined) {
						this.data_matrix	= table_to_array(columns, $(rows[0]).find('td').length);
					}
					
					var data			= this.data_matrix;
					
					if (settings.debug) {
						console.time('sort data');
					}
					
					data.sort(function (a, b) {
						if (a.data[index] == b.data[index]) {
							return 0;
						}
						
						return (desc ? a.data[index] > b.data[index] : a.data[index] < b.data[index]) ? -1 : 1;
					});
										
					if (settings.debug) {
						console.timeEnd('sort data');
						console.time('build table');
					}
					
					// Will use this to re-attach the tbody object.
					var table		= tbody.parent();
					
					// Detach the tbody to prevent unnecassy overhead related
					// to the browser environment.
					var tbody		= tbody.detach();
					
					// Convert NodeList into an array.
					rows			= Array.prototype.slice.call(rows, 0);
					
					var last_row	= rows[data[data.length-1].index];
					
					for (var i = 0, j = data.length-1; i < j; i++) {
						tbody[0].insertBefore(rows[data[i].index], last_row);
						
						// Restore the index.
						data[i].index	= i;
					}
					
					// // Restore the index.
					data[data.length-1].index	= data.length-1;
					
					tbody.prepend(anomalies);
					
					table.append(tbody);
					
					
					if (settings.debug) {
						console.timeEnd('build table');
					}
				});
			});
		});
	};
})($);