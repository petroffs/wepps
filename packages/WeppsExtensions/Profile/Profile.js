var profileInit = function () {
    $('div#wepps-option-nav').off('click').on('click', function (e) {
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
            obj : $('#profile-loader')
        });
    });
};

// Добавляем в profileInit инициализацию темы
var profileThemeInit = function() {
    const $themeSelect = $('#theme-select');
    if ($themeSelect.length === 0) return;
    
    const savedTheme = localStorage.getItem('w_theme') || 'auto';
    $themeSelect.val(savedTheme);
    
    // Функция определения системной темы
    function getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }
    
    // Применяем тему
    function applyTheme(theme) {
        if (theme === 'auto') {
            theme = getSystemTheme();
        }
        $('html').attr('data-theme', theme);
    }
    
    // Применяем сохраненную тему
    applyTheme(savedTheme);
    
    // Сохраняем тему в localStorage при изменении
    $themeSelect.off('change').on('change', function() {
        const selectedTheme = $(this).val();
        localStorage.setItem('w_theme', selectedTheme);
        applyTheme(selectedTheme);
    });
    
    // Отслеживаем изменения системной темы
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            const currentTheme = localStorage.getItem('w_theme') || 'auto';
            if (currentTheme === 'auto') {
                applyTheme('auto');
            }
        });
    }
};

$(document).ready(function () {
    profileInit();
    profileThemeInit();
});