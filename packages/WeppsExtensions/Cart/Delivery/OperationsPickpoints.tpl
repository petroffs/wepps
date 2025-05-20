<div class="content-block delivery-pickpoints">
    <label class="pps pps_input">
        <input type="text" name="delivery-pickpoints-message" class="pps_hide" />
    </label>
    <div class="delivery-pickpoints-items">
        {foreach name="out" item="item" from=$deliveryOperations.data}
            <div class="delivery-pickpoints-item" data-id="{$item.Id}" data-code="{$item.Code}" data-postal-code="{$item.PostalCode}" data-name="{$item.Name}"
                data-work-time="{$item.WorkTime}" data-coords="{$item.Coords}" data-email="{$item.Email}" data-phone="{$item.Phone}" data-city="{$item.City}"
                data-address="{$item.Address}" data-zoom="{$item.MapZoom}">
            </div>
        {/foreach}
    </div>
    <div id="delivery-pickpoints-map"></div>
    <div id="delivery-pickpoints-address">
        <input type="text" name="address-id" value="" />
        <input type="text" name="address-title" value="" />
        <input type="text" name="address-city" value="" />
        <input type="text" name="address-street" value="" />
        <input type="text" name="address-postal-code" value="" />
        <div></div>
    </div>
</div>
{$deliveryMinify}