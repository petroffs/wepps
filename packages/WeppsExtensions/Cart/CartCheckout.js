let editCheck = ()=> {
	var ids = "";	
	$('input[name="cart-check"]:checked').each(function(i,o){
		ids += $(o).val() + ",";
	})
	layoutWepps.request({
		data: 'action=check&id=' + ids + '&context=cart',
		url: '/ext/Cart/Request.php',
		obj: $('#cart-checkout')
	});
	return ids;
}
let editCheckAll = () => {
	let count = $('input[name="cart-check"]:checked').length;
	let countAll = $('input[name="cart-check"]').length;
	let el = $('#cart-check-all'); 
	if (count==countAll) {
		el.prop('checked',true);
	} else {
		el.prop('checked',false);
	}
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
		obj : $('input[name="cart-check"]'),
		event : 'change',
		fn : ()=> {
			editCheck();
		}
	});
	layoutWepps.handler({
		obj : $('#cart-check-all'),
		event : 'change',
		fn : ()=> {
			if ($('#cart-check-all').prop('checked')) {
				//console.log(1)
				$('input[name="cart-check"]').prop('checked',true);
			} else {
				//console.log(0)
				$('input[name="cart-check"]').prop('checked',false);	
			}
			editCheck();
		}
	});
	editCheckAll();
}
$(document).ready(cartCheckoutInit);