var readyLegalModalInit = function () {
	$('a#privacy-agree').on('click', function (e) {
		e.preventDefault();
		layoutWepps.request({ data: 'action=agree&default=true&analytics=true', url: '/ext/Legal/Request.php' });
	});
	$('a.privacy-settings').on('click', function (e) {
		e.preventDefault();
		layoutWepps.modal({ size: 'medium', data: 'action=settings', url: '/ext/Legal/Request.php' });
	});
	// $('a#privacy-settings').trigger('click')
};
$(document).ready(readyLegalModalInit);