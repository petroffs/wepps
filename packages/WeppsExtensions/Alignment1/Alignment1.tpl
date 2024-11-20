<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_top">
	<div class="pps_test2 pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin_small pps_flex_start">
			<div class="pps_flex_14 pps_flex_11_view_small test">test</div>
			<div class="pps_flex_14 pps_flex_11_view_small test">test</div>
			<div class="pps_flex_12 pps_flex_11_view_small test">test</div>
			<div class="pps_flex_12 pps_flex_11_view_small test">test</div>
			<div class="pps_flex_14 pps_flex_11_view_small test">test</div>
			<div class="pps_flex_14 pps_flex_11_view_small test">test</div>
		</div>
	</div>
	<div class="pps_test pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin pps_flex_start">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
	<div class="pps_test pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin_medium pps_flex_start">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
	<div class="pps_test2 pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin_large pps_flex_start">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
</div>

<div class="pps_interval"></div>
<div class="pps_interval pps_test"></div>
<div class="pps_interval"></div>

<div class="pps_flex pps_flex_col pps_flex_margin_medium pps_flex_start" style="height: 600px;max-width: calc(100% / 2 + var(--step2))">
	<div class="pps_flex_14 test">test1</div>
	<div class="pps_flex_14 test">test2</div>
	<div class="pps_flex_12 test">test3</div>
	<div class="pps_flex_12 test">test4</div>
	<div class="pps_flex_14 test">test5</div>
	<div class="pps_flex_14 test">test6</div>
</div>

<div class="pps_interval"></div>
<div class="pps_test">test2</div>
<div class="pps_interval"></div>


<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_top">
	<div class="pps_test2 pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin_small pps_flex_start pps_padding">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
	<div class="pps_test pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin pps_flex_start pps_padding">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
	<div class="pps_test pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin_medium pps_flex_start pps_padding">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
	<div class="pps_test2 pps_flex_12">
		<div class="pps_flex pps_inline_flex pps_flex_row pps_flex_margin_large pps_flex_start pps_padding">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
		</div>
	</div>
</div>

<div class="aligment1-wrapper">
	<div class="aligment1-items">
		{assign var=images value=$element.Images_FileUrl|strarr}
		<section class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin pps_animate">
			<div class="img pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small ">
				{if $images.0}
				<img src="/pic/catdir{$images.0}" class="pps_image"/>
				{else}
				<img src="/ext/Template/files/noimage.png" class="pps_image"/>
				{/if}
			</div>
			<div class="text pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">{$element.Text2}</div>
			<div class="text text-13 pps_flex_11">{$element.Text1}</div>
		</section>
	</div>
</div>