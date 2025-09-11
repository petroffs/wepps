var profileInit = function () {
    $('div#pps-option-nav').off('click').on('click', function (e) {
        e.preventDefault();
        let nav = $('.profile-nav');
        nav.toggleClass('w_hide_view_medium');
    });
    $('a[data-event]').off('click').on('click', function (e) {
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