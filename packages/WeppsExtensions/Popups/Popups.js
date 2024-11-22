var readyPopupsPageInit = function() {
	$('#plain-popup').on('click',function() {
		layoutWepps.win({ size:'small',content: $('#test')});
	});
	$('#ajax-popup').on('click',function() {
		layoutWepps.win({ size:'medium',data:'action=test',url:'/ext/Popups/Request.php' });
	});
	$('#ajax-to-obj').on('click',function() {
		layoutWepps.request({ data:'action=test',url:'/ext/Popups/Request.php',obj:$('#test2') });
	});
	$('#ajax-hidden').on('click',function() {
		layoutWepps.request({ data:'action=test',url:'/ext/Popups/Request.php' });
	});
}
$(document).ready(readyPopupsPageInit);