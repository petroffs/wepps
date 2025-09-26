class CartWepps {
	constructor(settings = {}) {
		this.settings = settings;
	};
	init() {
		this.addHandler();
		this.checkHandler();
		this.checkAllHandler();
		this.removeHandler();
		this.quantityHandler();
		this.favoritesHandler();
		this.checkAll();
		this.btnCheckoutHandler()
	};
	initCheckout() {
		this.citiesHandler();
		this.deliveryHandler();
		this.paymentsHandler();
		this.btnConfirmtHandler();
		formsInit();
	};
	add() {

	};
	edit() {

	};
	favorites(self) {
		self.toggleClass('active');
		let id = self.closest('section').data('id');
		layoutWepps.request({
			data: 'action=favorites&id=' + id,
			url: '/ext/Cart/Request.php'
		});
	};
	remove() {

	};
	check(self) {
		var ids = "";
		$('input[name="cart-check"]:checked').each(function(i, o) {
			ids += $(o).val() + ",";
		});
		layoutWepps.request({
			data: 'action=check&id=' + ids + '&context=cart',
			url: '/ext/Cart/Request.php',
			obj: $('#cart-default')
		});
		return ids;
	};
	checkAll() {
		let count = $('input[name="cart-check"]:checked').length;
		let countAll = $('input[name="cart-check"]').length;
		let el = $('#cart-check-all');
		if (count == countAll) {
			el.prop('checked', true);
		} else {
			el.prop('checked', false);
		}
	};
	metrics(obj) {
		if (obj.quantity) {
			$('a#header-cart').attr('data-metrics',obj.quantity);
			$('a#footer-cart').attr('data-metrics',obj.quantity);
		}
	};
	addVariationsHandler() {
		$('.cart-add-v').off('click');
		$('.cart-add-v').on('click', function (e) {
			e.preventDefault();
			if ($(this).hasClass('pps_disabled')) {
				return;
			}
			$(this).toggleClass('active');
			var disabled = true;
			if ($('.cart-add-v.active').length > 0) {
				disabled = false;
			}
			$(this).closest($('.prices')).find('input.cart-add').prop('disabled', disabled);
			//$('input.cart-add').prop('disabled', disabled);
		});
	};
	addHandler() {
		$('.cart-add').off('click');
		$('.cart-add').on('click', function(e) {
			e.preventDefault();
			let id = $(this).data('id');
			let idv = [];
			let variations = $('.cart-add-v.active');
			if (variations.length > 0) {
				variations.each(function () {
					idv.push($(this).data("id"));
				});
				layoutWepps.request({
					data: 'action=add&id=' + id + '&idv=' + idv.toString(),
					url: '/ext/Cart/Request.php'
				});	
				return;
			} else if ($(this).data('idv')>0) {
				layoutWepps.request({
					data: 'action=add&id=' + id + '&idv=' + $(this).data('idv'),
					url: '/ext/Cart/Request.php'
				});
			} else if ($(this).data('popup-v')==1) {
				layoutWepps.modal({
					size: 'medium',
					data: 'action=variations&id=' + id,
					url: '/ext/Cart/Request.php'
				});
			}
		});
	};
	checkHandler() {
		layoutWepps.handler({
			obj: $('input[name="cart-check"]'),
			event: 'change',
			fn: (self) => {
				this.check(self);
			}
		});
	};
	checkAllHandler() {
		layoutWepps.handler({
			obj: $('#cart-check-all'),
			event: 'change',
			fn: (self) => {
				if (self.prop('checked')) {
					$('input[name="cart-check"]').prop('checked', true);
				} else {
					$('input[name="cart-check"]').prop('checked', false);
				};
				this.check(self);
			}
		});
	};
	quantityHandler() {
		let editTimeout = null;
		formWepps.minmaxAfter = function(id, inputVal) {
			clearTimeout(editTimeout);
			editTimeout = setTimeout(() => {
				layoutWepps.request({
					data: 'action=edit&id=' + id + '&quantity=' + inputVal,
					url: '/ext/Cart/Request.php',
					obj: $('#cart-default')
				});
			}, 300);
		};
		formWepps.minmax();
	};
	removeHandler() {
		$('.cart-remove').off('click');
		$('.cart-remove').on('click',function(e) {
			e.preventDefault();
			if ($(this).hasClass('active')) {
				let id = $(this).closest('section').data('id');
				layoutWepps.request({
					data: 'action=remove&id=' + id,
					url: '/ext/Cart/Request.php',
					obj: $('#cart-default')
				});
			};
			$(this).addClass('active').find('span').text('Потдвердить удаление');
		});
	};
	favoritesHandler() {
		layoutWepps.handler({
			obj: $('.cart-favorite'),
			event: 'click',
			fn: (self) => {
				this.favorites(self);
			}
		});
	};
	btnCheckoutHandler() {
		$('#cart-btn-checkout').off('click');
		$('#cart-btn-checkout').on('click',function(e) {
			e.preventDefault();
			if ($(this).data('auth')=='0') {
				$('a#header-profile').trigger('click');
				return;
			}
			window.open('/cart/checkout.html','_self');
		});
		// window.addEventListener('popstate', (event) => {
		// 	console.log('restore');
		//   });
		document.addEventListener('visibilitychange', () => {
			if (!document.hidden) {
				window.location.reload();
			}
		});
	};
	citiesHandler() {
		let suggestionsRegions = new SuggestionsWepps({
			input: 'cart-city',
			action: 'cities',
			url: '/ext/Cart/Request.php',
		});
		suggestionsRegions.init();
		suggestionsRegions.afterSelectItem = function (self, suggestions, selectedIndex) {
			const selectedItem = suggestions.eq(selectedIndex);
			if (selectedItem.length && selectedIndex > -1) {
				$(self).val(selectedItem.text());
				layoutWepps.request({
					data: 'action=delivery&citiesId=' + selectedItem.data('id') + '&context=cart',
					url: '/ext/Cart/Request.php',
					obj: $('#cart-default')
				});
			}
		}
	};
	deliveryHandler() {
		let obj = $('input[type="radio"][name="delivery"]');
		if (!obj.length) {
			return;
		};
		$('#cart-delivery-checkout').removeClass('w_hide');
		obj.off('change');
		obj.change(function (e) { 
			e.preventDefault();
			layoutWepps.request({
				data: 'action=payments&deliveryId=' + $(this).val() + '&context=cart',
				url: '/ext/Cart/Request.php',
				obj: $('#cart-default')
			});
		});
	};
	paymentsHandler() {
		let obj = $('input[type="radio"][name="payments"]');
		if (!obj.length) {
			return;
		};
		$('#cart-payments-checkout').removeClass('w_hide');
		obj.off('change');
		obj.change(function (e) { 
			e.preventDefault();
			if ($(this).data('auth')=='0') {
				$('a#header-profile').trigger('click');
				return;
			}
			layoutWepps.request({
				data: 'action=shipping&paymentsId=' + $(this).val() + '&context=cart',
				url: '/ext/Cart/Request.php',
				obj: $('#cart-default')
			});
		});
	};
	btnConfirmtHandler() {
		$('#cart-btn-confirm').off('click');
		$('#cart-btn-confirm').on('click',function(e) {
			e.preventDefault();
			let serialize = $('#cart-default').find('input,textarea').serialize();
			layoutWepps.request({
				data: 'action=addOrder&context=cart&'+serialize,
				url: '/ext/Cart/Request.php'
			});
		});
	}
	displayExists() {
		$('input.cart-add').val('В Корзину');
		$('a.cart-exists').each(function(i,e) {
			$(e).siblings('input').val('В Корзине')
		});
	}
};
let cart = new CartWepps();