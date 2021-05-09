var FormSenderWepps  = function () {
	var uploadaction = function(event,filesfield,myform) {
		event.stopPropagation();
		var files = event.target.files;
		var data = new FormData();
		$.each(files, function(key, value) {
			data.append(key, value);
		});
		$.ajax({
			url : '/packages/WeppsAdmin/Lists/Request.php?action=upload&filesfield=' 
				+ filesfield + '&myform=' + myform,
			type : 'POST',
			data : data,
			cache : false,
			processData : false,
			contentType : false,
			beforeSend: function(){
				$('.pps_loader').remove();
		    	let loader = $('<div class="pps_loader"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
		        $('body').prepend(loader)
		    }
		}).done(function(responseText) {
			setTimeout(function() {
				$('.pps_loader').fadeOut();
			},500);
			$("#pps_ajax").remove();
			var t = $("<div></div>");
			t.attr("id", "pps_ajax");
			t.html(responseText);
			$(document.body).prepend(t);
		}).fail(function() {
			$("#dialog").html('<p>При загрузке файла произошла ошибка</p>').dialog({
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
	this.upload = function(event) {
		//console.log($(this).attr('name'));
		uploadaction(event,$(this).attr('name'),$(this).closest('form').attr('id'));
	}
	this.send = function (action, myform, url, lang) {
		$('.controlserrormess').remove();
		var str = 'action=' + action + '&form=' + myform + '&link=' + lang + '&';
		var serialized = $("#" + myform).serialize();
		if (!layoutWepps) var layoutWepps = new LayoutWepps();
		layoutWepps.request(str + serialized, url);
	}
}
var formSenderWepps = new FormSenderWepps();

var readyFormsInit = function() {
	$('label.pps.pps_upload').find('input[type="file"]').on('change', formSenderWepps.upload);
	$('.pps_form_group').find('.pps_flex_14').on('click',function(event) {
		var parent1 = $(this).parent();
		var input1 = parent1.find('input');
		var num2 = parseInt(input1.val());
		num2 = (!num2) ? 0 : num2;
		if ($(this).hasClass('pps_form_group_minus')) {
			num2--;
		} else {
			num2++;
		}
		if (num2<parseInt(input1.attr('min'))) num2 = parseInt(input1.attr('min'));
		if (num2>parseInt(input1.attr('max'))) num2 = parseInt(input1.attr('max'));
		if (num2==0) num2="не важно"
		input1.val(num2);
	});
	$('a.reset').on('click',function(event) {
		event.preventDefault();
		var t = $(this).closest('form');
		document.getElementById(t.attr('id')).reset();
	});
}
$(document).ready(readyFormsInit);