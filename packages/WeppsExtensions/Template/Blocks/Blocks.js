var readyBlocksInit = function () {
	if ($('.pps_sortable').length == 0) {
		return;
	}
	$(".pps_sortable").sortable({
		stop: function (event, ui) {
			let items = $(this).closest('.pps_panel').find('.pps_block');
			var str = '';
			$.each(items, function (num, elem) {
				str += $(elem).data('id') + ',';
			});
			str = str.substr(0, str.length - 1);
			layoutWepps.request({
				data: 'action=sortable&items=' + str,
				url: '/ext/Template/Blocks/Request.php'
			})
		}
	});
	$(".pps_sortable").disableSelection();
};
$(document).ready(readyBlocksInit);