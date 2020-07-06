cartTopUpdate({
	'qtyTop' : qtyTop,
	'priceAmountTop' : priceAmountTop
});

$("#qtychange").on('change',function() {
	//console.log($(this).val());
	var str = 'action=addCart&id='+id+'&color='+colorstr+'&sizes='+sizestr+'&image='+image+'&add='+add+'&qty='+$(this).val();
	//console.log(str);
	layoutWepps.add(str,'/ext/Cart/Request.php');
});

$('#layerClose').on('click',function() {
	layoutWepps.remove();
});

$('#cartWelcome').on('click',function() {
	location.href='/cart/';
});