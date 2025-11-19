$('input#privacy-accept-all').on('click', function (e) {
		e.preventDefault();
		layoutWepps.request({ data: 'action=agree&default=true&analytics=true', url: '/ext/Legal/Request.php' });
	});