var setPoint = function (id, request = true) {
	let point = $('div.delivery-pickpoints-items').children('div').eq(id);
	let pointWorkTime = (point.data('work-time')) ? '<p><strong>Время работы</strong><br/>' + point.data('work-time') + '</p>' : '';
	let pointAddr = (point.data('city')) ? '<p><strong>Адрес ПВЗ ' + point.data('name') + '</strong><br/>' + point.data('city') + ', ' + point.data('address') + '</p>' : '<p><strong>Адрес ПВЗ ' + point.data('name') + '</strong><br/>' + point.data('address') + '</p>';
	$('#delivery-pickpoints-operations').children('div').text();
	$('#delivery-pickpoints-operations').children('div').html(
		pointAddr +
		pointWorkTime
	);
	$('input[name="operations-id"]').val(point.data('id'));
	$('input[name="operations-title"]').val(point.data('name'));
	$('input[name="operations-city"]').val(point.data('city'));
	$('input[name="operations-street"]').val(point.data('address'));
	$('input[name="operations-postal-code"]').val(point.data('postal-code'));
	/* $("html, body").animate({
		scrollTop: $('#delivery-pickpoints-operations').offset().top
	}, 1000); */
	$('a.set').text('ПВЗ Выбран');
	if (request == true) {
		let fields = $('input[name^="operations-"]');
		layoutWepps.request({
			data: 'action=deliveryOperations&' + fields.serialize() + '&context=cart',
			url: '/ext/Cart/Request.php'
		});
	}
};
var fnPointsInit = function () {
	ymaps.ready(init);
	function init() {
		let map = new yandexMapsConstructor();

		var point = $('.delivery-pickpoints-item').eq(0);
		var zoom = point.data('zoom');
		var indx = 0;
		var active = 0;
		if ($('.delivery-pickpoints-item.active').length) {
			point = $('.delivery-pickpoints-item.active').eq(0);
			zoom = 14;
			indx = point.data('indx');
			active = 1;
		}

		//let point = ($('.delivery-pickpoints-item.active').length)?$('.delivery-pickpoints-item.active').eq(0):$('.delivery-pickpoints-item').eq(0);
		let coords = point.data('coords');
		map.addMap('delivery-pickpoints-map', { coord: coords, zoom: zoom });
		$.each($('div.delivery-pickpoints-item'), function (i, v) {
			let coords = $(v).data('coords');
			let title = $(v).data('name');
			let descr = $(v).data('address') + '<br/>' + $(v).data('work-time') + '<br/>' + $(v).data('phone') + '<br/>' + $(v).data('email') +
				'<br/><a href="javascript:setPoint(' + i + ')" class="set">Выбрать этот ПВЗ</a>'
			map.addMarker(coords, { title: title, descr: descr });
		});
		map.map.events.add('click', function (e) {
			map.map.balloon.close();
		});

		map.addClusterer();

		var searchControl = new ymaps.control.SearchControl({
			options: {
				float: 'right',
				placeholderContent: 'Поиск по улице, дому',
				//floatIndex: 100,
				noPlacemark: true,
				provider: 'yandex#map'
			}
		});
		map.map.controls.add(searchControl);

		var geolocationControl = new ymaps.control.GeolocationControl({
			options: {
				noPlacemark: true
			}
		});
		map.map.controls.add(geolocationControl);

		//map.markers[indx].events.fire('click');
		//map.markers[indx].options.set('preset', 'islands#redIcon');
		map.markers[indx].options.set('preset', 'islands#blueStarIcon');
		if (active == 1) {
			setPoint(indx, false);
		}
	}
};
$(document).ready(fnPointsInit);