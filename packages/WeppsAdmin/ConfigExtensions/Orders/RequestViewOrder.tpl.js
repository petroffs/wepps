var readyViewOrderInit = function() {
	if ($('.pps_select').find('select').data('select2')) {
		$('.pps_select').find('select').select2('destroy');		
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
		var element = $(this).closest('.item').eq(0);
		let order = element.data('order');
		let obj = $('#view'+order);
		let qty = element.find('select.quantity').val();
		let title = $('#add-products').val();
		let price = $('#add-products-price').val();
		let option = $('#add-products-options').attr('data-option-id');
		let id = $('#add-products-options').attr('data-position-id');
		let str = 'action=addProducts&order='+order+'&title='+title+'&price='+price+'&qty='+qty+'&id='+id+'&option='+option;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
	});
	
	if ($( "#add-products" ).length) {
		$( "#add-products" ).autocomplete({
		      source: "/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php?action=searchProducts",
		      minLength: 2,
		      open: function( event, ui ) {
		    	  $('.item-add').children('div.price').text('');
		    	  $('#add-products-options').addClass('pps_hide');
		    	  $('#add-products-options').text('');
		    	  //$('#addPositionOptions').attr('data-option-id','');
		    	  //$('#addPositionOptions').attr('data-position-id','');
		    	  $('#addPositionPrice').val('');
		      },
		      select: function( event, ui ) {
		    	  /*
		    	   * Дейсвие при выборе
		    	   */
		    	  $('.itemAdd').children('div.price').text(layoutWepps.money(ui.item.Price));
		    	  $('#add-products-options').removeClass('pps_hide');
		    	  $('#add-products-options').text(ui.item.OptionsTitle);
		    	  $('#add-products-options').attr('data-option-id',ui.item.OptionsId);
		    	  $('#add-products-options').attr('data-position-id',ui.item.Id);
		    	  $('#add-products-price').val(ui.item.Price);
		      }
		}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		      return $( "<li>" )
		      //ampoules DirectoryUrl
		        .append( "<div class='result pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_row_nowrap'>" +
		        		 "	<div class='pps_flex_23 val'><div class\"search-value\">" + item.Name + "<br/>"+item.Articul+"</div></div>" +
		        		 "	<div class='pps_flex_16 val'><span>" + item.OptionsTitle + "</span></div>" +
		        		 "	<div class='pps_flex_16 val'><span>" + layoutWepps.money(item.Price) + "</span></div>" +
		        		 "</div>")
		        .appendTo( ul );
		 };
	}
	$('div.actions').find('div.status').find('a.list-item-save').off('click');
	$('div.actions').find('div.status').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		let element = $(this).closest('.status').find('select.statusselect');
		let order = element.data('order');
		let obj = $('#view'+order);
		let id = element.val();
		let str = 'action=setOrderStatus&order='+order+'&id='+id;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
	});
	$('div.actions').find('div.payment').find('a.list-item-save').off('click');
	$('div.actions').find('div.payment').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		let element = $('#addPaymentValue');
		let order = element.data('order');
		let obj = $('#view'+order);
		let paymentValue = element.val();
		let str = 'action=setOrderPayment&order='+order+'&value='+paymentValue;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
	});
	$('div.actions').find('div.messages').find('a.list-item-add').off('click');
	$('div.actions').find('div.messages').find('a.list-item-add').on('click',function(event) {
		event.preventDefault();
		let element = $('#addCommentValue');
		let order = element.data('order');
		let obj = $('#view'+order);
		let attach = $('#addOrderPositionsToMessage').prop('checked');
		let payment = $('#addPaymentLink').prop('checked');
		let str = 'action=addOrderMessage&order='+order+'&value='+element.val()+'&attach='+attach+'&payment='+payment;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
	});
}

readyViewOrderInit()
//$(document).ready(readyViewOrderInit);
readyAdminWeppsInit();