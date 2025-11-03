var cartEmptyInit = function () {
    $('#cart-profile').on('click', function (e) {
        e.preventDefault();
        layoutWepps.modal({ size: 'medium', data: 'action=sign-in-popup', url: '/ext/Profile/Request.php' });
    });
};
$(document).ready(cartEmptyInit);