var readyAdminInit = function() {
	$('.w_admin').find('a').on('click',function(event) {
		event.stopPropagation();
	});
};
$(document).ready(readyAdminInit);