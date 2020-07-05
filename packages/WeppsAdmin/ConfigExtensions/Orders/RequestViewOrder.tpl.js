var readyViewOrderInit = function() {
	let order = $('.orders').children('.item[data-id="'+orderId+'"]');
	order.children('.itm.price').find('span').text(layoutWepps.money(orderSumm));
	
	$('select.qtyselect,.price>label>input').on('focus',function(event) {
		event.stopPropagation();
		$(this).closest('.item').find('a.list-item-save').removeClass('pps_hide');
	});
	$('div.positions').find('a.list-item-save').off('click');
	$('div.positions').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		var element = $(this).closest('.item').eq(0);
		let id = element.data('position');
		let order = element.data('order');
		let obj = $('#view'+order);
		let price = element.find('.price').find('input').val();
		let qty = element.find('select.qtyselect').val();
		let str = 'action=setPositionQty&order='+order+'&id='+id+'&price='+price+'&qty='+qty;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
	});
	$('div.positions').find('a.list-item-remove').off('click');
	$('div.positions').find('a.list-item-remove').on('click',function(event) {
		event.preventDefault();
		var element = $(this).closest('.item').eq(0);
		$("#dialog").html('<p>Вы действительно желаете удалить позицию?</p>').dialog({
			'title':'Внимание!',
			'modal': true,
			'buttons' : [{
				text : "Удалить",
				icon : "ui-icon-close",
				click : function() {
					let id = element.data('position');
					let order = element.data('order');
					let obj = $('#view'+order);
					let str = 'action=removePosition&order='+order+'&id='+id;
					layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
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
	
	$('div.positions').find('a.list-item-add').off('click');
	$('div.positions').find('a.list-item-add').on('click',function(event) {
		event.preventDefault();
		var element = $(this).closest('.item').eq(0);
		let order = element.data('order');
		let obj = $('#view'+order);
		let qty = element.find('select.qtyselect').val();
		let title = $('#addPosition').val();
		let price = $('#addPositionPrice').val();
		let option = $('#addPositionOptions').attr('data-option-id');
		let id = $('#addPositionOptions').attr('data-position-id');
		let str = 'action=addPosition&order='+order+'&title='+title+'&price='+price+'&qty='+qty+'&id='+id+'&option='+option;
		layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php',obj);
	});
	
	if ($( "#addPosition" ).length) {
		$( "#addPosition" ).autocomplete({
		      source: "/packages/WeppsAdmin/ConfigExtensions/Orders/Request.php?action=searchPosition",
		      minLength: 2,
		      open: function( event, ui ) {
		    	  $('.itemAdd').children('div.price').text('');
		    	  $('#addPositionOptions').addClass('pps_hide');
		    	  $('#addPositionOptions').text('');
		    	  //$('#addPositionOptions').attr('data-option-id','');
		    	  //$('#addPositionOptions').attr('data-position-id','');
		    	  $('#addPositionPrice').val('');
		      },
		      select: function( event, ui ) {
		    	  /*
		    	   * Дейсвие при выборе
		    	   */
		    	  $('.itemAdd').children('div.price').text(layoutWepps.money(ui.item.Price));
		    	  $('#addPositionOptions').removeClass('pps_hide');
		    	  $('#addPositionOptions').text(ui.item.OptionsTitle);
		    	  $('#addPositionOptions').attr('data-option-id',ui.item.OptionsId);
		    	  $('#addPositionOptions').attr('data-position-id',ui.item.Id);
		    	  $('#addPositionPrice').val(ui.item.Price);
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