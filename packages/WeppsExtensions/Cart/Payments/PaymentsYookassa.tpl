<div class="w_3scol_view_medium">
    <div class="content-block cart-total">
        {$operationsData.data.order.PaymentDescrFinish}
        <label class="pps pps_button pps_button_important">
            <a href="/ext/Cart/Payments/PaymentsYookassa.php?action=form&id={$operationsData.data.order.Alias}" class="pps_button">Оплатить заказ</a>
        </label>
    </div>
</div>