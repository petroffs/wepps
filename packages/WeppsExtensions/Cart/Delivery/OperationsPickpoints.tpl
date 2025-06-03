<div class="content-block delivery-pickpoints">
    <label class="pps pps_input">
        <input type="text" name="delivery-pickpoints-message" class="pps_hide" />
    </label>
    <div class="delivery-pickpoints-items">
        {foreach name="out" key="key" item="item" from=$deliveryOperations.data}
        <div class="delivery-pickpoints-item{if $deliveryOperations.active.id==$item.Id} active{/if}" data-id="{$item.Id}" data-code="{$item.Code}" data-postal-code="{$item.PostalCode}" data-name="{$item.Name}"
                data-work-time="{$item.WorkTime}" data-coords="{$item.Coords}" data-email="{$item.Email}" data-phone="{$item.Phone}" data-city="{$item.City}"
                data-address="{$item.Address}" data-zoom="{$item.MapZoom}" data-indx="{$key}">
            </div>
        {/foreach}
    </div>
    <div id="delivery-pickpoints-map"></div>
    <div id="delivery-pickpoints-operations">
        <input type="hidden" name="operations-id" value="{$deliveryOperations.active.id}" autocomplete="off"/>
        <input type="hidden" name="operations-title" value="{$deliveryOperations.active.title}" autocomplete="off"/>
        <input type="hidden" name="operations-city" value="{$deliveryOperations.active.city}" autocomplete="off"/>
        <input type="hidden" name="operations-address-short" value="{$deliveryOperations.active['address-short']}" autocomplete="off"/>
        <input type="hidden" name="operations-postal-code" value="{$deliveryOperations.active['postal-code']}" autocomplete="off"/>
        <div></div>
    </div>
</div>
{$deliveryMinify}