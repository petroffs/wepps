<div class="pps_interval"></div>
<div class="pps_interval"></div>
<div class="pps_interval"></div>



<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_top">
	<div class="pps_test2 pps_flex_12">
		<div class="pps_flex pps_flex_row pps_flex_margin_small pps_flex_start">
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_12 test">test</div>
			<div class="pps_flex_14 test">test</div>
			<div class="pps_flex_14 test">test</div>
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
<div class="pps_test">test2</div>
<div class="pps_interval"></div>

<div class="pps_flex pps_flex_col pps_flex_margin pps_flex_start" style="height: 500px;max-width: calc(100% / 2 + var(--stepHalf))">
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

<div class="elements Alignment1">
	<div class="items">
		{assign var=images value=$element.Images_FileUrl|strarr}
		<div
			class="item pps_flex pps_flex_row pps_flex_row_str pps_flex_margin pps_animate">
			<div
				class="img pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small ">
				<img
					src="/pic/catbig{$images.0|default:'/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg'}"
					class="pps_image" />
			</div>
			<div
				class="descr pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">{$element.Text2}</div>
			<div class="descr">{$element.Text1}</div>
		</div>
	</div>
</div>