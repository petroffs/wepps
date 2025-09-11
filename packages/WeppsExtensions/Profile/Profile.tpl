<div class="profile">
	{if $content.Text1}
		<div class="text text-top">{$content.Text1}</div>
	{/if}
	{* <div class="profile-nav-mobile content-block w_hide w_block_view_medium">
		<div id="pps-option-nav"><i class="bi bi-sliders"></i></div>
	</div> *}
	<section class="profile-nav subnav content-block w_hide_view_medium_">
		<div class="nav pps_animate">
			<ul>
				{foreach name="out" item="item" from=$profileNav}
					<li class="{if $pathItem==$item.alias}active{/if}">
						<a href="{$item.url}" {if $item.event} data-event="{$item.event}" {/if}>{$item.title}</a>
					</li>
				{/foreach}
			</ul>
		</div>
	</section>
	<div class="profile-wrapper content-block">
		<div id="pps-rows-wrapper">
			{$profileTpl}
		</div>
	</div>
</div>