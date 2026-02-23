var readyBlocksAnimateInit = function () {
    // Функция для инициализации анимации
    const initFadeIn = () => {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const delayMs = 250; // Задержка в мс между появлениями блоков

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('visible')) {
                    // Найти индекс блока в его контейнере
                    const container = entry.target.closest('.w_blocks_wrapper');
                    const blocksInContainer = container ? Array.from(container.querySelectorAll('.fade-in, .slide-up')) : [entry.target];
                    const index = blocksInContainer.indexOf(entry.target);
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * delayMs);
                }
            });
        }, observerOptions);

        const applyObserver = () => {
            const fadeInElements = document.querySelectorAll('.fade-in:not(.observed), .slide-up:not(.observed)');
            fadeInElements.forEach(el => {
                el.classList.add('observed');
                observer.observe(el);
                // Для уже видимых элементов при загрузке
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0 && !el.classList.contains('visible')) {
                    const container = el.closest('.w_blocks_wrapper');
                    const blocksInContainer = container ? Array.from(container.querySelectorAll('.fade-in, .slide-up')) : [el];
                    const index = blocksInContainer.indexOf(el);
                    setTimeout(() => {
                        el.classList.add('visible');
                    }, index * delayMs);
                }
            });
        };

        // Применяем сразу
        applyObserver();

        // Наблюдаем за изменениями в DOM
        const mutationObserver = new MutationObserver(() => {
            applyObserver();
        });

        const targetNode = document.querySelector('.w_blocks') || document.body;
        mutationObserver.observe(targetNode, { childList: true, subtree: true });
    };
    initFadeIn();
};
var readyBlocksInit = function () {
	readyBlocksAnimateInit();
	if ($('.w_sortable').length == 0) {
		return;
	}
	$(".w_sortable").sortable({
		stop: function (event, ui) {
			let items = $(this).closest('.w_panel').find('.w_block');
			var str = '';
			$.each(items, function (num, elem) {
				str += $(elem).data('id') + ',';
			});
			str = str.substr(0, str.length - 1);
			layoutWepps.request({
				data: 'action=sortable&items=' + str,
				url: '/ext/Template/Blocks/Request.php'
			})
		}
	});
	$(".w_sortable").disableSelection();
};
$(document).ready(readyBlocksInit);