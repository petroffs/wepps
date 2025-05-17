var readyOrdersInit = function() {
	$('.orders').children('.item').children('.item-field').on('click',function() {
		let id = $(this).parent().data('id');
		let obj = $('#view'+id);
		let str = 'action=viewOrder&id='+id;
		let settings = {
			data:str,
			url:'/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
			obj:obj
		};
		layoutWepps.request(settings);
		$('.orders').children('.item').children('.order-wrapper').html('');
		obj.removeClass('pps_hide');
		$('.orders').children('.item').removeClass('active');
		$(this).parent().addClass('active');
	});
	if ($('.orders').children('.item').length==1) {
		$('.orders').children('.item').children('.item-field').eq(0).trigger('click');	
	}
};
$(document).ready(readyOrdersInit);