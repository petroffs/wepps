var error404Init = function() {
	$('#error404_search').on('click',function(e) {
		e.preventDefault();
		console.log(1);
	});
}
$(document).ready(error404Init);