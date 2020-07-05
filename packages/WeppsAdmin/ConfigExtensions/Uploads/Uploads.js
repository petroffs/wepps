if (readyListsItemInit==undefined) {
	var readyListsItemInit = function() {
		
	}
}
var readyUploadInit = function() {
	$('form.list-data').find('a.file-remove').off('click');
	$('form.list-data').find('a.file-remove').on('click',function(event) {
		event.preventDefault();
		var item = $(this).closest('.item');
		var str = 'action=uploadRemove&filesfield='+item.data('id')+'&filename='+$(this).attr('rel')
		layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
		$(this).parent().remove();
	});
	
}
$(document).ready(readyUploadInit);