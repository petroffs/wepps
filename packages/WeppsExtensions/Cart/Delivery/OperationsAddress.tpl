<div class="content-block delivery-address {if $deliveryOperations.active.address} active{/if}">
    <div class="w_grid w_3col w_gap_large">
        <div class="w_3scol">
            <div class="title">Город</div>
            <label class="pps pps_input">
                <input type="hidden" name="operations-city" value="{$deliveryOperations.data.deliveryCtiy.Title}" readonly/>
                <input type="hidden" name="operations-address-short" value="" readonly/>
                <input type="text" name="operations-city2" value="{$deliveryOperations.data.deliveryCtiy.Title}" disabled/>
            </label>
        </div>
        <div class="w_2scol">
            <div class="title">Адрес</div>
            <label class="pps pps_input label-dadata">
                <i class="pps_field_empty"></i>
                <input type="text" name="operations-address" value="{$deliveryOperations.active.address}" data-token="{$deliveryOperations.data.token}"/>
            </label>
        </div>
        <div>
            <div class="title">Индекс</div>
            <label class="pps pps_input">
                <input type="text" name="operations-postal-code" value="{$deliveryOperations.active['postal-code']}"/>
            </label>
        </div>
         <div class="delivery-btn w_hide">
            <label class="pps pps_button">
                <input type="button" name="" value="Сохранить" id="deliveryAddressBtn"/>
            </label>
        </div>
    </div>
</div>
{$deliveryMinify}