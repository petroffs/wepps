cart.metrics({
    quantity: '{$cartSummary.quantity}'
});
var cartBtn = $('input.cart-add[data-id="{$get.id}"]');
if (cartBtn.siblings('a.cart-exists').length==0) {
    $('<a href="/cart/" class="cart-exists"></a>').insertBefore(cartBtn);
    cartBtn.val('В Корзине');
}
var arr = "{$get.idv}".split(',');
$.each(arr,function(i,e) {
    $('a.cart-add-v[data-id="'+e+'"]').addClass('cart-add-v-exists');
});