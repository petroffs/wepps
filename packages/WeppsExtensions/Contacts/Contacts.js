var contactsInit = function() {
	ymaps.ready(function() {
		$('a[data-coord]').eq(0).trigger('click');
	});
	$('input[name="phone"]').inputmask("+7 (999) 999-99-99");
	$('a[data-coord]').on('click',function(e) {
		e.preventDefault();
		if ($(this).data('coord')) {
			let coord = $(this).data('coord');
			let map = new yandexMapsConstructor();
			console.log(coord);
			$('#map').html('');
			//map.destroy();
			map.addMap('map',{coord:coord,zoom:10});
			map.addMarker(coord,{title:'',descr:''});
		}
	});
}
$(document).ready(contactsInit);