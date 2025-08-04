cart.metrics({
    quantity: '{$cartSummary.quantity}'
});
var cartBtn = $('input.cart-add[data-id="{$get.id}"]');
if (cartBtn.siblings('a.cart-exists').length==0) {
    $('<a href="/cart/" class="cart-exists"></a>').insertBefore(cartBtn);
    cartBtn.val('В Корзине');
}