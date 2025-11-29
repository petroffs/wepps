$('input#privacy-save').on('click', function (e) {
	e.preventDefault();
	let serialized = '';
	$('#legal-form').find('input[type="checkbox"]').each(function () {
		let name = $(this).attr('name');
		if (name) {
			serialized += name + '=' + ($(this).is(':checked') ? $(this).val() : 'false') + '&';
		}
	});
	serialized = serialized.slice(0, -1);
	layoutWepps.request({ data: 'action=agree&' + serialized, url: '/ext/Legal/Request.php' });
	layoutWepps.remove();
});
$('input#privacy-accept-all').on('click', function (e) {
	e.preventDefault();
	layoutWepps.request({ data: 'action=agree&default=true&analytics=true', url: '/ext/Legal/Request.php' });
	layoutWepps.remove();
});
$('input#privacy-reject-all').on('click', function (e) {
	e.preventDefault();
	layoutWepps.request({ data: 'action=agree&default=false&analytics=false', url: '/ext/Legal/Request.php' });
	layoutWepps.remove();
});