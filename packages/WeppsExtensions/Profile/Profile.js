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
    $('input[name="phone"]').inputmask("+7 (999) 999-99-99");
    $('.w_table').find('tr[data-id]').off('click').on('click', function (e) {
        window.location.href = (window.location.origin + window.location.pathname) + '?id=' + $(this).data('id');
    });
    $('#order-message-btn').off('click').on('click', function (e) {
        e.preventDefault();
        let obj = $('#order-message');
        if (obj.val() == '') {
            return;
        }
        layoutWepps.request({
            data: 'action=addOrdersMessage&id='+obj.data('id')+'&message='+obj.val(),
            url: '/ext/Profile/Request.php',
            obj : $('#pps-rows-wrapper')
        });
    });
};
$(document).ready(profileInit);