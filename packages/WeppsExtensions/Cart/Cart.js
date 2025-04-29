class CartWepps {
	constructor(settings = {}) {
		this.settings = settings;
	}
	initCheckout() {
		this.addHandler();
		this.checkHandler();
		this.checkAllHandler();
		this.removeHandler();
		this.quantityHandler();
		this.favoritesHandler();
		this.checkAll()
		this.btnSettingsHandler()
	}
	initSettings() {
		this.citiesSearchHandler();
	}
	add() {

	}
	edit() {

	}
	favorites(self) {
		self.toggleClass('active');
		let id = self.closest('section').data('id');
		layoutWepps.request({
			data: 'action=favorites&id=' + id,
			url: '/ext/Cart/Request.php'
		});
	}
	remove() {

	}
	check(self) {
		var ids = "";
		$('input[name="cart-check"]:checked').each(function(i, o) {
			ids += $(o).val() + ",";
		})
		layoutWepps.request({
			data: 'action=check&id=' + ids + '&context=cart',
			url: '/ext/Cart/Request.php',
			obj: $('#cart-checkout')
		});
		return ids;
	}
	checkAll() {
		let count = $('input[name="cart-check"]:checked').length;
		let countAll = $('input[name="cart-check"]').length;
		let el = $('#cart-check-all');
		if (count == countAll) {
			el.prop('checked', true);
		} else {
			el.prop('checked', false);
		}
	}
	metrics() {
		$('#cartTopQty').removeClass('pps_hide');
		$('#cartTopQty').text(data.qtyTop);
		$('#cartTopPriceAmount').parent().removeClass('pps_hide');
		$('#cartTopPriceAmount').closest('.itm').addClass('active');
		$('#cartTopPriceAmount').text(data.priceAmountTop);
	}
	addHandler() {
		$('.cart-add').on('click', function(e) {
			e.preventDefault();
			let id = $(this).data('id');
			console.log(id);
			layoutWepps.request({
				data: 'action=add&id=' + id,
				url: '/ext/Cart/Request.php'
			});
		});
	}
	checkHandler() {
		layoutWepps.handler({
			obj: $('input[name="cart-check"]'),
			event: 'change',
			fn: (self) => {
				this.check(self);
			}
		});
	}
	checkAllHandler() {
		layoutWepps.handler({
			obj: $('#cart-check-all'),
			event: 'change',
			fn: (self) => {
				if (self.prop('checked')) {
					$('input[name="cart-check"]').prop('checked', true);
				} else {
					$('input[name="cart-check"]').prop('checked', false);
				}
				this.check(self);
			}
		});
	}
	quantityHandler() {
		let editTimeout = null;
		formWepps.minmaxAfter = function(id, inputVal) {
			clearTimeout(editTimeout);
			editTimeout = setTimeout(() => {
				layoutWepps.request({
					data: 'action=edit&id=' + id + '&quantity=' + inputVal,
					url: '/ext/Cart/Request.php',
					obj: $('#cart-checkout')
				});
			}, 300);
		}
		formWepps.minmax();
	}
	removeHandler() {
		$('.cart-remove').on('click',function(e) {
			e.preventDefault();
			if ($(this).hasClass('active')) {
				let id = $(this).closest('section').data('id');
				layoutWepps.request({
					data: 'action=remove&id=' + id,
					url: '/ext/Cart/Request.php',
					obj: $('#cart-checkout')
				});
			}
			$(this).addClass('active').find('span').text('Потдвердить удаление');
		});
	}
	favoritesHandler() {
		layoutWepps.handler({
			obj: $('.cart-favorite'),
			event: 'click',
			fn: (self) => {
				this.favorites(self);
			}
		});
	}
	btnSettingsHandler() {
		$('#cart-btn-settings').on('click',function() {
			window.location.href = '/cart/settings.html';
		});
	}
	citiesSearchHandler() {
		let suggestionsRegions = new SuggestionsWepps({
			input: 'cart-region',
			action: 'cities',
			url: '/ext/Cart/Request.php'
		});
		suggestionsRegions.init();
		suggestionsRegions.afterSelectItem = function (self, suggestions, selectedIndex) {
			const selectedItem = suggestions.eq(selectedIndex);
			if (selectedItem.length && selectedIndex > -1) {
				$(self).val(selectedItem.text())
				layoutWepps.request({
					data: 'action=delivery&citiesId=' + selectedItem.data('id') + '&context=cart',
					url: '/ext/Cart/Request.php',
					obj: $('#cart-delivery-settings').eq(0)
				});
			}
		}
	}
}

let cart = new CartWepps();