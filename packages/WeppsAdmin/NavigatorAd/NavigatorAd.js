var readyListsInit = function() {
	if ($( "#navigator-search" ).length) {
		$( "#navigator-search" ).autocomplete({
		      source: "/packages/WeppsAdmin/NavigatorAd/Request.php?action=search",
		      minLength: 1,
		      select: function( event, ui ) {
		    	  location.href = ui.item.Url;
		      }
		}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		      return $( "<li>" )
		        .append( "<div class='pps_padding'>" +
		        		 "	<div class\"search-value\">" + item.value + " (" + item.id + ")</div>" +
		        		 "</div>")
		        .appendTo( ul );
		 };
	}
	
	$('.pps_list.dir').find('i').on('click',function(event){
		var parent1 = $(this).closest('li').data('id');
		var set1 = $('[data-parent="'+parent1+'"]')
		//set1 = $(this).closest('li');
		//console.log(set1);
		if (set1.hasClass('pps_hide')) {
			set1.removeClass('pps_hide');
		} else {
			set1.addClass('pps_hide');
		}
		
	});
	
	$('.pps_list.dir:not(.level0)').addClass('pps_hide');
	
	var active = $('.pps_list.dir').find('li.active');
	var level = active.closest('ul').data('level')
	
	if (level>0) {
		for (var i = 0; i <= level; i++) {
			active.closest('ul.level'+i).removeClass('pps_hide');
		}
	}
	
}
$(document).ready(readyListsInit);
