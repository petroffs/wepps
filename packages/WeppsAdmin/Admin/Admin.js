var readyAdminInit = function() {
	$('.pps_admin').find('a').on('click',function(event) {
		event.stopPropagation();
	});
}
$(document).ready(readyAdminInit);