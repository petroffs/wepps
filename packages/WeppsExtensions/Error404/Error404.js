var readyError404Init = function() {
	$('.btn').find('input').eq(0).on('click',function() {
		window.history.back();
	});
	$('.btn').find('input').eq(1).on('click',function() {
		location.href = '/';
	});
}
$(document).ready(readyError404Init);