$('#layerClose').on('click',function() {
	layoutWepps.remove();
});

$('#removeApply').on('click',function() {
	console.log();
	var id = $(this).data('id');
	layoutWepps.remove();
	
	setTimeout(function() {
		$('#'+id).fadeOut();
	},500);
	
	setTimeout(function() {
		layoutWepps.request('action=remove&id='+id,'/ext/Cart/Request.php',$('#cart-wrapper'));
	},600);
});