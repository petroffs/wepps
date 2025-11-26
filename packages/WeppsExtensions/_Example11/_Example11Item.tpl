<div class="page _Example11-item">
	<section class="_Example11-item-wrapper">
		<div class="w_grid w_2col w_1col_view_small">
			{assign var="images" value=$element.Images_FileUrl|strarr}
			{$element.Id|wepps:"News"}
			{if $images.0}
			<div>
				<div class="_Example11-item-img"><img src="/pic/mediumv{$images.0}"/></div>
			</div>
			{/if}
			<div class="w_padding_medium">
				<h1>{$element.Name}</h1>
				<div class="_Example11-item-date">{$element.NDate|date_format:"%d.%m.%Y"}</div>
				<div class="_Example11-item-text">{$element.Descr}</div>
				<div class="w_interval"></div>
				<a href="" class="w_button" id="ajax-test" data-id="{$element.Id}">AJAX-window test</a>
			</div>
		</div>
	</section>
	<div class="w_interval"></div>
	<section>
		<section class="_Example11-wrapper w_animate w_grid w_3col w_2col_view_medium w_1col_view_small w_gap_medium">
			{foreach name="out" item="item" from=$elements}
			{assign var="images" value=$item.Images_FileUrl|strarr}
			<section class="w_flex_13 w_flex_12_view_medium">
				{$item.Id|wepps:"_Example11"}
				<a href="{$item.Url}">
					<div class="_Example11-img">
						{if $images.0}
						<img src="/pic/medium{$images.0}"/>
						{else}
						<img src="/ext/Template/files/noimage640.png"/>
						{/if}
					</div>
					<div class="_Example11-text">
						<div class="title">{$item.Name}</div>
						{if $item.Announce}<div class="text">{$item.Announce}</div>{/if}
					</div>
				</a>
			</section>
			{/foreach}
		</section>
	</section>
	<div class="w_interval_small"></div>
</div>