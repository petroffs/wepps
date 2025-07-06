<div class="w_3scol_view_medium">
    <div class="content-block cart-total">
    {if $operationsData.data.payments}
        <p>Ваш заказ №{$operationsData.data.order.Id} оплачен и передан в обработку.</p>
    {else}
        {$operationsData.data.order.PaymentDescrFinish}
        <label class="pps pps_button pps_button_important">
            <a href="/ext/Cart/Payments/Yookassa/Request.php?action=form&id={$operationsData.data.order.Alias}" class="pps_button">Оплатить заказ</a>
        </label>
    {/if}
    </div>
</div>