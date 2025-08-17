var profileInit = function() {
	$('div.pps-option-nav').off('click');
    $('div#pps-option-nav').on('click',function(e) {
        e.preventDefault();
        let sidebar = $('section.sidebar');
        sidebar.toggleClass('w_hide_view_medium');
        $('#sidebar-medium').toggleClass('w_hide');
        if (!sidebar.hasClass('w_hide_view_medium')) {
            sidebar.detach().appendTo('#sidebar-medium');
        } else {
            sidebar.detach().prependTo('#content-wrapper');
        }
    });
    $('a[data-event]').off('click');
    $('a[data-event]').on('click',function(e) {
        e.preventDefault();
        switch ($(this).data('event')) {
            case 'sign-out':
                layoutWepps.request({
                    data: 'action=sign-out',
                    url: '/ext/Profile/Request.php'
                });
                break;
        };
    });
};
$(document).ready(profileInit);