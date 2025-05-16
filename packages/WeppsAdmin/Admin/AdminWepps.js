var readyAdminWeppsInit = function() {
	$('#sidebar-show').on('click',function(event) {
		event.preventDefault();
		if ($('.sidebar').hasClass('pps_hide_view_medium')) {
			$('.sidebar').removeClass('pps_hide_view_medium');
		} else {
			$('.sidebar').eq(1).addClass('pps_hide_view_medium');
		}
	});
	$('#sign-out').on('click',function(event) {
		event.preventDefault()
		let settings = {
			url: '/packages/WeppsAdmin/Admin/Request.php',
			data : 'action=sign-out'
		}
		layoutWepps.request(settings);
	});
	$('.pps_select').find('select').select2({
		language: "ru",
		delay: 500
	});
};
$(document).ready(readyAdminWeppsInit);