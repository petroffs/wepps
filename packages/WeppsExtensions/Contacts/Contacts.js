var contactsInit = function() {
	ymaps.ready(function() {
		let coord = $('.mapData').eq(0).data('coord');
		let title = $('.mapData').eq(0).data('title');
		let descr = $('.mapData').eq(0).data('descr');
		let map = new yandexMapsConstructor();
		map.addMap('map',{coord:coord,zoom:16});
		map.addMarker(coord,{title:title,descr:descr});
	});
}
$(document).ready(contactsInit);