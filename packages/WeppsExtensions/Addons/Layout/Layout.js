var readyLayoutInit = function() {
	$('.pps_radius').height($('.pps_radius').width());
	$('.pps_admin').find('a').on('click',function(event) {
		event.stopPropagation();
	});
}

$(document).ready(readyLayoutInit);
//$(window).on('resize', readyLayoutInit);


/**
 * WindowLayer Object
 * deprecated
 */
var LayoutWepps = function() {
	this.add = function(serialized, url) {
		$('.winLayer').remove();
		this.parent = $('body').eq(0);
		this.parent.addClass('winLayerParent');
		var el = $('<div></div>');
		el.addClass('winLayer');
		el.css('top', $(window).scrollTop());
		this.parent.prepend(el);
		el.fadeIn();
		var self = this;

		$('.winLayer').on('click', function(e) {
			 if ( $(e.target).closest('.winLayerContent').length === 0 ) {
				 //self.remove();
			 }
		});
		$(document).keyup(function(e) {
		     if (e.keyCode == 27) {
		    	 self.remove();
		    }
		});
		this.el = el;
		this.lay();

		/*
		 * Вызваем аякс и результат в блок lay
		 */
		this.request(serialized, url, $('#winLayerContent'));

	}

	this.remove = function() {
		$('.winLayer').fadeOut();
		var self = this;
		setTimeout(function() {
			$('.winLayer').remove();
			self.parent.removeClass('winLayerParent');
		}, 50);
	}

	this.lay = function() {
		$('.winLayerLay').remove();
		var lay = $('<div></div>');
		lay.addClass('winLayer');
		lay.addClass('winLayerLay');
		this.el.append(lay);
		lay.animate({
			'top' : 0
		}, 500);
		var content = $('<div></div>');
		content.addClass('winLayerContent');
		lay.append(content);
		setTimeout(function() {
			content.fadeIn();
			if (content.height() > (lay.height() - 45)) {
				var footer = $('<div></div>');
				footer.addClass('winLayerFooter');
				lay.append(footer);
			}
		}, 700);

		content.attr('id', 'winLayerContent');
			
		var closer = $('<div></div>');
		closer.addClass('winLayerCloser');
		var self = this;
		closer.on('click', function() {
			self.remove();
		});
		setTimeout(function() {
			content.prepend(closer);
		}, 500);

	}

	this.request = function(serialized, url, obj) {
		// console.log(serialized)
		$("#pps_ajax").remove();
		$.ajax({
			type : "POST",
			url : url,
			data : serialized,
		}).done(function(responseText) {
			if (obj) {
				// Результат в объект
				obj.html(responseText);
				$("#pps_ajax").remove();
				console.log(obj)
			} else {
				// Если скрытый вызов
				var t = $("<div></div>");
				t.attr("id", "pps_ajax");
				t.html(responseText);
				$(document.body).prepend(t);
				t.css('display', 'none');
				$("#pps_ajax").remove();
			}
		});
		
	}
	this.money = function(val) {
		return val.toString().replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1 ");
	}
}

class Layout2Wepps {
	constructor(settings={}) {
		if (settings != undefined) {
			this.settings = settings 
		}
	}
	add() {
		return 1;
	}
	remove() {
		let self = this;
		this.body.removeClass('pps_win_parent');
		$('html').removeClass('pps_overflow');
		self.back.remove();
		this.back.fadeOut(500, function() {
			self.back2.remove();
			
		});
		return 2;
	}
	win(settings={}) {
		let self = this;
		this.window = $('<div></div>');
		this.window.addClass('pps_win_element');
		this.window.attr('id', 'pps_win_element');
		
		this.closer = $('<div></div>');
		this.closer.on('click', function() {
			self.remove();
		});
		this.closer.addClass('pps_win_closer');
		this.window.append(this.closer);
		
		this.content = $('<div></div>');
		this.content.addClass('pps_win_content');
		this.window.append(this.content);
		switch (settings.size) {
			case 'small':
				this.window.addClass('pps_win_small');
				break;
			case 'large':
				this.window.addClass('pps_win_large');
				break;
			default:
				this.window.addClass('pps_win_medium');
				break;
		}
		this.back = $('<div></div>');
		this.back.addClass('pps_win_bg');
		this.back.append(this.window);
		this.back2 = $('<div></div>');
		this.back2.addClass('pps_win_bg2');
		this.body = $('body');
		this.body.addClass('pps_win_parent');
		this.body.prepend(this.back2);
		this.body.prepend(this.back);
		$('html').addClass('pps_overflow');
		this.window.fadeIn();
		if (settings.content != undefined) {
			let clone = settings.content.clone();
			clone.removeClass('pps_hide');
			this.content.html(clone);
		} else if (settings.url != undefined && settings.data != undefined) {
			settings.obj = this.content;
			this.request(settings);
		} 
		$(document).keyup(function(e) {
		    if (e.keyCode == 27) {
		    	self.remove();
		    }
		});
		$(document).mouseup(function(e) {
		    if (!self.window.is(e.target) && self.window.has(e.target).length === 0 && $(e.target).hasClass('pps_loader')==false) {
		    	//console.log()
		        self.remove();
		    }
		});
		return 1;
	}
	request(settings={}) {
		let self = this;
		$("#pps_ajax").remove();
		$.ajax({
			type : "POST",
			url : settings.url,
			data : settings.data,
			beforeSend: function(){
				$('.pps_loader').remove();
		    	let loader = $('<div class="pps_loader"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
		        $('body').prepend(loader)
		        
		    }
		}).done(function(responseText) {
			setTimeout(function() {
				$('.pps_loader').fadeOut();
			},300);
			if (settings.obj) {
				settings.obj.html(responseText);
				$("#pps_ajax").remove();
			} else {
				var t = $("<div></div>");
				t.attr("id", "pps_ajax");
				t.html(responseText);
				$(document.body).prepend(t);
				t.css('display', 'none');
				$("#pps_ajax").remove();
			}
			self.call();
		});
		return 1;
	}
	call () {
		
	}
}

class UtilsWepps {
	money(val) {
		return val.toString().replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1 ");
	}
}

var layoutWepps = new LayoutWepps(); //deprecated
var layout2Wepps = new Layout2Wepps();
var utilsWepps = new UtilsWepps();