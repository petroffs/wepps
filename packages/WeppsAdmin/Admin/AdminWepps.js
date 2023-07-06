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
		let settings = {
			url: '/packages/WeppsAdmin/Admin/Request.php',
			data : 'action=logoff'
		}
		layoutWepps.request(settings);
	});
	$('.pps_select').find('select').select2({
		language: "ru",
		delay: 500
	});
}
$(document).ready(readyAdminWeppsInit);