var readyLegalInit = function () {
	$('a#ajax-test').on('click', function (e) {
		e.preventDefault();
		let id = $(this).data('id');
		layoutWepps.modal({ size: 'medium', data: 'action=test&id=' + id, url: '/ext/Legal/Request.php' });
	});
};
$(document).ready(readyLegalInit);