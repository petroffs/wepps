var ready_Example11Init = function () {
	$('a#ajax-test').on('click', function (e) {
		e.preventDefault();
		let id = $(this).data('id');
		layoutWepps.modal({ size: 'medium', data: 'action=test&id=' + id, url: '/ext/_Example11/Request.php' });
	});
};
$(document).ready(ready_Example11Init);