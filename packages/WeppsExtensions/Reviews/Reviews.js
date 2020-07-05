$(document).ready(function() {
	$('form').find('.rateitems').on('mousemove',function(event) {
		var obj = $(this).find('.rateitem');
		var pos = $(this).offset();
	    var elem_left = pos.left;
	    var Xinner = event.pageX - elem_left;
	    setRatesClass(obj,Xinner);
	});
	
	$('form').find('.rateitems').on('mouseleave',function(event) {
		var obj = $(this).find('.rateitem');
		var rate = parseInt($('#setRate').html());
		if (!rate) rate = 0;
		var Xinner = rate * 20 + 3;
	    setRatesClass(obj,Xinner);
	});
	
	$('form').find('.rateitems').on('click',function(event) {
		var obj = $(this).find('.rateitem');
		var pos = $(this).offset();
	    var elem_left = pos.left;
	    var Xinner = event.pageX - elem_left;
	    var rate = setRatesClass(obj,Xinner);
	    $('#setRate').html(rate);
	    
	    $('input[name="rate"]').val(rate);
	    
	});
});

var setRatesClass = function(obj,Xinner) {
	var i = 0;
    if (Xinner<=0) {
    	i = 0;
	} else if (Xinner<=23) {
    	i = 1;
    } else if (Xinner<=46) {
    	i = 2;
    } else if (Xinner<=69) {
    	i = 3;
    } else if (Xinner<=92) {
    	i = 4;
    } else if (Xinner<=115) {
    	i = 5;
    }
    removeRatesClass(obj);
	obj.addClass('rateitem'+i)
	
	return i;
}

var removeRatesClass = function(obj) {
	obj.removeClass('rateitem0');
	obj.removeClass('rateitem1');
	obj.removeClass('rateitem2');
	obj.removeClass('rateitem3');
	obj.removeClass('rateitem4');
	obj.removeClass('rateitem5');
}