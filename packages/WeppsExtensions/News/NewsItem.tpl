<div class="page news-item">
	<section class="news-item-wrapper">
		<div>
			<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_medium">
				{assign var="images" value=$element.Images_FileUrl|strarr}
				{$element.Id|pps:"News"}
				{if $images.0}
				<div class="pps_flex_12 pps_flex_11_view_medium">
					<div class="news-item-img"><img src="/pic/catbigv{$images.0}"/></div>
				</div>
				{/if}
				<div class="pps_flex_12 pps_flex_11_view_medium">
					<h1>{$element.Name}</h1>
					<div class="news-item-date">{$element.NDate|date_format:"%d.%m.%Y"}</div>
					<div class="news-item-text">{$element.Descr}</div>
				</div>
			</div>
		</div>	
	</section>
	<div class="pps_interval"></div>
	<section>
		<section class="news-wrapper pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large pps_animate">
			{foreach name="out" item="item" from=$elements}
			{assign var="images" value=$item.Images_FileUrl|strarr}
			<section class="pps_flex_13 pps_flex_12_view_medium">
				{$item.Id|pps:"News"}
				<a href="{$item.Url}">
					<div class="news-img">
						{if $images.0}
						<img src="/pic/catbig{$images.0}"/>
						{else}
						<img src="/ext/Template/files/noimage640.png"/>
						{/if}
					</div>
					<div class="news-text">
						<div class="title">{$item.Name}</div>
						{if $item.Announce}<div class="text">{$item.Announce}</div>{/if}
					</div>
				</a>
			</section>
			{/foreach}
		</section>
	</section>
	<div class="pps_interval_small"></div>
</div>