<div class="sidebar w_flex_15 w_flex_11_view_medium w_flex w_flex_col w_hide w_flex_view_medium">
	<ul class="w_list w_flex_max">
		<li>
			<div class="title">
				<a href="" id="sidebar-show"><i class="fa fa-reorder"></i></a>
			</div>
		</li>
	</ul>
</div>
<div class="sidebar w_flex_15 w_flex_11_view_medium w_flex w_flex_col w_hide_view_medium">
	<ul class="w_list w_flex_max">
		<li>
			<div class="title title-search">
				<label class="pps w_input list-search">
					<input type="text" placeholder="Поиск списков" id="list-search"/>
				</label>
			</div>
		</li>
		{foreach name="out" item="item" key="key" from=$lists}
		<li>
			<div class="title">{$translate.$key}</div>
			<ul class="w_list dir">
				{foreach name="o" item="i" key="k" from=$item}
				<li class="{if $i.TableName==$listSettings.TableName}active{/if}"><a
					href="/_wepps/lists/{$i.TableName}/">{$i.Name}</a></li> {/foreach}
			</ul>
		</li> {/foreach}
	</ul>
</div>