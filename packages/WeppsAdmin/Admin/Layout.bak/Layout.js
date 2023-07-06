var readyLayoutInit = function() {
	$('.pps_radius').height($('.pps_radius').width());
	$('.pps_admin').find('a').on('click',function(event) {
		event.stopPropagation();
	});
}

$(document).ready(readyLayoutInit);
$(window).on('resize', readyLayoutInit);


/**
 * WindowLayer Object
 */
var LayoutWepps = function() {
	//this.parent = $('body').eq(0);
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
		$("#pps_ajax").remove();
		$.ajax({
			type : "POST",
			url : url,
			data : serialized,
			beforeSend: function(){
				$('.pps_loader').remove();
		    	let loader = $('<div class="pps_loader"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
		        $('body').prepend(loader)
		        
		    }
		}).done(function(responseText) {
			setTimeout(function() {
				$('.pps_loader').fadeOut();
			},500);
			if (obj) {
				// Результат в объект
				obj.html(responseText);
				$("#pps_ajax").remove();
			} else {
				// Если скрытый вызов
				var t = $("<div></div>");
				t.attr("id", "pps_ajax");
				t.html(responseText);
				$(document.body).prepend(t);
				t.css('display', 'none');
				$("#pps_ajax").remove();
			}
		}).fail(function() {
			$("#dialog").html('<p>При запросе произошла ошибка</p>').dialog({
				'title':'Ошибка',
				'modal': true,
				'buttons' : [{
					text : "Закрыть",
					icon : "ui-icon-close",
					click : function() {
						$(this).dialog("close");
					}
				}]
			});
			setTimeout(function() {
				$('.pps_loader').fadeOut();
			},500);
		 });
	}
	this.money = function(val) {
		return val.toString().replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1 ");
	}
	this.dialog = function(serialized, url) {
		var obj = $('#dialog');
		$.ajax({
			type : "POST",
			url : url,
			data : serialized,
		}).done(function(responseText) {
			obj.html(responseText);
			obj.dialog({
				'modal': true,
				'buttons':[]
			});
		});
	}
}

var layoutWepps = new LayoutWepps();
// windowOver.add('action=order','/files/bg.exts.php');
