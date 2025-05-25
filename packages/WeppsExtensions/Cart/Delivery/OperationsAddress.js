var fnAddressInit = function () {
	$('#deliveryAddressBtn').on('click', function () {
		let fields = $('input[name^="operations-"]');
		layoutWepps.request({
			data: 'action=deliveryOperations&' + fields.serialize() + '&context=cart',
			url: '/ext/Cart/Request.php'
		});
	});
	let suggest = $('input[name="operations-address"]').eq(0);
	let token = suggest.data('token');
	suggest.suggestions({
		token: token,
		type: "ADDRESS",
  		constraints: {
			locations: {
				city: $('#cart-city').data('city')
			},
		},
		onSelect: function (suggestion) {
			console.log(suggestion);
			suggest.removeClass('active');
		},
		onSearchStart: function(params) {
			suggest.addClass('active');
		},
		onSelectNothing: function(params) {
			suggest.removeClass('active');
		}
	});
	//https://confluence.hflabs.ru/pages/viewpage.action?pageId=207454320
};
$(document).ready(fnAddressInit);