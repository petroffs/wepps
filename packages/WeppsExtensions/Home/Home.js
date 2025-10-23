const readyHomeInit = function () {
	$('.image-gallery').magnificPopup({
		type: 'image',
		gallery: {
			enabled: true,
			tPrev: '',
			tNext: '',
			tCounter: '%curr% / %total%'
		},
		image: {
			titleSrc: 'title',
			cursor: ''
		}
	});
	if (typeof ymaps === 'undefined') {
		console.error('Yandex Maps not loaded');
		return;
	}
	ymaps.ready(function () {
		try {
			let coord = $('.mapData').eq(0).data('coord');
			let title = $('.mapData').eq(0).data('title');
			let descr = $('.mapData').eq(0).data('descr');
			let map = new yandexMapsConstructor();
			map.addMap('map', { coord: coord, zoom: 16 });
			map.addMarker(coord, { title: title, descr: descr });
		} catch (e) {
			console.log('Yandex Maps error: ' + e);
		}

	});
};
$(document).ready(readyHomeInit);