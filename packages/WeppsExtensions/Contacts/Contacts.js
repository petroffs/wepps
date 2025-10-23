const contactsInit = function () {
	$('input[name="phone"]').inputmask("+7 (999) 999-99-99");
	if (typeof ymaps === 'undefined') {
		console.error('Yandex Maps not loaded');
		return;
	}
	ymaps.ready(function () {
		try {
			$('a[data-coord]').on('click', function (e) {
				e.preventDefault();
				if ($(this).data('coord')) {
					let coord = $(this).data('coord');
					if (!coord) {
						console.warn('No coordinates provided');
						return;
					}
					let map = new yandexMapsConstructor();
					$('#map').empty();
					map.addMap('map', { coord: coord, zoom: 10 });
					map.addMarker(coord, { title: '', descr: '' });
				};
			});
		} catch (e) {
			console.log('Yandex Maps error: ' + e);
		}
		$('a[data-coord]').first().trigger('click');
	});
};
$(document).ready(contactsInit);