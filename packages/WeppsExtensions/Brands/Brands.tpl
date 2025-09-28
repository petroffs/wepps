{* <section class="brands-wrapper w_flex w_flex_row w_flex_start w_flex_row_str w_flex_margin_large w_animate">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="w_flex_13 w_flex_12_view_medium">
		{$item.Id|wepps:"News"}
		<div class="brands-img">
			{if $images.0}
			<img src="/pic/catbig{$images.0}" class="w_image"/>
			{else}
			<img src="/ext/Template/files/noimage640.png" class="w_image"/>
			{/if}
		</div>
		<div class="brands-text">
			<div class="title">{$item.Name}</div>
			{if $item.Announce}<div class="text">{$item.Announce}</div>{/if}
		</div>
	</section>
	{/foreach}
</section> *}

<section class="brands-wrapper w_grid w_5col w_3col_view_medium w_2col_view_small w_gap_large w_animate">
	{foreach name="out" item="item" key="key" from=$brands}
		<div class="content-block item">
			<div class="title">{$key}</div>
			<div class="brands-items">
				{foreach name="o" item="i" key="k" from=$item}
					<div class="brands-item">
						<a href="{$i.Url}">{$i.PValue}</a>
					</div>
				{/foreach}
			</div>
		</div>
	{/foreach}
</section>