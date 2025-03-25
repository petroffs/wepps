let editActive = ()=> {
	var ids = "";	
	$('input[name="cart-active"]:checked').each(function(i,o){
		ids += $(o).val() + ",";
	})
	if (ids) {
		console.log(ids);
	} else {
		console.log('remove');
	}
	return ids;
}

let cartCheckoutInit = function() {
	let editTimeout = null;
	formWepps.minmaxAfter = function(id,inputVal) {
		clearTimeout(editTimeout);
		editTimeout = setTimeout(() => {
			layoutWepps.request({
				data: 'action=edit&id=' + id + '&quantity='+inputVal+'&context=cart',
				url: '/ext/Cart/Request.php',
				obj: $('#cart-checkout')
			});
		}, 300);
	}
	formWepps.minmax();
	
	layoutWepps.handler({
		obj : $('input[name="cart-active"]'),
		event : 'change',
		fn : ()=> {
			let ids = editActive();
		}
	});
	
	layoutWepps.handler({
		obj : $('#cart-active-all'),
		event : 'change',
		fn : ()=> {
			if ($('#cart-active-all').prop('checked')) {
				$('input[name="cart-active"]').prop('checked',true);
			} else {
				$('input[name="cart-active"]').prop('checked',false);	
			}
			let ids = editActive();
		}
		
	});
}
$(document).ready(cartCheckoutInit);