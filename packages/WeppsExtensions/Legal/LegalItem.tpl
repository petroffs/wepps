<div class="page legal-item">
	<section class="legal-item-wrapper">
		<div class="w_grid w_2col w_1col_view_small">
			{assign var="images" value=$element.Images_FileUrl|strarr}
			{$element.Id|wepps:"Legal"}
			{if $images.0}
			<div>
				<div class="legal-item-img"><img src="/pic/mediumv{$images.0}"/></div>
			</div>
			{/if}
			<div class="w_padding_medium">
				<div class="legal-item-text">{$element.Text1}</div>
			</div>
		</div>
	</section>
	<div class="w_interval"></div>
	<section>
		<section class="legal-wrapper w_animate">
			{foreach name="out" item="item" from=$elements}
			<section class="w_flex_13 w_flex_12_view_medium">
				{$item.Id|wepps:"Legal"}
				<a href="{$item.Url}">
					<div class="legal-text">
						<div class="title">{$item.Name}</div>
					</div>
				</a>
			</section>
			{/foreach}
		</section>
	</section>
	<div class="w_interval_small"></div>
</div>