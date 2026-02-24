var blocksTabsInit = function() {
	$('.li-tabs a').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $li = $this.closest('.li-tabs');
        var index = $li.index();
        var $content = $this.closest('.w_blocks').find('.block-tabs-content .block-tabs').eq(index);
        $this.closest('.w_blocks').find('.block-tabs-content .block-tabs').removeClass('active');
        $this.closest('.w_blocks').find('.li-tabs a').removeClass('active');
        $content.addClass('active');
        $this.addClass('active');
    });
};
$(document).ready(blocksTabsInit);