var readyAdminWeppsInit = function() {
	$('#sidebar-show').on('click',function(event) {
		event.preventDefault();
		if ($('.sidebar').hasClass('w_hide_view_medium')) {
			$('.sidebar').removeClass('w_hide_view_medium');
		} else {
			$('.sidebar').eq(1).addClass('w_hide_view_medium');
		}
	});
	$('#sign-out').on('click',function(event) {
		event.preventDefault();
		let settings = {
			url: '/packages/WeppsAdmin/Admin/Request.php',
			data : 'action=sign-out'
		};
		layoutWepps.request(settings);
	});
	$('.w_select').find('select').select2({
		language: "ru",
		delay: 500
	});
	utilsWepps.theme();
};
$(document).ready(readyAdminWeppsInit);