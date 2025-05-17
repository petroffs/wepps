var readyGalleryInit = function() {
	$('.image-gallery').magnificPopup({
		type: 'image',
		gallery: {
			enabled: true,
			tPrev: '',
			tNext: '',
			tCounter: '%curr% / %total%'
		},
		image: {
			titleSrc: 'title',
			cursor: ''
		}
	});
};
$(document).ready(readyGalleryInit);