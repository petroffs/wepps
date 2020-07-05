


<div class="page contacts">
	{if $contacts.0.Street}
		<div class="param mapData pps_hide" data-coord="{$contacts.0.AdGoogle}" data-title="{$contacts.0.Name}" data-descr="{$contacts.0.Street}">{$contacts.0.Street}</div>
		{/if}
	<div id="map"></div>
	
</div>