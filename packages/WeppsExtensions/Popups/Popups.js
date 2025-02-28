var readyPopupsPageInit = function() {
	$('#plain-popup').on('click',function() {
		layoutWepps.modal({ size:'small',content: $('#test')});
	});
	$('#plain-popup-medium').on('click',function() {
		layoutWepps.modal({ size:'medium',content: $('#test')});
	});
	$('#plain-popup-large').on('click',function() {
		layoutWepps.modal({ size:'large',content: $('#test')});
	});
	$('#ajax-popup').on('click',function() {
		layoutWepps.modal({ size:'medium',data:'action=test',url:'/ext/Popups/Request.php' });
	});
	$('#ajax-to-obj').on('click',function() {
		layoutWepps.request({ data:'action=test',url:'/ext/Popups/Request.php',obj:$('#test2') });
	});
	$('#ajax-hidden').on('click',function() {
		layoutWepps.request({ data:'action=test',url:'/ext/Popups/Request.php' });
	});
}
$(document).ready(readyPopupsPageInit);