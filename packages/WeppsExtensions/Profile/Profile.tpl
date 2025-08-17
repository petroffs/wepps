<div class="page profile">
	<section id="content-wrapper" class="content-wrapper w_grid w_4col w_gap_medium">
		<section class="sidebar w_hide_view_medium w_1scol">
			<div class="nav pps_animate w_4scol w_2scol_view_medium w_1scol_view_small">
				<ul>
					{foreach name="out" item="item" from=$profileNav}
					<li class="{if $pathItem==$item.alias}active{/if}">
					<a href="{$item.url}"{if $item.event} data-event="{$item.event}"{/if}>{$item.title}</a>
					</li>
					{/foreach}
				</ul>		
			</div>
		</section>
		<section class="content w_3scol w_4scol_view_medium">
			<div class="content-block">
				<h1>{$content.Name}</h1>
				{if $content.Text1}
				<div class="text text-top">{$content.Text1}</div>
				{/if}
			</div>
			<div class="profile-wrapper content-block w_hide w_flex_view_medium">
				<div id="pps-option-nav"><i class="bi bi-sliders"></i></div>
			</div>
			<div id="sidebar-medium" class="content-block w_hide"></div>
			<div class="profile-wrapper content-block">
				<div id="pps-rows-wrapper">
					{$profileTpl}
				</div>
			</div>
		</section>
	</section>
</div>