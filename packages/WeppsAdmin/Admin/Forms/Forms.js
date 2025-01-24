var resizeTextareaAuto = function(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight}px`;
}
var getSelect2Ajax = function(obj,fn) {
	$(obj.id).select2({
		multiple: true,
		language: "ru",
		maximumSelectionLength: 1,
		placeholder : obj.placeholder,
		ajax: {
			url: obj.url,
			delay: 500,
			dataType: 'json',
			data: function(params) {
				var query = {
					search: params.term,
					page: params.page || 1
				}
				return query;
			},
		}
	}).on('select2:select', function(event) {
		fn(event);
	});
}
var readyFormsInit = function() {
	/* test */
	$('label.pps.pps_upload').find('input[type="file"]').on('change', function(event) {
		event.stopPropagation();
		formWepps.upload($(this),event.target.files);
	});
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
	var approveform = function() {
		$('input[name="approve"]').on('change',function() {
			if ($(this).prop('checked')==true) {
				$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled',false);
			} else {
				$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled','disabled');
			}
		});
	}
	approveform();
	$('a.reset').on('click',function(event) {
		event.preventDefault();
		var t = $(this).closest('form');
		document.getElementById(t.attr('id')).reset();
	});
	$('.pps.pps_area').find('textarea').on('input', function () {
		resizeTextareaAuto(this);
	}).trigger('input');
}
$(document).ready(readyFormsInit);

class FormWepps {
	constructor(settings={}) {
		if (settings != undefined) {
			this.settings = settings
		}
	}
	upload(el,files) {
		let filesfield = el.attr('name');
		let myform = el.closest('form').attr('id');
		let data = new FormData();
		$.each(files, function(key, value) {
			data.append(key, value);
		});
		$.ajax({
			url : '/packages/WeppsAdmin/Lists/Request.php?action=upload&filesfield=' + filesfield + '&myform=' + myform,
			type : 'POST',
			data : data,
			cache : false,
			processData : false,
			contentType : false
		}).done(function(responseText) {
			$("#pps_ajax").remove();
			let t = $("<div></div>");
			t.attr("id", "pps_ajax");
			t.html(responseText);
			$(document.body).prepend(t);
		});
	}
	send(action, myform, url) {
		$('.controlserrormess').remove();
		let link = $(location).attr('pathname');
		var str = 'action=' + action + '&form=' + myform + '&link=' + link + '&';
		var serialized = $("#" + myform).serialize();
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();	
		}
		let settings = {
			url: url,
			data : str + serialized
		}
		layoutWepps.request(settings);
	}
	popup(action, myform, url) {
		$('.controlserrormess').remove();
		let link = $(location).attr('pathname');
		var str = 'action=' + action + '&form=' + myform + '&link=' + link + '&';
		var serialized = $("#" + myform).serialize();
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();	
		}
		let settings = {
			url: url,
			data : str + serialized
		}
		layoutWepps.win(settings);
	}
}
var formWepps = new FormWepps();