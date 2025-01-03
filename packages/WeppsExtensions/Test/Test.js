var readyTestInit = function() {
	$('a#ajax-test').on('click',function(e) {
		e.preventDefault();
		let id = $(this).data('id');
		layoutWepps.win({ size:'medium',data:'action=test&id='+id,url:'/ext/Test/Request.php' });
	})
}
$(document).ready(readyTestInit);