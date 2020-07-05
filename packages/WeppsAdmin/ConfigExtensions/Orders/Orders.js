var readyOrdersInit = function() {
	$('.orders').children('.item').children('.itm').on('click',function() {
		let id = $(this).parent().data('id');
		let obj = $('#view'+id);
		let str = 'action=viewOrder&id='+id;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
		$('.orders').children('.item').children('.order').html('');
		obj.removeClass('pps_hide');
		$('.orders').children('.item').removeClass('active');
		$(this).parent().addClass('active');
	});
}
$(document).ready(readyOrdersInit);