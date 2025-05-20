var setPoint = function(id) {
	let point = $('div.points').children('div').eq(id);
	let pointWorkTime = (point.data('work-time')) ? '<p><strong>Время работы</strong><br/>'+point.data('work-time')+'</p>' : '';
	let pointAddr = (point.data('city')) ? '<p><strong>Адрес ПВЗ '+point.data('name')+'</strong><br/>'+point.data('city')+', '+point.data('address')+'</p>' : '<p><strong>Адрес ПВЗ '+point.data('name')+'</strong><br/>'+point.data('address')+'</p>';
	$('#pointAddress').children('div').text();
	$('#pointAddress').children('div').html(
		pointAddr +
		pointWorkTime
		);
	$('input[name="pointTitle"]').val(point.data('name'));
	$('input[name="pointCity"]').val(point.data('city'));
	$('input[name="pointStreet"]').val(point.data('address'));
	$('input[name="pointAddressIndex"]').val(point.data('postal-code'));
	$("html, body").animate({
        scrollTop: $('#pointAddress').offset().top 
    }, 1000);
	$('a.set').text('ПВЗ Выбран');
};
var fnCdekPointsInit = function() {
	ymaps.ready(init);
	function init () {
		var map = new yandexMapsConstructor();
		var coords = $('.point').eq(0).data('coords');
		map.addMap('delivery-pickpoints-map',{ coord:coords , zoom:$('.point').eq(0).data('zoom') });
		$.each($('div.point'),function(i,v) {
			let coords = $(v).data('coords');
			let title = $(v).data('name');
			
			let descr = $(v).data('address')+'<br/>'+$(v).data('work-time')+'<br/>'+$(v).data('phone')+'<br/>'+$(v).data('email')+
			'<br/><a href="javascript:setPoint('+i+')" class="set">Выбрать этот ПВЗ</a>'
			map.addMarker(coords,{ title: title , descr:descr });
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