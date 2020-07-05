<div class="extBlock normalBlock">
	<ul class="uk-breadcrumb way">
		{foreach name="out" item="item" from=$way} {if
		!$smarty.foreach.out.last}
		<li><a href="{$item.Url}">{$item.Name}</a></li> {else}
		<li class="active"><span>{$item.Name}</span></li> {/if}
		{/foreach}
	</ul>
	<div class="pps_flex pps_flex_row pps_flex_start pps_flex_center">
	<div class="pps_flex_13">
		<h1>{$get.title}</h1>
		</div>
		{if $get.user.Name}
		<div class="personalInfo pps_flex_13 pps_flex pps_flex_row pps_flex_start">
			<div class="pps_flex_23"><a href="/profile/">{$get.user.Name}</a></div>
			<div class="pps_flex_13 pps_right"><a href="javascript:void(0)" onclick="formSenderPPS.send('removeAuth','removeAuthForm','/ext/User/Request.php')">Выйти</a></div>
		</div>
		{/if}
	</div>
	<div class="profile-wrapper pps_flex_11">
		{$tpl}
	</div>
</div>