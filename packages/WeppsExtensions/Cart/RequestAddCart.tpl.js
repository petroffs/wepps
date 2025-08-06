cart.metrics({
    quantity: '{$cartSummary.quantity}'
});
var cartBtn = $('input.cart-add[data-id="{$get.id}"]');
if (cartBtn.siblings('a.cart-exists').length==0) {
    $('<a href="/cart/" class="cart-exists"></a>').insertBefore(cartBtn);
    cartBtn.val('В Корзине');
}
var arr = "{$get.idv}".split(',');
console.log(arr);
$.each(arr,function(i,e) {
    console.log(e);
    $('a.cart-add-v[data-id="'+e+'"]').addClass('cart-add-v-exists');
});