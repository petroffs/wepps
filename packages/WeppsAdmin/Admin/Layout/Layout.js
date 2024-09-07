var readyLayoutInit = function() {
	$('.pps_radius').height($('.pps_radius').width());
	$('.pps_admin').find('a').on('click',function(event) {
		event.stopPropagation();
	});
}
$(document).ready(readyLayoutInit);
//$(window).on('resize', readyLayoutInit);

class LayoutWepps {
	constructor(settings={}) {
		if (settings != undefined) {
			this.settings = settings 
		}
	}
	init() {
		$('body').removeClass('pps_win_parent');
		$('html').removeClass('pps_overflow');
		$('.pps_win_bg2').remove();
		$('.pps_win_bg').remove();
		$('.pps_loader').remove();	
		return 1;
	}
	remove() {
		let self = this;	
		$('.pps_win_element').fadeOut(300, function() {
			self.init();		
		});
		return 2;
	}
	win(settings={}) {
		let self = this;
		this.init();
		this.window = $('<div></div>');
		this.window.addClass('pps_win_element');
		this.window.attr('id', 'pps_win_element');
		
		this.closer = $('<div></div>');
		this.closer.addClass('pps_win_closer');
		this.window.append(this.closer);
		
		this.closer.on('click', function() {
			self.remove();
		});
		
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
		$(document).off('keyup');
		$(document).keyup(function(e) {
		    if (e.keyCode == 27) {
		    	self.remove();
		    }
		});
		$(document).off('mouseup');
		$(document).mouseup(function(e) {
		    if ($('.pps_win_element').has(e.target).length === 0 && $(e.target).hasClass('pps_win_element')==false && $(e.target).hasClass('pps_loader')==false) {
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
		        $('body').prepend(loader);
		    }
		}).done(function(responseText) {
			$('.pps_loader').fadeOut()
			setTimeout(function() {
				$('.pps_loader').remove();
			},500);
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

var layoutWepps = new LayoutWepps();
var utilsWepps = new UtilsWepps();