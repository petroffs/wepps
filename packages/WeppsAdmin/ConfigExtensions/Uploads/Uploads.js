if (readyListsItemInit==undefined) {
	var readyListsItemInit = function() {
		
	}
}
var readyUploadInit = function() {
	$('form.list-data').find('a.file-remove').off('click');
	$('form.list-data').find('a.file-remove').on('click',function(event) {
		event.preventDefault();
		let item = $(this).closest('.item');
		let str = 'action=uploadRemove&filesfield='+item.data('id')+'&filename='+$(this).attr('rel')
		let settings = {
			data:str,
			url:'/packages/WeppsAdmin/Lists/Request.php',
		}
		layoutWepps.request(settings);
		$(this).parent().remove();
	});
	
}
$(document).ready(readyUploadInit);