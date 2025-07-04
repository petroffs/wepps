<input type="hidden" name="operations-city" value="{$deliveryOperations.data.address.city}" autocomplete="off"/>
<input type="hidden" name="operations-address" value="{$deliveryOperations.data.address.address}" autocomplete="off"/>
<input type="hidden" name="operations-address-short" value="{$deliveryOperations.data.address['address-short']}" autocomplete="off"/>
<input type="hidden" name="operations-postal-code" value="{$deliveryOperations.data.address['postal-code']}" autocomplete="off"/>
<div class="content-block delivery-notice">
    {$deliveryOperations.data.text}
</div>
{$deliveryMinify}