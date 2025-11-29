function autoResizeTextarea(textarea) {
	textarea.style.height = 'auto';
	textarea.style.height = `${textarea.scrollHeight}px`;
};
var select2Ajax = function (obj, fn) {
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
			data: function (params) {
				var query = {
					search: params.term,
					page: params.page || 1
				};
				return query;
			}
		}
	}).on('select2:select', function (event) {
		fn(event);
	});
	//$(id).select2("destroy").select2();
};

var select2Render = function() {
 	if ($(".w_select select").hasClass("select2-hidden-accessible")) {
		return;
	}
    $('.w_select select').select2({
        language: "ru",
        delay: 500
    });
}
var formsInit = function () {
	$('label.w_label.w_upload').find('input[type="file"]').off('change').on('change', function (event) {
		event.stopPropagation();
		formWepps.upload($(this), event.target.files);
	});
	$('div.w_upload_file').children('.bi').off('click').on('click', function (e) {
		e.preventDefault();
		let el = $(this).closest('section').find('input[type="file"]');
		let filesField = el.attr('name');
		let filesForm = el.closest('form').attr('id');
		let key = $(this).closest('div.w_upload_file').data('key');
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();
		};
		let settings = {
			url: '/ext/Template/Request.php?action=removeUploaded',
			data: 'filesfield=' + filesField + '&filesform=' + filesForm + '&key=' + key
		};
		layoutWepps.request(settings);
	});
	var approveform = function () {
		$('input[name="approve"]').off('change').on('change', function () {
			var val = true;
			if ($(this).prop('checked') == true) {
				val = false;
			}
			$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled', val);
		});
	};
	approveform();
	$('a.reset').off('click').on('click', function (event) {
		event.preventDefault();
		var t = $(this).closest('form');
		document.getElementById(t.attr('id')).reset();
	});
	$('.w_label.w_area').find('textarea').off('input').on('input', function () {
		autoResizeTextarea(this);
	}).trigger('input');
	select2Render();
	$('i.w_field_empty').off('click').on('click', function () {
		$(this).siblings('input,textarea').val('');
	});
};
$(document).ready(formsInit);

class FormWepps {
	constructor(settings = {}) {
		if (settings != undefined) {
			this.settings = settings
		}
	};
	upload(el, files) {
		let filesField = el.attr('name');
		let filesForm = el.closest('form').attr('id');
		let data = new FormData();
		$.each(files, function (key, value) {
			data.append(key, value);
		});
		$.ajax({
			url: '/ext/Template/Request.php?action=upload&filesfield=' + filesField + '&filesform=' + filesForm,
			type: 'POST',
			data: data,
			cache: false,
			processData: false,
			contentType: false
		}).done(function (responseText) {
			$("#w_ajax").remove();
			let t = $("<div></div>");
			t.attr("id", "w_ajax");
			t.html(responseText);
			$(document.body).prepend(t);
		});
	};
	send(action, myform, url) {
		$('.w_error').remove();
		let link = $(location).attr('pathname');
		let str = 'action=' + action + '&form=' + myform + '&link=' + link + '&';
		let serialized = $("#" + myform).serialize();
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();
		};
		let settings = {
			url: url,
			data: str + serialized
		};
		layoutWepps.request(settings);
	};
	popup(action, myform, url) {
		$('.w_error').remove();
		let link = $(location).attr('pathname');
		var str = 'action=' + action + '&form=' + myform + '&link=' + link + '&';
		var serialized = $("#" + myform).serialize();
		if (!layoutWepps) {
			var layoutWepps = new LayoutWepps();
		};
		let settings = {
			url: url,
			data: str + serialized
		};
		layoutWepps.win(settings);
	};
	minmax() {
		let self = this;
		let fn = function (input, inputVal) {
			if (inputVal < parseInt(input.attr('min'))) {
				inputVal = parseInt(input.attr('min'));
			};
			if (inputVal > parseInt(input.attr('max'))) {
				inputVal = parseInt(input.attr('max'));
			};
			input.val(inputVal);
			self.minmaxAfter(input.closest('section').data('id'), inputVal);
		};
		$('.w_minmax').find('button').off('click');
		$('.w_minmax').find('button').on('click', function (event) {
			event.preventDefault();
			let input = $(this).siblings('input');
			var inputVal = parseInt(input.val()) ?? 1;
			if ($(this).hasClass('sub')) {
				inputVal--;
			} else {
				inputVal++;
			};
			fn(input, inputVal);
		});
		$('.w_minmax').find('input').off('keyup');
		$('.w_minmax').find('input').on('keyup', function (event) {
			event.preventDefault();
			let input = $(this);
			var inputVal = parseInt(input.val()) ?? 1;
			if (!Number.isInteger(inputVal)) {
				inputVal = parseInt(input.attr('min'));
			};
			fn(input, inputVal);
		});
	};
	minmaxAfter(id, inputVal) {
		console.log(id + ' / ' + inputVal);
	};
};
var formWepps = new FormWepps();