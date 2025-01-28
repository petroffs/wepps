var readyViewOrderInit = function() {
	if ($('.pps_select').find('select').data('select2')) {
		//$('.pps_select').find('select').select2('destroy');		
	}
	/*$('.pps_select').find('select').select2({
		language: "ru",
		delay: 500
	});*/
	readyFormsInit();
	let order = $('.orders').children('.item[data-id="'+orderId+'"]');
	order.children('.item-field.price').find('span').text(utilsWepps.money(orderSum));
	$('select.quantity,.price>label>input').on('focus',function(event) {
		event.stopPropagation();
		$(this).closest('.item').find('a.list-item-save').removeClass('pps_hide');
	});
	$('select.quantity').off('change');
	$('select.quantity').on('change',function(event) {
		event.preventDefault();
		let el = $(this).closest('.item');
		let price = parseFloat(el.find('input[name="price"]').val());
		let sum = (price*$(this).val()).toFixed(2);
		el.find('div.price.sum').find('span').text(utilsWepps.money(sum));
	});
	$('div.products').find('a.list-item-save').off('click');
	$('div.products').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		let el = $(this).closest('.item');
		let obj = $('#view'+el.data('order'));
		let settings = {
			data: 'action=setProducts&id='+el.data('order')+'&index='+el.data('index')+'&products='+el.data('products')+'&price='+el.find('.price').find('input').val()+'&quantity='+el.find('select.quantity').val(),
			url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
			obj: obj
		}
		layoutWepps.request(settings);
	});
	$('div.products').find('a.list-item-remove').off('click');
	$('div.products').find('a.list-item-remove').on('click',function(event) {
		event.preventDefault();
		var el = $(this).closest('.item').eq(0);
		$("#dialog").html('<p>Подтвердите удаление</p>').dialog({
			'title':'Внимание!',
			'modal': true,
			'buttons' : [{
				text : "Удалить",
				icon : "ui-icon-close",
				click : function() {
					let obj = $('#view'+el.data('order'));
					let settings = {
						data: 'action=removeProducts&id='+el.data('order')+'&index='+el.data('index'),
						url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
						obj: obj
					}
					var layoutWeppsCustom = new LayoutWepps();
					layoutWeppsCustom.call = function() {
						$("#dialog").dialog('close');
					}
					layoutWeppsCustom.request(settings);
					$(this).dialog("close");
				}
			},{
				text : "Отмена",
				click : function() {
					$(this).dialog("close");
				}
			}]
		});
	});
	
	$('div.products').find('a.list-item-add').off('click');
	$('div.products').find('a.list-item-add').on('click',function(event) {
		event.preventDefault();
		let el = $(this).closest('.item');
		let obj = $('#view'+el.data('order'));
		let settings = {
			data: 'action=addProducts&id='+el.data('order')+'&products='+$('#add-products').val()+'&price='+$('#add-products-price').val()+'&quantity='+$('#add-products-quantity').val(),
			url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
			obj: obj
		}
		layoutWepps.request(settings);
		return;					
	});
	
	$('div.settings-wrapper').find('div.status').find('a.list-item-save').off('click');
	$('div.settings-wrapper').find('div.status').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		let el = $(this).closest('.settings-wrapper');
		let obj = $('#view'+el.data('order'));
		let settings = {
			data: 'action=setStatus&id='+el.data('order')+'&status='+el.find('select.status-select').val(),
			url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
			obj: obj
		}
		layoutWepps.request(settings);
		return;
	});
	$('div.settings-wrapper').find('div.payments').find('a.list-list-item-add').off('click');
	$('div.settings-wrapper').find('div.payments').find('a.list-item-add').on('click',function(event) {
		event.preventDefault();
		let el = $(this).closest('.settings-wrapper');
		let obj = $('#view'+el.data('order'));
		let settings = {
			data: 'action=addPayments&id='+el.data('order')+'&payments='+$('#add-payments').val(),
			url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
			obj: obj
		}
		layoutWepps.request(settings);
		return;
	});
	$('div.settings-wrapper').find('div.messages').find('a.list-item-add').off('click');
	$('div.settings-wrapper').find('div.messages').find('a.list-item-add').on('click',function(event) {
		event.preventDefault();
		let el = $(this).closest('.settings-wrapper');
		let obj = $('#view'+el.data('order'));
		let settings = {
			data: 'action=addMessages&id='+el.data('order')+'&messages='+$('#add-messages').val(),
			url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',
			obj: obj
		}
		layoutWepps.request(settings);
		return;
	});
}

readyViewOrderInit()
readyAdminWeppsInit();
if ($( "#add-products" ).length) {
	getSelect2Ajax({
		id : '#add-products',
		url: '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php?action=searchProducts',
		placeholder: 'Новый товар'
	},function(event) {
		let params = event.params.data;
		$('#add-products-price').val(params.price);
	});
}
