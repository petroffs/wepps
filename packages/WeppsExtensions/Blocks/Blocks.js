var readyBlocksInit = function() {
	$( ".pps_sortable" ).sortable({
		stop: function(event, ui) {
			//console.log($(ui));
			//let items = $('.pps_panel').find('.pps_block');
			let items = $(this).closest('.pps_panel').find('.pps_block');
			var str = '';
			$.each(items,function(num,elem) {
				str += $(elem).data('id')+',';
			});
			str = str.substr(0,str.length-1);
			//console.log(str);
			//layoutPPS2.request('action=sortable&items='+str, '/ext/Blocks/Request.php');
			layout2Wepps.request({
				data:'action=sortable&items='+str,
				url:'/ext/Blocks/Request.php'
			})
		}
	});
	$( ".pps_sortable" ).disableSelection();
	
	
}
$(document).ready(readyBlocksInit);