function cleanString2(t1, t2) {
	const index = t1.indexOf(t2);
	if (index === -1) return t1.trim();
	let result = t1.slice(index + t2.length);
	result = result.replace(/^[^\wа-яА-ЯёЁ\d\s]+|[^\wа-яА-ЯёЁ\d\s]+$/g, '').trim();
	return result;
};
var fnAddressInit = function () {
	$('#deliveryAddressBtn').on('click', function () {
		let fields = $('input[name^="operations-"]');
		layoutWepps.request({
			data: 'action=deliveryOperations&' + fields.serialize() + '&context=cart',
			url: '/ext/Cart/Request.php'
		});
	});
	$('input[name="operations-address-short"], input[name="operations-postal-code"]').on('focus', function () {
		$('.delivery-btn.w_hide').removeClass('w_hide');
	});
	let suggest = $('input[name="operations-address-short"]').eq(0);
	let parent = suggest.closest('.delivery-address');
	let token = suggest.data('token');
	suggest.suggestions({
		token: token,
		type: "ADDRESS",
  		constraints: {
			locations: [{
				city: $('#cart-city').data('city')
			},{
				city: $('#cart-city').data('region')
			}]
		},
		onSelect: function(suggestion) {
			suggest.removeClass('active');
			parent.addClass('active');
			parent.find('input[name="operations-address-short"]').val(cleanString2(suggestion.value,suggestion.data.city_with_type??suggestion.data.region_with_type))
			parent.find('input[name="operations-address"]').val(suggestion.value)
			parent.find('input[name="operations-postal-code"]').val(suggestion.data.postal_code)
			$('#deliveryAddressBtn').trigger('click');
		},
		onSearchStart: function(params) {
			suggest.addClass('active');
			parent.find('input[name="operations-address"]').val(cleanString2(''))
			parent.find('input[name="operations-postal-code"]').val('')
		},
		onSelectNothing: function(params) {
			suggest.removeClass('active');
		},
		// Добавляем обработчики ошибок
		onSearchError: function(query, jqXHR, textStatus, errorThrown) {
			suggest.removeClass('active');
			var errorMessage = 'Ошибка при работе с сервисом DaData';
			try {
				const errorData = JSON.parse(jqXHR.responseText);
				if (errorData.message) {
					errorMessage = errorData.message;
				}
			} catch (e) {
				console.error('Failed to parse DaData error response:', e);
			}
			$('.dadata-error').remove();
			console.error('DaData search error:', errorMessage);
			let errorElement = $('<div class="dadata-error" style="color:var(--color-attention);margin-top:var(--s);display:none"></div>');
			errorElement.text(errorMessage);
			suggest.after(errorElement);
			errorElement.fadeIn(500);
			//console.log(suggest);
			//errorElement.after(suggest);

			//errorElement.text(errorMessage).show();
			//console.error('DaData error:', error);
		},
	});
	//https://confluence.hflabs.ru/pages/viewpage.action?pageId=207454320
};
$(document).ready(() => {
	setTimeout(fnAddressInit,500);
});