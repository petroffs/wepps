var readyAdminWeppsInit = function() {
	$('#showleftmenu').on('click',function(event) {
		event.preventDefault();
		if ($('.leftmenu').hasClass('pps_hide_view_medium')) {
			$('.leftmenu').removeClass('pps_hide_view_medium');
		} else {
			$('.leftmenu').eq(1).addClass('pps_hide_view_medium');
		}
	});
	$('#logoff').on('click',function(event) {
		event.preventDefault()
		layoutWepps.request('action=logoff', '/packages/WeppsAdmin/Admin/Request.php');
	});
}
$(document).ready(readyAdminWeppsInit);