<div class="sidebar pps_flex_15 pps_flex_11_view_medium pps_flex pps_flex_col pps_hide pps_flex_view_medium">
	<ul class="pps_list pps_flex_max">
		<li>
			<div class="title">
				<a href="" id="sidebar-show"><i class="fa fa-reorder"></i></a>
			</div>
		</li>
	</ul>
</div>
<div class="sidebar pps_flex_15 pps_flex_11_view_medium pps_flex pps_flex_col pps_hide_view_medium">
	<ul class="pps_list pps_flex_max">
		<li>
			<div class="title title-search">
				<label class="pps pps_input list-search">
					<input type="text" placeholder="Поиск списков" id="list-search"/>
				</label>
			</div>
		</li>
		{foreach name="out" item="item" key="key" from=$lists}
		<li>
			<div class="title">{$translate.$key}</div>
			<ul class="pps_list dir">
				{foreach name="o" item="i" key="k" from=$item}
				<li class="{if $i.TableName==$listSettings.TableName}active{/if}"><a
					href="/_pps/lists/{$i.TableName}/">{$i.Name}</a></li> {/foreach}
			</ul>
		</li> {/foreach}
	</ul>
</div>