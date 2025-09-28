var readyBlocksInit = function () {
	if ($('.w_sortable').length == 0) {
		return;
	}
	$(".w_sortable").sortable({
		stop: function (event, ui) {
			let items = $(this).closest('.w_panel').find('.w_block');
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
	$(".w_sortable").disableSelection();
};
$(document).ready(readyBlocksInit);