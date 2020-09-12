<div class="pps_flex pps_flex_row pps_flex_row_top extension Products">
	<div
		class="pps_flex_14 pps_flex_13_view_medium pps_flex_11_view_small leftBlock">
		<ul class="extNav">
			{foreach name="out" item="item" from=$extensionNav}
			<li {if $contentId==$item.Id} class="active"{/if}><a
				href="{$item.Url}">{$item.Name}</a></li> {/foreach}
		</ul>
		{foreach name="out" item="item" key="key" from=$filtersNav} {assign
		var=hide value=""}
		<div class="extFilters" data-id='{$key}'>
			<div class="title">{$item.0.PropertyName}</div>
			<div class="items">
				<ul>
					{foreach name="o" item="i" from=$item} {if
					$smarty.foreach.o.iteration>10} {assign var=hide value="hide"}
					{/if}
					<li class="{$hide}"><label class="pps pps_checkbox"> <input
							type="checkbox" name="{$i.Alias}" /> <span>{$i.PValue} <span>{$i.Co}</span></span>
					</label></li> {/foreach} {if $hide!=""}
					<li class="more"><a href="">Еще</a></li> {/if}
				</ul>
			</div>
		</div>
		{/foreach}

	</div>
	<div
		class="pps_flex_34 pps_flex_23_view_medium pps_flex_11_view_small centerBlock">
		<div class="extProductsItems">
			<h1>{$content.Name}</h1>
			{if $content.Text1}
			<div class="descr">{$content.Text1}</div>
			{/if}
			<div class="extProductsItems2">{$elementsTpl}</div>
		</div>
	</div>
</div>
