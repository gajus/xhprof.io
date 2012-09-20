/*
(function($){
	$.fn.ayTableEmpty	= function(){
		this.each(function(){
			var table			= $(this);
			
			var colspan			= 0;
			
			table.find('thead tr').eq(0).find('th').each(function(){
				colspan	+= $(this).attr('colspan') ? parseInt($(this).attr('colspan')) : 1;
			});
			
			table.find('tbody').each(function(){
				if(!$(this).find('tr').length)
				{
					$(this).append($('<tr><td></td></tr>').addClass('empty').find('td').attr('colspan', colspan).text('No data.').parent());
				}
			});
		});
	};
})($);
*/

$(function(){
	$('table.ay-sort').ayTableSort();
	$('thead.ay-sticky').ayTableSticky();
	//$('table').ayTableEmpty();
});