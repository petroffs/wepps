var fnAddressInit = function () {
    $('#deliveryAddressBtn').on('click',function() {
        let fields = $('input[name^="operations-"]');
		layoutWepps.request({
			data: 'action=deliveryOperations&' + fields.serialize() + '&context=cart',
			url: '/ext/Cart/Request.php'
		});
    });
};
$(document).ready(fnAddressInit);