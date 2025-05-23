<div class="content-block">
    <div class="w_grid w_3col w_gap_large">
        <div>
            <div class="title">Индекс</div>
            <label class="pps pps_input">
                <input type="text" name="operations-postal-code" value="{$deliveryOperations.active['postal-code']}"/>
            </label>
        </div>
        <div class="w_2scol">
            <div class="title">Улица</div>
            <label class="pps pps_input">
                <input type="text" name="operations-street" value="{$deliveryOperations.active.street}"/>
            </label>
        </div>
        <div>
            <div class="title">Дом, корпус</div>
            <label class="pps pps_input">
                <input type="text" name="operations-house" value="{$deliveryOperations.active.house}"/>
            </label>
        </div>
        <div>
            <div class="title">Квартира, офис</div>
            <label class="pps pps_input">
                <input type="text" name="operations-flat" value="{$deliveryOperations.active.flat}"/>
            </label>
        </div>
         <div>
            <div class="title">&nbsp;</div>
            <label class="pps pps_button">
                <input type="button" name="" value="Сохранить" id="deliveryAddressBtn"/>
            </label>
        </div>
    </div>
</div>
{$deliveryMinify}