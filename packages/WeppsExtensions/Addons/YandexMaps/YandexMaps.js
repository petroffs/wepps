const yandexMapsConstructor = function() {
	this.getLatlng = function(coord) {
		let myCoord = coord.split(',');
		let myLatlng = [parseFloat(myCoord[0]),parseFloat(myCoord[1])];
		return myLatlng;
	};
	this.addMap = function(mapId, settings) {
		let widthWin = $(window).width();
		let zoom = 16;
		let myLatlng = this.getLatlng(settings.coord);
		
		if (widthWin<480) {
			zoom = 14;
		} else if (widthWin<=768) {
			zoom = 15;
		};
		zoom = (settings.zoom)?settings.zoom:zoom;
		
		this.map = new ymaps.Map(document.getElementById(mapId), {
			center : myLatlng,
			zoom : zoom,
			controls : ['zoomControl'],
		}, {
            searchControlProvider: 'yandex#search',
            suppressMapOpenBlock: true
        });
		this.map.behaviors.disable('scrollZoom');
	};
	this.markers = [];	
	this.addMarker = function(coord,customobj) {
		let myLatlng = this.getLatlng(coord);
		let marker = new ymaps.Placemark(myLatlng, {
            balloonContentHeader:customobj.title,
            balloonContentBody: customobj.descr,
            //balloonContentFooter: "host",
            //hintContent: "host"
        },
        {
			
        	//iconLayout: 'default#image',
        	//iconImageHref: '/ext/Template/files/geomarker.png',
        	//iconImageSize: [35, 45],
        	//iconImageOffset: [-5, -38]
        });
		this.map.geoObjects.add(marker);
		this.markers.push(marker);
	};
	this.addClusterer = function() {
		let clusterer = new ymaps.Clusterer({
	        preset: 'islands#invertedBlueClusterIcons',
	        groupByCoordinates: false,
	        clusterDisableClickZoom: true,
	        clusterHideIconOnBalloonOpen: false,
	        geoObjectHideIconOnBalloonOpen: false
	    });
		clusterer.options.set({
	        gridSize: 80,
	        clusterDisableClickZoom: false
	    });
		clusterer.add(this.markers);
	    this.map.geoObjects.add(clusterer);
	};
	this.destroy = function() {
		if (this.map) {
			this.map.destroy();
			this.map = null;
		}
	}
};
/**
 * 	Пример вызова
 *  $headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=7f8feb44-10b0-419b-be74-0bb485407e59");
 * 	var map = new yandexMapsConstructor();
 *	map.addMap('map',{coord:'60.011000, 30.334153'});
 *	map.addMarker($(value).data('geo'),{'title':$(value).data('name'),'descr':$(value).html(),'image':$(value).data('image')}); 
 */
