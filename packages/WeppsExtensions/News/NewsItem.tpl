<div class="page news-item">
	<section class="news-item-wrapper">
		<div class="w_grid w_2col w_1col_view_small">
			{assign var="images" value=$element.Images_FileUrl|strarr}
			{$element.Id|pps:"News"}
			{if $images.0}
			<div>
				<div class="news-item-img"><img src="/pic/catbigv{$images.0}"/></div>
			</div>
			{/if}
			<div class="w_padding_medium">
				<h1>{$element.Name}</h1>
				<div class="news-item-date">{$element.NDate|date_format:"%d.%m.%Y"}</div>
				<div class="news-item-text">{$element.Descr}</div>
			</div>
		</div>
	</section>
	<div class="pps_interval_large"></div>
	<section>
		<section class="news-wrapper pps_animate w_grid w_3col w_2col_view_medium w_1col_view_small w_gap_medium">
			{foreach name="out" item="item" from=$elements}
			{assign var="images" value=$item.Images_FileUrl|strarr}
			<section>
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
		<div class="pps_interval_medium"></div>
	</section>
	<div class="pps_interval_small"></div>
</div>