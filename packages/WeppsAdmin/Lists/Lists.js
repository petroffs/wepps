var readyListsInit = function() {
	var isMobile = false;
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i
			.test(navigator.userAgent)) {
		isMobile = true;
	}
	if (isMobile == false) {
		var mx = 0;
		$(".draggable").on({
			mousemove : function(e) {
				var mx2 = e.pageX - this.offsetLeft;
				if (mx)
					this.scrollLeft = this.sx + mx - mx2;
			},
			mousedown : function(e) {
				this.sx = this.scrollLeft;
				mx = e.pageX - this.offsetLeft;
			}
		});
		$(document).on("mouseup", function() {
			mx = 0;
		});
	}

	var cntrlIsPressed = false;
	$(document).keydown(function(event){
	    if(event.which=="17")
	        cntrlIsPressed = true;
	});
	$(document).keyup(function(){
	    cntrlIsPressed = false;
	});
	$('tr[data-url]').on('dblclick',function(event) {
		
		if(cntrlIsPressed == true) {
			window.open($(this).data('url'));
		} else {
			location.href=$(this).data('url');
		}
	});
	
	$('tr[data-url]').contextmenu(function() {
		//layoutWepps.add('action=test', '/packages/WeppsAdmin/Lists/Request.php');
		//$('#dialog').dialog();
		//layoutWepps.dialog('action=form', '/packages/WeppsAdmin/Lists/Request.php');
		return false;
	});
	$('a.filter').on('click',function(event) {
		event.preventDefault();
		if (!$(this).siblings('.filter2').eq(0).length) {
			$('.filter').removeClass('active');
			$(this).addClass('active');
			var filter2style = 'filter2'+'_'+$(this).data('field');
			var elem = $('<div>load...</div>');
			elem.addClass('filter2');
			elem.attr('id',filter2style);
			$('.filter2').remove();
			$(this).after(elem);
			let str = 'action=filter&list='+$(this).data('list')+'&field='+$(this).data('field')+'&orderby='+$(this).data('orderby');
			let settings = {
						data: str,
						url: '/packages/WeppsAdmin/Lists/Request.php',
						obj: $('#'+filter2style)
					}
			layoutWepps.request(settings);
		} else {
			$(this).removeClass('active');
			$('.filter2').remove();
		}
	});
	$('input.search').on('change',function(event) {
		var field = $(this).closest('form').find('select').val();
		var search = $(this).val();
		var orderby = ($(this).data('orderby')!='') ? 'orderby='+$(this).data('orderby')+'&' : '';
		var href = '/_pps/lists/'+$(this).data('list')+'/?'+orderby+'field='+field+'&search='+search;
		location.href = href;
	});
	if ($( "#list-search" ).length) {
		$( "#list-search" ).autocomplete({
		      source: "/packages/WeppsAdmin/Lists/Request.php?action=search",
		      minLength: 2,
		      select: function( event, ui ) {
		    	  console.log(1);
		    	  location.href = ui.item.Url;
		      }
		}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		      return $( "<li>" )
		        .append( "<div class='pps_padding'>" +
		        		 "	<div class\"search-value\">" + item.value + "</div>" +
		        		 "</div>")
		        .appendTo( ul );
		 };
	}
	$('a#export').on('click',function(event) {
		event.preventDefault();
		location.href='/packages/WeppsAdmin/Lists/Request.php?action=export&list='+$(this).data('list');
	});
	
}
$(document).ready(readyListsInit);
