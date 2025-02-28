var layoutInit = function() {
	$('.w_radius').height($('.pps_radius').width());
	$('.w_admin').find('a').on('click',function(event) {
		event.stopPropagation();
	});
	$('.w_select').find('select').select2({
		language: "ru",
		delay: 500
	});
}
$(document).ready(layoutInit);
//$(window).on('resize', readyLayoutInit);

class LayoutWepps {
	constructor(settings={}) {
		if (settings != undefined) {
			this.settings = settings 
		}
	}
	init() {
		$('body').removeClass('w_modal_parent');
		$('html').removeClass('w_overflow');
		$('.w_modal_bg2').remove();
		$('.w_modal_bg').remove();
		$('.w_loader').remove();	
		return 1;
	}
	remove() {
		let self = this;
		$('.w_modal_element').fadeOut(300, function() {
			self.init();		
		});
		return 2;
	}
	modal(settings={}) {
		let self = this;
		this.init();
		this.window = $('<div></div>');
		this.window.addClass('w_modal_element');
		this.window.attr('id', 'w_modal_element');
		
		this.closer = $('<div></div>');
		this.closer.addClass('w_modal_closer');
		this.window.append(this.closer);
		
		this.closer.on('click', function() {
			self.remove();
		});
		
		this.content = $('<div></div>');
		this.content.addClass('w_modal_content');
		this.window.append(this.content);
		switch (settings.size) {
			case 'small':
				this.window.addClass('w_modal_small');
				break;
			case 'large':
				this.window.addClass('w_modal_large');
				break;
			default:
				this.window.addClass('w_modal_medium');
				break;
		}
		this.back = $('<div></div>');
		this.back.addClass('w_modal_bg');
		this.back.append(this.window);
		this.back2 = $('<div></div>');
		this.back2.addClass('w_modal_bg2');
		this.body = $('body');
		this.body.addClass('w_modal_parent');
		this.body.prepend(this.back2);
		this.body.prepend(this.back);
		$('html').addClass('w_overflow');
		this.window.fadeIn();
		if (settings.content != undefined) {
			let clone = settings.content.clone();
			clone.removeClass('w_hide');
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
		    if ($('.w_modal_element').has(e.target).length === 0 && $(e.target).hasClass('w_modal_element')==false && $(e.target).hasClass('w_loader')==false) {
		      	self.remove();
		    }
		});
		return 1;
	}
	request(settings={}) {
		let self = this;
		$("#w_ajax").remove();
		$.ajax({
			type : "POST",
			url : settings.url,
			data : settings.data,
			beforeSend: function(){
				$('.w_loader').remove();
		    	let loader = $('<div class="w_loader"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
		        $('body').prepend(loader)
		        
		    }
		}).done(function(responseText) {
			$('.w_loader').fadeOut()
			setTimeout(function() {
				$('.w_loader').remove();
			},500);
			if (settings.obj) {
				settings.obj.html(responseText);
				$("#w_ajax").remove();
			} else {
				var t = $("<div></div>");
				t.attr("id", "w_ajax");
				t.html(responseText);
				$(document.body).prepend(t);
				t.css('display', 'none');
				$("#w_ajax").remove();
			}
			self.call();
		});
		return 1;
	}
	call () {
		
	}
}

class UtilsWepps {
	digit(val) {
		return val.toString().replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1 ");
	}
}

var layoutWepps = new LayoutWepps();
var utilsWepps = new UtilsWepps();