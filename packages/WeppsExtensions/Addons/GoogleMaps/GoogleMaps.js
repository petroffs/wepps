var googleMapsConstructor = function() {
	this.getLatlng = function(coord) {
		var myCoord = coord.split(',');
		var myLatlng = {
				lat : parseFloat(myCoord[0]),
				lng : parseFloat(myCoord[1])
			};
		return myLatlng;
	}
	this.addMap = function(mapId, settings) {
		var styleArray = [{
			featureType : "all",
			stylers : [{
				saturation : -50
			}]
		}, {
			featureType : "road.arterial",
			elementType : "geometry",
			stylers : [{
				hue : "#1200ff"
			}, {
				saturation : 50
			}]
		}, {
			featureType : "poi.business",
			elementType : "labels",
			stylers : [{
				visibility : "off"
			}]
		}, {
			featureType : "poi",
			elementType : "labels",
			stylers : [{
				visibility : "off"
			}]
		}];
		var widthWin = $(window).width();
		var zoom = 16;
		if (widthWin<=768) zoom = 15;
		if (widthWin<480) zoom = 14;
		zoom = (settings.zoom)?settings.zoom:zoom;
		if (widthWin<=768 && settings.zoom) zoom = settings.zoom-1;
		if (widthWin<480 && settings.zoom) zoom = settings.zoom-2;
		
		
		var myLatlng = this.getLatlng(settings.coord);
		this.map = new google.maps.Map(document.getElementById(mapId), {
			center : myLatlng,
			scrollwheel : false,
			styles : styleArray,
			zoom : zoom,
		});
		google.maps.event.addListener(this.map, 'click', function() {
		    if (infowindow) {
		        infowindow.close();
		    }
		});
		
	}
	var infoWindow = new google.maps.InfoWindow({
		maxWidth : 200
	});
	this.addMarker = function(coord,settings) {
		var myLatlng = this.getLatlng(coord);
		var marker = new google.maps.Marker({
			position: myLatlng,
			map: this.map,
			//icon: '/files/template/iz/ico-marker.png',
			custom: settings
		});
		
		marker.setMap(this.map);
		marker.addListener('click', function(e) {
			if (this.custom.descr!='') {
				infoWindow.open(this.map, marker);
				infoWindow.setContent(this.custom.descr);
			}
			//$('.gm-style-iw').parent().addClass('infobox');
		  });
	}
}
/**
 * 	Пример вызова
 * 	var map = new googleMapsConstructor();
 *	map.addMap('map','60.011000, 30.334153');
 *	map.addMarker($(value).data('geo'),{'title':$(value).data('name'),'descr':$(value).html(),'image':$(value).data('image')}); 
 */
