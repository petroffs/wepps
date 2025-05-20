<div class="content-block delivery-pickpoints">
    <label class="pps pps_input">
        <input type="text" name="points-message" class="pps_hide" />
    </label>
    <div class="points">
        {foreach name="out" item="item" from=$deliveryOperations.data}
            <div class="point" data-id="{$item.Id}" data-code="{$item.Code}" data-postal-code="{$item.PostalCode}" data-name="{$item.Name}"
                data-work-time="{$item.WorkTime|replace:':::':'<br/>'}" data-coords="{$item.Coords}"
                data-email="{$item.Email}" data-phone="{$item.Phone}" data-city="{$item.City}"
                data-address="{$item.Address}" data-zoom="{$item.MapZoom}">
            </div>
        {/foreach}
    </div>

    <div id="delivery-pickpoints-map"></div>
    <div id="pointAddress">
        <input type="hidden" name="pointId" value="" />
        <input type="hidden" name="pointTitle" value="" />
        <input type="hidden" name="pointCity" value="" />
        <input type="hidden" name="pointStreet" value="" />
        <input type="hidden" name="pointAddressIndex" value="" />
        <div></div>
    </div>
</div>
{$deliveryMinify}