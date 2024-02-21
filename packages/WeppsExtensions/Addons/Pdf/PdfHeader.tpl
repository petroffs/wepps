<div class="header">
	<div class="img">
		{if $shopInfo.Logo_FileUrl}
		<img src="{$shopInfo.Logo_FileUrl}" width="120"/>
		{else}
		<img src="{$projectInfo.logo}" width="120"/>
		{/if}
	</div>
	<div class="descr">
		<div>{$shopInfo.Name2}</div>
		<div>{$shopInfo.Citi}</div>
		<div>{$shopInfo.Address}</div>
	</div>
	<div class="descr2">
		<div>{$shopInfo.Phone}</div>
		<div>{$shopInfo.Email}</div>
		<div>{$shopInfo.Site}</div>
	</div>
	<hr/>
</div>