$('#layerClose').on('click',function() {
	layoutPPS.remove();
});

$('#removeApply').on('click',function() {
	console.log();
	var id = $(this).data('id');
	layoutPPS.remove();
	
	setTimeout(function() {
		$('#'+id).fadeOut();
	},500);
	
	setTimeout(function() {
		layoutPPS.request('action=remove&id='+id,'/ext/Cart/Request.php',$('#cart-wrapper'));
	},600);
});