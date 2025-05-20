var setPoint = function (id) {
	let point = $('div.delivery-pickpoints-items').children('div').eq(id);
	let pointWorkTime = (point.data('work-time')) ? '<p><strong>Время работы</strong><br/>' + point.data('work-time') + '</p>' : '';
	let pointAddr = (point.data('city')) ? '<p><strong>Адрес ПВЗ ' + point.data('name') + '</strong><br/>' + point.data('city') + ', ' + point.data('address') + '</p>' : '<p><strong>Адрес ПВЗ ' + point.data('name') + '</strong><br/>' + point.data('address') + '</p>';
	$('#delivery-pickpoints-address').children('div').text();
	$('#delivery-pickpoints-address').children('div').html(
		pointAddr +
		pointWorkTime
	);
	$('input[name="address-id"]').val(point.data('id'));
	$('input[name="address-title"]').val(point.data('name'));
	$('input[name="address-city"]').val(point.data('city'));
	$('input[name="address-street"]').val(point.data('address'));
	$('input[name="address-postal-code"]').val(point.data('postal-code'));
	$("html, body").animate({
		scrollTop: $('#delivery-pickpoints-address').offset().top
	}, 1000);
	$('a.set').text('ПВЗ Выбран');
	let fields = $('input[name^="address-"]');
	layoutWepps.request({
		data: 'action=address&' + fields.serialize() + '&context=cart',
		url: '/ext/Cart/Request.php'
	});
};
var fnCdekPointsInit = function () {
	ymaps.ready(init);
	function init() {
		var map = new yandexMapsConstructor();
		var coords = $('.delivery-pickpoints-item').eq(0).data('coords');
		map.addMap('delivery-pickpoints-map', { coord: coords, zoom: $('.delivery-pickpoints-item').eq(0).data('zoom') });
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
	}
};
$(document).ready(fnCdekPointsInit);