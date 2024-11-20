<div class="accordion-wrapper">
	<div class="accordion-items pps_animate">
		{foreach name="out" item="item" key="key" from=$elements}
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<section>
			<h2>{$item.Name}</h2>
			<div class="text">{$item.Descr}</div>
		</section>
		{/foreach}
	</div>
</div>