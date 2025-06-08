function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight}px`;
};
var select2Ajax = function(obj,fn) {
	let id = obj.id;
	let url = obj.url;
	let max = obj.max;
	let placeholder = obj.placeholder;
	$(id).select2({
		language: "ru",
		multiple: true,
		maximumSelectionLength: max,
		ajax: {
			url: url,
			delay: 500,
			dataType: 'json',
			data: function(params) {
				var query = {
					search: params.term,
					page: params.page || 1
				};
				return query;
			}
		}
	}).on('select2:select', function(event) {
		fn(event);
	});
	//$(id).select2("destroy").select2();
};
var formsInit = function() {
	$('label.pps.pps_upload').find('input[type="file"]').on('change', function(event) {
		event.stopPropagation();
		formWepps.upload($(this),event.target.files);
	});
	var approveform = function() {
		$('input[name="approve"]').on('change',function() {
			if ($(this).prop('checked')==true) {
				$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled',false);
			} else {
				$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled','disabled');
			}
		});
	};
	approveform();
	$('a.reset').on('click',function(event) {
		event.preventDefault();
		var t = $(this).closest('form');
		document.getElementById(t.attr('id')).reset();
	});
	$('.pps.pps_area').find('textarea').on('input', function () {
		autoResizeTextarea(this);
	}).trigger('input');
	$('.pps_select').find('select').select2({
		language: "ru",
		delay: 500
	});
	$('i.pps_field_empty').on('click',function() {
		$(this).siblings('input,textarea').val('');
	});
};
$(document).ready(formsInit);

class FormWepps {
	constructor(settings={}) {
		if (settings != undefined) {
			this.settings = settings
		}
	};
	upload(el,files) {
		let filesfield = el.attr('name');
		let myform = el.closest('form').attr('id');
		let data = new FormData();
		$.each(files, function(key, value) {
			data.append(key, value);
		});
		$.ajax({
			url : '/ext/Tempate/Request.php?action=upload&filesfield=' + filesfield + '&myform=' + myform,
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
	};
	send(action, myform, url) {
		$('.pps_error').remove();
		let link = $(location).attr('pathname');
		var str = 'action=' + action + '&form=' + myform + '&link=' + link + '&';
		var serialized = $("#" + myform).serialize();
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();	
		};
		let settings = {
			url: url,
			data : str + serialized
		};
		layoutWepps.request(settings);
	};
	popup(action, myform, url) {
		$('.pps_error').remove();
		let link = $(location).attr('pathname');
		var str = 'action=' + action + '&form=' + myform + '&link=' + link + '&';
		var serialized = $("#" + myform).serialize();
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();	
		};
		let settings = {
			url: url,
			data : str + serialized
		};
		layoutWepps.win(settings);
	};
	minmax() {
		let self = this;
		let fn = function(input,inputVal) {
			if (inputVal<parseInt(input.attr('min'))) {
				inputVal = parseInt(input.attr('min'));
			};
			if (inputVal>parseInt(input.attr('max'))) {
				inputVal = parseInt(input.attr('max'));
			};
			input.val(inputVal);
			self.minmaxAfter(input.closest('section').data('id'),inputVal);
		};
		$('.pps_minmax').find('button').off('click');
		$('.pps_minmax').find('button').on('click',function(event) {
			event.preventDefault();
			let input = $(this).siblings('input');
			var inputVal = parseInt(input.val())??1;
			if ($(this).hasClass('sub')) {
				inputVal--;
			} else {
				inputVal++;
			};
			fn(input,inputVal);
		});
		$('.pps_minmax').find('input').off('keyup');
		$('.pps_minmax').find('input').on('keyup',function(event) {
			event.preventDefault();
			let input = $(this);
			var inputVal = parseInt(input.val())??1;
			if (!Number.isInteger(inputVal)) {
				inputVal = parseInt(input.attr('min'));
			};
			fn(input,inputVal);
		});
	};
	minmaxAfter(id,inputVal) {
		console.log(id+' / '+inputVal);
	};
};
var formWepps = new FormWepps();