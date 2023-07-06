var readyGalleryInit = function() {
	$('.image-gallery').magnificPopup({
		type: 'image',
		gallery: {
			enabled: true,
			tPrev: '',
			tNext: '',
			tCounter: '<span class="mfp-counter">%curr% / %total%</span>'
		},
		image: {
			titleSrc: 'title',
			cursor: ''
		}
	});
}
$(document).ready(readyGalleryInit);